<?php
/**
 * API: Listar tabelas do banco de dados
 * Usado pelo Page Builder para popular dropdowns
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Apenas admins podem listar estrutura do banco
Auth::require();

header('Content-Type: application/json');

try {
    // Buscar todas as tabelas do banco
    $query = "SHOW TABLES";
    $results = DB::query($query);

    // Prefixos permitidos (não expor tabelas do sistema)
    $allowedPrefixes = ['tbl_', 'canais', 'youtube_', 'pages', 'modules', 'components'];
    $blockedTables = ['users', 'members', 'sessions', 'groups', 'permissions'];

    $tables = [];
    foreach ($results as $row) {
        $tableName = array_values($row)[0];

        // Bloquear tabelas sensíveis explicitamente
        if (in_array($tableName, $blockedTables)) {
            continue;
        }

        // Verificar se começa com prefixo permitido
        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($tableName, $prefix) === 0) {
                $allowed = true;
                break;
            }
        }

        if ($allowed) {
            $tables[] = [
                'value' => $tableName,
                'label' => $tableName
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'tables' => $tables
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
