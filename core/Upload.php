<?php
/**
 * @doc Upload
 *
 * Sistema de upload SEGURO de arquivos com múltiplas validações
 *
 * @security CRITICAL - MIME validation, extension whitelist, size limit, sanitization
 * @performance Redimensionamento automático de imagens
 * @author Claude Code + Template
 * @version 2.0.0
 */

class Upload {
    // ✅ Whitelist de MIME types permitidos
    private static $allowed = [
        // Imagens
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'image/svg+xml' => ['svg'],

        // Documentos
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],

        // Texto
        'text/plain' => ['txt'],
        'text/csv' => ['csv']
    ];

    /**
     * Valida MIME type do arquivo usando finfo
     *
     * @param string $tmpPath Path temporário do arquivo
     * @return string|false MIME type se válido, false se não
     */
    public static function validateMime($tmpPath) {
        if (!file_exists($tmpPath)) {
            return false;
        }

        // ✅ USAR finfo (não confia em $_FILES['type'])
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        // ✅ Verificar se MIME está na whitelist
        if (!isset(self::$allowed[$mime])) {
            return false;
        }

        return $mime;
    }

    /**
     * Valida extensão baseada no MIME
     *
     * @param string $filename Nome do arquivo
     * @param string $mime MIME type validado
     * @return bool
     */
    public static function validateExtension($filename, $mime) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // ✅ Extensão deve estar na lista permitida para esse MIME
        if (!in_array($ext, self::$allowed[$mime])) {
            return false;
        }

        return true;
    }

    /**
     * Valida tamanho do arquivo
     *
     * @param int $size Tamanho em bytes
     * @param int $maxMB Tamanho máximo em MB (default: 5MB)
     * @return bool
     */
    public static function validateSize($size, $maxMB = 5) {
        $maxBytes = $maxMB * 1024 * 1024;

        if ($size > $maxBytes || $size <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Valida dimensões da imagem
     *
     * @param string $tmpPath Path temporário
     * @param int $maxWidth Largura máxima (default: 4000px)
     * @param int $maxHeight Altura máxima (default: 4000px)
     * @return bool
     */
    public static function validateDimensions($tmpPath, $maxWidth = 4000, $maxHeight = 4000) {
        $imageInfo = @getimagesize($tmpPath);

        if (!$imageInfo) {
            return false;
        }

        [$width, $height] = $imageInfo;

        if ($width > $maxWidth || $height > $maxHeight) {
            return false;
        }

        return true;
    }

    /**
     * Gera nome de arquivo SEGURO (não usa nome original)
     *
     * @param string $mime MIME type validado
     * @return string Nome sanitizado
     */
    public static function generateSafeName($mime) {
        // ✅ Extensão baseada no MIME (não no nome)
        $extensions = self::$allowed[$mime];
        $ext = $extensions[0]; // Usar primeira extensão da whitelist

        // ✅ Gerar nome único
        $uniqueId = bin2hex(random_bytes(16)); // 32 caracteres hex
        $timestamp = time();

        // ✅ Formato: timestamp_uniqueid.ext
        return "{$timestamp}_{$uniqueId}.{$ext}";
    }

    /**
     * Upload completo com TODAS as validações de segurança
     *
     * @param array $file Arquivo do $_FILES
     * @param string $type Tipo de storage (images, documents)
     * @param int $maxMB Tamanho máximo em MB
     * @param string|null $customName Nome customizado (sem extensão) - se null, gera aleatório
     * @param bool $useSubfolders Criar subpastas YYYY/MM? (default: true)
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public static function uploadFile($file, $type = 'documents', $maxMB = 5, $customName = null, $useSubfolders = true) {
        // ✅ Verificar se arquivo foi enviado
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Nenhum arquivo enviado ou erro no upload'];
        }

        // ✅ 1. VALIDAR MIME TYPE
        $mime = self::validateMime($file['tmp_name']);
        if (!$mime) {
            return ['success' => false, 'error' => 'Tipo de arquivo não permitido'];
        }

        // ✅ 2. VALIDAR EXTENSÃO
        if (!self::validateExtension($file['name'], $mime)) {
            return ['success' => false, 'error' => 'Extensão não corresponde ao tipo de arquivo'];
        }

        // ✅ 3. VALIDAR TAMANHO
        if (!self::validateSize($file['size'], $maxMB)) {
            return ['success' => false, 'error' => "Arquivo muito grande (máximo {$maxMB}MB)"];
        }

        // ✅ 4. SE IMAGEM RASTER: VALIDAR DIMENSÕES (pular SVG - é vetorial)
        if (strpos($mime, 'image/') === 0 && $mime !== 'image/svg+xml') {
            if (!self::validateDimensions($file['tmp_name'], 4000, 4000)) {
                return ['success' => false, 'error' => 'Imagem muito grande (máximo 4000x4000)'];
            }
        }

        // ✅ 5. GERAR NOME SEGURO
        if ($customName) {
            // Nome customizado fornecido - usar ele + extensão baseada no MIME
            $extensions = self::$allowed[$mime];
            $ext = $extensions[0];
            $safeName = $customName . '.' . $ext;
        } else {
            // Gerar nome aleatório padrão
            $safeName = self::generateSafeName($mime);
        }

        // ✅ 6. CRIAR DIRETÓRIO (com ou sem subpastas de data)
        if ($useSubfolders) {
            $year = date('Y');
            $month = date('m');
            $uploadDir = STORAGE_PATH . "/uploads/{$type}/{$year}/{$month}";
            $relativePath = "uploads/{$type}/{$year}/{$month}/{$safeName}";
        } else {
            $uploadDir = STORAGE_PATH . "/uploads/{$type}";
            $relativePath = "uploads/{$type}/{$safeName}";
        }

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Erro ao criar diretório'];
            }
        }

        // ✅ 7. MOVER ARQUIVO
        $fullPath = "{$uploadDir}/{$safeName}";
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Erro ao salvar arquivo'];
        }

        // ✅ 8. PERMISSÕES CORRETAS
        chmod($fullPath, 0644);

        return [
            'success' => true,
            'path' => $relativePath,
            'name' => $safeName,
            'original_name' => $file['name'],
            'mime' => $mime,
            'size' => $file['size']
        ];
    }

    /**
     * Upload de imagem
     *
     * @param array $file Array do $_FILES
     * @param string $destination Pasta de destino (ex: 'palpiteiros')
     * @param array $options Opcoes: maxSize (bytes), allowedTypes (array)
     * @return array ['success' => bool, 'path' => string, 'message' => string]
     */
    public static function image($file, $destination = '', $options = []) {
        error_log("=== UPLOAD::IMAGE v2.0 COM FALLBACK INICIADO ===");

        // Opcoes padrao
        $maxSize = $options['maxSize'] ?? 5242880; // 5MB
        $allowedTypes = $options['allowedTypes'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        // Validar se arquivo foi enviado
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado'];
        }

        // Validar erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro ao enviar arquivo: ' . $file['error']];
        }

        // Validar tamanho
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 1);
            return ['success' => false, 'message' => "Arquivo muito grande. Maximo: {$maxMB}MB"];
        }

        // Validar tipo MIME (com fallback para servidores sem fileinfo)
        $mimeType = null;

        // Tentar finfo_open (mais seguro)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
        // Fallback: mime_content_type
        elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
        }
        // Fallback: validar apenas extensão
        else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $validExtensions)) {
                return ['success' => false, 'message' => 'Tipo de arquivo nao permitido. Use: JPG, PNG, GIF ou WEBP'];
            }
        }

        // Validar MIME type se conseguiu detectar
        if ($mimeType && !in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de arquivo nao permitido. Use: JPG, PNG, GIF ou WEBP'];
        }

        // Gerar nome do arquivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Se customName foi fornecido, usar ele
        if (isset($options['customName']) && !empty($options['customName'])) {
            $filename = $options['customName'] . '.' . $extension;
        } else {
            // Nome padrão: uniqid + timestamp
            $filename = uniqid() . '_' . time() . '.' . $extension;
        }

        // Definir caminho completo
        $uploadDir = UPLOAD_PATH . ($destination ? $destination . '/' : '');

        // Criar diretorio se nao existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fullPath = $uploadDir . $filename;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'message' => 'Erro ao salvar arquivo'];
        }

        // Retornar caminho relativo ao storage
        $relativePath = ($destination ? $destination . '/' : '') . $filename;

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'message' => 'Upload realizado com sucesso'
        ];
    }

    /**
     * Deletar arquivo
     *
     * @param string $path Caminho relativo (ex: 'palpiteiros/foto.jpg')
     * @return bool
     */
    public static function delete($path) {
        if (empty($path)) {
            return false;
        }

        $fullPath = UPLOAD_PATH . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Obter URL publica do arquivo
     *
     * @param string $path Caminho relativo (ex: 'palpiteiros/foto.jpg')
     * @return string URL completa
     */
    public static function url($path) {
        if (empty($path)) {
            return '';
        }

        return url('/storage/uploads/' . $path);
    }
}
