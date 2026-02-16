<?php
/**
 * API GENÉRICA: Retornar dados de qualquer tabela com filtros
 * Usado pelos componentes Filtros + Tabelas
 *
 * Parâmetros GET obrigatórios:
 * - table: Nome da tabela
 * - columns: Colunas a retornar (separadas por vírgula)
 *
 * Parâmetros GET opcionais (filtros):
 * - value_field: Nome da coluna de filtro dropdown (ex: canal)
 * - select: Valor do filtro dropdown (ex: energia97)
 * - date_field: Nome da coluna de data (ex: video_published)
 * - date_start: Data início (YYYY-MM-DD)
 * - date_end: Data fim (YYYY-MM-DD)
 * - order_by: Coluna para ordenar
 * - order_direction: ASC ou DESC (padrão: DESC)
 * - limit: Limite de registros (padrão: 1000, máx: 1000)
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

// Headers
header('Content-Type: application/json; charset=utf-8');

try {
    $db = DB::connect(); // ✅ REGRA #1

    // ========================================
    // 1. VALIDAR PARÂMETROS OBRIGATÓRIOS
    // ========================================

    if (empty($_GET['table']) || empty($_GET['columns'])) {
        http_response_code(400);
        die(json_encode([
            'error' => 'Parâmetros obrigatórios ausentes',
            'required' => ['table', 'columns']
        ]));
    }

    // ========================================
    // 2. SANITIZAR INPUTS
    // ========================================

    // Tabela (apenas alfanuméricos e underscore)
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']);

    // Colunas (separadas por vírgula)
    $columnsRaw = Security::sanitize($_GET['columns']);
    $columns = array_map('trim', explode(',', $columnsRaw));
    $columns = array_map(function($col) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $col);
    }, $columns);
    $columns = array_filter($columns); // Remover vazios

    if (empty($columns)) {
        http_response_code(400);
        die(json_encode(['error' => 'Nenhuma coluna válida especificada']));
    }

    // Condição adicional (para database_condicional)
    $conditionColumn = !empty($_GET['condition_column']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['condition_column']) : null;
    $conditionOperator = !empty($_GET['condition_operator']) ? $_GET['condition_operator'] : '=';
    $conditionValue = !empty($_GET['condition_value']) ? Security::sanitize($_GET['condition_value']) : null;

    // Validar operador
    $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
    if (!in_array($conditionOperator, $allowedOperators)) {
        $conditionOperator = '=';
    }

    // Filtros opcionais
    $valueField = !empty($_GET['value_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['value_field']) : null;
    $selectValue = !empty($_GET['select']) ? Security::sanitize($_GET['select']) : null;

    $dateField = !empty($_GET['date_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['date_field']) : null;
    $dateStart = !empty($_GET['date_start']) ? Security::sanitize($_GET['date_start']) : null;
    $dateEnd = !empty($_GET['date_end']) ? Security::sanitize($_GET['date_end']) : null;

    $orderBy = !empty($_GET['order_by']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['order_by']) : null;
    $orderDirection = strtoupper($_GET['order_direction'] ?? 'DESC');
    $orderDirection = ($orderDirection === 'ASC') ? 'ASC' : 'DESC';

    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 1000) : 1000;

    // ========================================
    // 3. MONTAR QUERY DINÂMICA
    // ========================================

    $selectFields = implode(', ', $columns);
    $query = "SELECT {$selectFields} FROM {$table} WHERE 1=1";
    $params = [];

    // Condição adicional PRIMEIRO (se for database_condicional)
    if ($conditionColumn && $conditionValue) {
        $query .= " AND {$conditionColumn} {$conditionOperator} ?";
        $params[] = $conditionValue;
    }

    // Filtro de valor (dropdown)
    if ($valueField && $selectValue && $selectValue !== '') {
        $query .= " AND {$valueField} = ?";
        $params[] = $selectValue;
    }

    // Filtro de data início
    if ($dateField && $dateStart && $dateStart !== '') {
        $query .= " AND {$dateField} >= ?";
        $params[] = $dateStart;
    }

    // Filtro de data fim
    if ($dateField && $dateEnd && $dateEnd !== '') {
        $query .= " AND {$dateField} <= ?";
        $params[] = $dateEnd;
    }

    // Ordenação
    if ($orderBy) {
        $query .= " ORDER BY {$orderBy} {$orderDirection}";
    } elseif ($dateField) {
        // Se tem campo de data mas não especificou order_by, ordenar por data (mais recentes primeiro)
        $query .= " ORDER BY {$dateField} DESC";
    }

    // Limite
    $query .= " LIMIT {$limit}";

    // ========================================
    // 4. EXECUTAR QUERY
    // ========================================

    $results = $db->query($query, $params);

    // ========================================
    // 5. FORMATAR PARA O COMPONENTE TABELAS
    // ========================================

    // Formato esperado: array de arrays
    // Exemplo: [["val1", "val2", "val3"], ["val4", "val5", "val6"]]

    $rows = [];
    foreach ($results as $row) {
        $rowData = [];
        foreach ($columns as $col) {
            $value = $row[$col] ?? '';

            // Formatar datas (remover hora se for DATETIME)
            // Detecta formato: YYYY-MM-DD HH:MM:SS
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                $value = substr($value, 0, 10); // Pega apenas YYYY-MM-DD
            }

            // Formatar números (se for numérico)
            if (is_numeric($value) && $value > 999) {
                $value = number_format($value, 0, ',', '.');
            }

            $rowData[] = $value;
        }
        $rows[] = $rowData;
    }

    // ========================================
    // 6. RETORNAR JSON
    // ========================================

    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Log do erro
    error_log('table-data.php error: ' . $e->getMessage());
    error_log('Query: ' . ($query ?? 'N/A'));
    error_log('Params: ' . json_encode($params ?? []));

    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar dados',
        'message' => $e->getMessage()
    ]);
}
