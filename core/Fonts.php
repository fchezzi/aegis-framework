<?php
/**
 * @doc Fonts
 * @title Sistema de Gerenciamento de Fontes
 * @description
 * Gerencia upload, armazenamento e seleção de fontes WOFF2 customizadas.
 * Suporta múltiplos contextos: Admin, Members, Frontend.
 *
 * Recursos:
 * - Upload seguro de WOFF2 para /storage/fonts/
 * - Validação de MIME type e extensão
 * - Metadata extraction (family, weight, style)
 * - Listagem para dropdowns de seleção
 * - Exclusão segura (arquivo + registro)
 *
 * @security Apenas WOFF2 permitido, validação rigorosa de MIME
 * @author AEGIS Framework
 * @version 1.0.0
 *
 * @example
 * // Listar fontes ativas
 * $fonts = Fonts::getActive();
 *
 * // Upload de fonte
 * $result = Fonts::upload($_FILES['font_file']);
 * if ($result['success']) {
 *     echo "Fonte enviada: " . $result['name'];
 * }
 *
 * // Deletar fonte
 * Fonts::delete($fontId);
 */

class Fonts {

    /**
     * MIME types permitidos (APENAS WOFF2)
     */
    private static $allowedMimes = [
        'font/woff2',
        'application/font-woff2',
        'application/octet-stream' // Alguns servidores servem WOFF2 como octet-stream
    ];

    /**
     * Extensões permitidas
     */
    private static $allowedExtensions = ['woff2'];

    /**
     * Tamanho máximo (2MB)
     */
    private static $maxSize = 2097152; // 2MB em bytes

    /**
     * Listar todas as fontes ativas
     *
     * @return array Lista de fontes
     */
    public static function getActive() {
        $db = DB::connect();

        $fonts = $db->select('tbl_fonts', ['active' => 1]);

        return $fonts ? $fonts : [];
    }

    /**
     * Alias para getActive()
     *
     * @return array
     */
    public static function getAll() {
        return self::getActive();
    }

    /**
     * Listar famílias únicas de fontes (para dropdowns)
     * Retorna array simples com nomes das famílias
     *
     * @return array ['Roboto', 'Inter', 'Open Sans', ...]
     */
    public static function getFamilies() {
        $db = DB::connect();

        $result = $db->query(
            "SELECT DISTINCT family FROM tbl_fonts WHERE active = 1 ORDER BY family ASC"
        );

        if (!$result) {
            return [];
        }

        // Extrair apenas os nomes das famílias
        $families = [];
        foreach ($result as $row) {
            $families[] = $row['family'];
        }

        return $families;
    }

    /**
     * Buscar fontes por família
     *
     * @param string $family Nome da família (ex: "Roboto")
     * @return array Fontes da família especificada
     */
    public static function getByFamily($family) {
        $db = DB::connect();

        $fonts = $db->query(
            "SELECT * FROM tbl_fonts WHERE family = ? AND active = 1 ORDER BY weight ASC",
            [$family]
        );

        return $fonts ? $fonts : [];
    }

    /**
     * Buscar fonte por ID
     *
     * @param string $id UUID da fonte
     * @return array|null
     */
    public static function find($id) {
        $db = DB::connect();

        $fonts = $db->select('tbl_fonts', ['id' => $id]);

        return $fonts && count($fonts) > 0 ? $fonts[0] : null;
    }

    /**
     * Upload de fonte WOFF2 com validações de segurança
     *
     * @param array $file Array do $_FILES
     * @param string|null $customName Nome customizado (opcional)
     * @return array ['success' => bool, 'id' => string|null, 'error' => string|null, ...]
     */
    public static function upload($file, $customName = null) {
        // Validar se arquivo foi enviado
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Nenhum arquivo enviado ou erro no upload'];
        }

        // 1. VALIDAR TAMANHO
        if ($file['size'] > self::$maxSize || $file['size'] <= 0) {
            $maxMB = round(self::$maxSize / 1048576, 1);
            return ['success' => false, 'error' => "Arquivo muito grande (máximo {$maxMB}MB)"];
        }

