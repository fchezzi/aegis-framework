<?php
/**
 * API: Listar colunas de uma tabela
 * Usado pelo Page Builder para popular dropdowns dependentes
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Apenas admins podem listar estrutura do banco
Auth::require();

header('Content-Type: application/json');

try {
    $table = $_GET['table'] ?? '';

    if (empty($table)) {
        throw new Exception('Parâmetro "table" é obrigatório');
    }

    // Sanitizar nome da tabela (segurança)
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    // Buscar colunas da tabela
    $query = "SHOW COLUMNS FROM `{$table}`";
    $results = DB::query($query);

    $columns = [];
    foreach ($results as $row) {
        $columnName = $row['Field'];
        $columns[] = [
            'value' => $columnName,
            'label' => $columnName . ' (' . $row['Type'] . ')'
        ];
    }

    echo json_encode([
        'success' => true,
        'columns' => $columns
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
