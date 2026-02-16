<?php
/**
 * AEGIS Framework - Upload de Imagens
 *
 * Endpoint para fazer upload de imagens
 */

// Capturar qualquer output acidental
ob_start();

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Limpar buffer antes de header
ob_end_clean();

header('Content-Type: application/json');

// Requer autenticação (admin ou member)
try {
    $isAuth = Auth::check();
    $isMember = MemberAuth::check();

    if (!$isAuth && !$isMember) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticação necessária'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na autenticação: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar se há arquivo
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo enviado ou erro no upload');
    }

    $file = $_FILES['image'];

    // Validar tipo de arquivo (apenas imagens)
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WEBP');
    }

    // Validar tamanho (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Arquivo muito grande. Máximo: 5MB');
    }

    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '_' . time() . '.' . $extension;

    // Diretório de upload
    $uploadDir = __DIR__ . '/../assets/img/uploads/';

    // Criar diretório se não existir
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Mover arquivo
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Erro ao salvar arquivo');
    }

    // Retornar PATH RELATIVO (funciona em dev e produção)
    $imagePath = '/assets/img/uploads/' . $filename;

    echo json_encode([
        'success' => true,
        'url' => $imagePath,
        'filename' => $filename
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
