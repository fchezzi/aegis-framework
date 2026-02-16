<?php
/**
 * AEGIS - Processar CSV e retornar preview
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // Validar CSRF token
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('Token CSRF não fornecido');
    }

    Security::validateCSRF($_POST['csrf_token']);

    // Validar parâmetros
    if (!isset($_POST['table']) || empty($_POST['table'])) {
        throw new Exception('Tabela não especificada');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo');
    }

    $table = $_POST['table'];
    $file = $_FILES['csv_file'];

    // Validar tabela permitida
    $allowedTables = ['youtube_extra', 'tbl_youtube', 'tbl_website', 'tbl_facebook', 'tbl_instagram', 'tbl_tiktok', 'tbl_x', 'tbl_x_inscritos', 'tbl_app', 'tbl_twitch'];
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabela não permitida');
    }

    // Validar tamanho (10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        $sizeMB = round($file['size'] / 1024 / 1024, 2);
        throw new Exception("Arquivo muito grande ({$sizeMB}MB). Máximo permitido: 10MB");
    }

    // Validar tipo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ['text/plain', 'text/csv', 'application/csv'])) {
        throw new Exception('Tipo de arquivo inválido. Use apenas CSV');
    }

    // Validar e converter encoding para UTF-8
    $content = file_get_contents($file['tmp_name']);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        file_put_contents($file['tmp_name'], $content);
    }

    // Ler CSV
    $rows = [];
    $handle = fopen($file['tmp_name'], 'r');

    if ($handle === false) {
        throw new Exception('Não foi possível abrir o arquivo');
    }

    // Primeira linha = cabeçalhos (detectar delimitador)
    $firstLine = fgets($handle);
    rewind($handle);

    // Detectar delimitador (, ou ;)
    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

    $headers = fgetcsv($handle, 0, $delimiter);

    if (!$headers || empty($headers)) {
        throw new Exception('CSV vazio ou sem cabeçalhos');
    }

    // Limpar cabeçalhos (remover BOM, espaços)
    $headers = array_map(function($header) {
        // Remover BOM em diferentes encodings
        $header = str_replace("\xEF\xBB\xBF", '', $header); // UTF-8 BOM
        $header = str_replace("ï»¿", '', $header); // BOM já decodificado
        $header = preg_replace('/^\x{FEFF}/u', '', $header); // Unicode BOM
        return trim($header);
    }, $headers);

    // Validar cabeçalhos por tabela
    $requiredHeaders = getRequiredHeaders($table);
    $missingHeaders = array_diff($requiredHeaders, $headers);

    if (!empty($missingHeaders)) {
        throw new Exception('Cabeçalhos faltando: ' . implode(', ', $missingHeaders) . '. Headers encontrados: ' . implode(', ', $headers));
    }

    // Ler linhas (usar mesmo delimitador)
    $lineNumber = 1;
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;

        if (count($data) !== count($headers)) {
            continue; // Pular linhas com número errado de colunas
        }

        $row = array_combine($headers, $data);

        // Limpar dados
        $row = array_map('trim', $row);

        // Pular linhas vazias
        if (empty(array_filter($row))) {
            continue;
        }

        $rows[] = $row;
    }

    fclose($handle);

    if (empty($rows)) {
        throw new Exception('Nenhuma linha válida encontrada no CSV');
    }

    // Retornar dados para preview
    echo json_encode([
        'success' => true,
        'table' => $table,
        'total_rows' => count($rows),
        'rows' => $rows,
        'headers' => $headers
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Obter cabeçalhos obrigatórios por tabela
 */
function getRequiredHeaders($table) {
    $headers = [
        'youtube_extra' => ['canal_id', 'data', 'inscritos', 'espectadores_unicos'],
        'tbl_youtube' => ['video_id', 'video_title'],
        'tbl_website' => ['data', 'website_id', 'visitantes'],
        'tbl_facebook' => ['canal_id', 'data'],
        'tbl_instagram' => ['canal_id', 'data'],
        'tbl_tiktok' => ['canal_id', 'data'],
        'tbl_x' => ['canal_id', 'data'],
        'tbl_x_inscritos' => ['canal_id', 'data', 'inscritos'],
        'tbl_app' => ['canal_id', 'data'],
        'tbl_twitch' => ['canal_id', 'data']
    ];

    return $headers[$table] ?? [];
}
