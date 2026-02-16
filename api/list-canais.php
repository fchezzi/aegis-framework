<?php
/**
 * API: Listar canais disponíveis da tabela canais
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Requer autenticação
if (!Auth::check() && !MemberAuth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticação necessária'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = DB::connect();

    // Buscar canais da tabela canais (retorna id e nome)
    $sql = "SELECT
                id,
                nome,
                plataforma,
                ativo
            FROM canais
            WHERE ativo = 1
            ORDER BY nome ASC";

    $canais = $db->query($sql);

    echo json_encode([
        'success' => true,
        'total_canais' => count($canais),
        'canais' => $canais
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