        // 2. VALIDAR EXTENSÃO
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedExtensions)) {
            return ['success' => false, 'error' => 'Apenas arquivos WOFF2 são permitidos'];
        }

        // 3. VALIDAR MIME TYPE (com fallback para servidores sem fileinfo)
        $mimeType = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
        }

        // Aceitar WOFF2 com MIME types variados (diferentes servidores)
        if ($mimeType && !in_array($mimeType, self::$allowedMimes)) {
            return ['success' => false, 'error' => 'Tipo de arquivo não permitido (MIME inválido)'];
        }

        // 4. EXTRAIR METADATA DO NOME DO ARQUIVO
        $metadata = self::extractMetadataFromFilename($file['name']);

        // 5. GERAR NOME SEGURO DO ARQUIVO
        if ($customName) {
            // Sanitizar nome customizado
            $safeName = self::sanitizeFilename($customName) . '.woff2';
        } else {
            // Usar nome original sanitizado
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $safeName = self::sanitizeFilename($originalName) . '.woff2';
        }

        // 6. VERIFICAR SE JÁ EXISTE
        $db = DB::connect();
        $existing = $db->select('tbl_fonts', ['filename' => $safeName]);
        if ($existing && count($existing) > 0) {
            return ['success' => false, 'error' => 'Já existe uma fonte com este nome'];
        }

        // 7. CRIAR DIRETÓRIO /storage/fonts/ SE NÃO EXISTIR
        $uploadDir = ROOT_PATH . 'storage/fonts';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Erro ao criar diretório de fontes'];
            }
        }

        // 8. MOVER ARQUIVO
        $fullPath = $uploadDir . '/' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Erro ao salvar arquivo'];
        }

        // 9. PERMISSÕES CORRETAS
        chmod($fullPath, 0644);

        // 10. INSERIR NO BANCO
        $id = Security::generateUUID();

        $insertData = [
            'id' => $id,
            'name' => $metadata['name'],
            'family' => $metadata['family'],
            'weight' => $metadata['weight'],
            'style' => $metadata['style'],
            'filename' => $safeName,
            'file_size' => $file['size'],
            'active' => 1
        ];

        $result = $db->insert('tbl_fonts', $insertData);

        if (!$result) {
            // Rollback: deletar arquivo se falhou no banco
            @unlink($fullPath);
            return ['success' => false, 'error' => 'Erro ao salvar no banco de dados'];
        }

        return [
            'success' => true,
            'id' => $id,
            'name' => $metadata['name'],
            'family' => $metadata['family'],
            'filename' => $safeName,
            'message' => 'Fonte enviada com sucesso'
        ];
    }

    /**
     * Deletar fonte (arquivo + registro)
     *
     * @param string $id UUID da fonte
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function delete($id) {
        $db = DB::connect();

        // 1. BUSCAR FONTE
        $font = self::find($id);

        if (!$font) {
            return ['success' => false, 'error' => 'Fonte não encontrada'];
        }

        // 2. DELETAR ARQUIVO
        $filePath = ROOT_PATH . 'storage/fonts/' . $font['filename'];
        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                return ['success' => false, 'error' => 'Erro ao deletar arquivo'];
            }
        }

        // 3. DELETAR REGISTRO
        $result = $db->delete('tbl_fonts', ['id' => $id]);

        if (!$result) {
            return ['success' => false, 'error' => 'Erro ao deletar registro do banco'];
        }

        return [
            'success' => true,
            'message' => 'Fonte deletada com sucesso'
        ];
    }

    /**
     * Extrair metadata do nome do arquivo
     * Exemplos:
     * - "roboto-regular.woff2" → family: Roboto, weight: normal, style: normal
     * - "inter-bold-italic.woff2" → family: Inter, weight: bold, style: italic
     * - "opensans-700.woff2" → family: Open Sans, weight: 700, style: normal
     *
     * @param string $filename Nome do arquivo
     * @return array ['name', 'family', 'weight', 'style']
     */
    private static function extractMetadataFromFilename($filename) {
        // Remover extensão
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Converter para lowercase para análise
        $nameLower = strtolower($name);

        // Substituir hífens/underscores por espaços
        $parts = preg_split('/[-_]/', $nameLower);

        // Primeira parte é a família
        $family = ucwords($parts[0]);

        // Detectar weight
        $weight = 'normal';
        $weightMap = [
            'thin' => '100',
            'extralight' => '200',
            'light' => '300',
            'regular' => 'normal',
            'medium' => '500',
            'semibold' => '600',
            'bold' => 'bold',
            'extrabold' => '800',
            'black' => '900',
            '100' => '100',
            '200' => '200',
            '300' => '300',
            '400' => 'normal',
            '500' => '500',
            '600' => '600',
            '700' => 'bold',
            '800' => '800',
            '900' => '900'
        ];

        foreach ($parts as $part) {
            if (isset($weightMap[$part])) {
                $weight = $weightMap[$part];
                break;
            }
        }

        // Detectar style
        $style = 'normal';
        if (in_array('italic', $parts) || in_array('oblique', $parts)) {
            $style = 'italic';
        }

        // Nome completo para display
        $displayName = ucwords(str_replace(['-', '_'], ' ', $name));

        return [
            'name' => $displayName,
            'family' => $family,
            'weight' => $weight,
            'style' => $style
        ];
    }

    /**
     * Sanitizar nome de arquivo
     * Remove caracteres especiais, mantém apenas letras, números, hífens
     *
     * @param string $filename Nome para sanitizar
     * @return string Nome sanitizado
     */
    private static function sanitizeFilename($filename) {
        // Converter para lowercase
        $filename = strtolower($filename);

        // Remover acentos
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);

        // Manter apenas letras, números, hífens e underscores
        $filename = preg_replace('/[^a-z0-9\-_]/', '-', $filename);

        // Remover hífens duplicados
        $filename = preg_replace('/-+/', '-', $filename);

        // Remover hífens no início/fim
        $filename = trim($filename, '-');

        return $filename;
    }

    /**
     * Gerar CSS @font-face para fonte específica
     *
     * @param array $font Registro da fonte
     * @return string CSS @font-face
     */
    public static function generateFontFace($font) {
        $url = '/aegis/storage/fonts/' . $font['filename'];

        $css = "@font-face {\n";
        $css .= "    font-family: '" . addslashes($font['family']) . "';\n";
        $css .= "    src: url('" . $url . "') format('woff2');\n";
        $css .= "    font-weight: " . $font['weight'] . ";\n";
        $css .= "    font-style: " . $font['style'] . ";\n";
        $css .= "    font-display: swap;\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Gerar CSS completo com todas as fontes ativas
     *
     * @return string CSS com todos os @font-face
     */
    public static function generateAllFontFaces() {
        $fonts = self::getActive();

        $css = "/* Fontes Customizadas - Gerado automaticamente */\n\n";

        foreach ($fonts as $font) {
            $css .= self::generateFontFace($font);
            $css .= "\n";
        }

        return $css;
    }
}
