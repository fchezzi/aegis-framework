<?php
/**
 * Endpoint: Listar tabelas do banco de dados
 * Usado pelo Page Builder para popular dropdown de tabelas
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // Buscar todas as tabelas do banco
    $tables = DB::query("SHOW TABLES");

    // Extrair nomes das tabelas
    $tableNames = [];
    $dbName = DB_NAME;

    foreach ($tables as $table) {
        // O resultado vem como array com chave "Tables_in_{db_name}"
        $key = "Tables_in_{$dbName}";
        if (isset($table[$key])) {
            $tableNames[] = $table[$key];
        }
    }

    // Retornar JSON
    echo json_encode([
        'success' => true,
        'tables' => $tableNames
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
