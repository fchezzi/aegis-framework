<?php
/**
 * AEGIS - Get CSRF Token (Public Endpoint)
 * Endpoint público para pegar CSRF token (usado por n8n)
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // Gerar ou pegar CSRF token existente
    Security::generateCSRF();
    $csrfToken = $_SESSION['csrf_token'];

    echo json_encode([
        'csrf_token' => $csrfToken
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
