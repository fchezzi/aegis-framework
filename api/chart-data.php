<?php
/**
 * AEGIS Framework - Chart Data API
 *
 * Endpoint para buscar dados de gráficos dinamicamente
 *
 * @package AEGIS
 * @version 1.0.0
 */

// Carregar framework
require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Requer autenticação
if (!Auth::check() && !MemberAuth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticação necessária'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Headers JSON
header('Content-Type: application/json');

try {
    // Validar parâmetros obrigatórios
    $table = $_GET['table'] ?? '';
    $columns = $_GET['columns'] ?? ''; // Pode ser múltiplas: "visualizacoes,seguidores"
    $dateField = $_GET['date_field'] ?? 'data';
    $groupBy = $_GET['group_by'] ?? 'day'; // day, week, month, year

    if (empty($table) || empty($columns)) {
        throw new Exception('Parâmetros obrigatórios ausentes: table, columns');
    }

    // Whitelist de segurança
    $allowedTables = ['tbl_youtube', 'tbl_insta', 'tbl_tiktok', 'tbl_website', 'tbl_facebook', 'youtube_extra'];

    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabela não autorizada');
    }

    // Processar colunas (pode ser múltiplas separadas por vírgula)
    $columnsList = array_map('trim', explode(',', $columns));

    // Sanitizar colunas (aceita letras, números, underscore e ESPAÇOS)
    foreach ($columnsList as $col) {
        if (!preg_match('/^[a-zA-Z0-9_ áàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ]+$/u', $col)) {
            throw new Exception('Nome de coluna inválido: ' . $col);
        }
    }

    // Conectar banco
    $db = DB::connect();

    // SEGURANÇA: Validar nomes de colunas contra schema real
    $validColumns = getTableColumns($db, $table);

    foreach ($columnsList as $col) {
        if (!in_array($col, $validColumns)) {
            throw new Exception("Coluna '$col' não existe na tabela");
        }
    }

    if (!in_array($dateField, $validColumns)) {
        throw new Exception('Campo de data não existe na tabela');
    }

    // Pegar filtros opcionais
    $valueField = !empty($_GET['value_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['value_field']) : null;

    if ($valueField && !in_array($valueField, $validColumns)) {
        throw new Exception('Campo de valor não existe na tabela');
    }

    $selectValue = !empty($_GET['select']) ? Security::sanitize($_GET['select']) : null;
    $dateStart = !empty($_GET['date_start']) ? Security::sanitize($_GET['date_start']) : null;
    $dateEnd = !empty($_GET['date_end']) ? Security::sanitize($_GET['date_end']) : null;
    $comparePeriod = ($_GET['compare_period'] ?? 'no') === 'yes';

    // Construir WHERE
    $whereConditions = [];
    $params = [];

    // Filtro de canal/valor
    if ($valueField && $selectValue && $selectValue !== '' && $selectValue !== 'todos') {
        $whereConditions[] = "$valueField = ?";
        $params[] = $selectValue;
    }

    // Filtro de data
    if ($dateStart) {
        $whereConditions[] = "$dateField >= ?";
        $params[] = $dateStart;
    }
    if ($dateEnd) {
        $whereConditions[] = "$dateField <= ?";
        $params[] = $dateEnd;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Calcular período anterior se necessário
    $previousDateStart = null;
    $previousDateEnd = null;
    if ($comparePeriod && $dateStart && $dateEnd) {
        $start = new DateTime($dateStart);
        $end = new DateTime($dateEnd);
        $interval = $start->diff($end);

        // Calcular início do período anterior
        $previousEnd = clone $start;
        $previousEnd->modify('-1 day');

        // Calcular início do período anterior baseado no intervalo
        $previousStart = clone $previousEnd;
        $previousStart->sub($interval);

        $previousDateStart = $previousStart->format('Y-m-d');
        $previousDateEnd = $previousEnd->format('Y-m-d');
    }

    // Determinar agrupamento e formato de data
    switch ($groupBy) {
        case 'month':
            $dateFormat = "DATE_FORMAT($dateField, '%Y-%m')";
            $dateLabel = "DATE_FORMAT($dateField, '%b/%Y')";
            break;
        case 'week':
            $dateFormat = "DATE_FORMAT($dateField, '%Y-%U')";
            $dateLabel = "DATE_FORMAT($dateField, 'Sem %U/%Y')";
            break;
        case 'year':
            $dateFormat = "DATE_FORMAT($dateField, '%Y')";
            $dateLabel = "DATE_FORMAT($dateField, '%Y')";
            break;
        case 'day':
        default:
            $dateFormat = "$dateField";
            $dateLabel = "DATE_FORMAT($dateField, '%d/%m')";
            break;
    }

    // Construir SELECT com todas as colunas
    $selectColumns = [];
    foreach ($columnsList as $col) {
        $selectColumns[] = "SUM($col) as $col";
    }
    $selectColumnsStr = implode(', ', $selectColumns);

    // Montar SQL
    $sql = "SELECT
                $dateFormat as date_group,
                $dateLabel as date_label,
                $selectColumnsStr
            FROM $table
            $whereClause
            GROUP BY date_group
            ORDER BY date_group ASC";

    // Executar query
    $result = $db->query($sql, $params);

    // Formatar dados para ApexCharts
    $categories = [];
    $series = [];

    // Inicializar arrays de series
    foreach ($columnsList as $col) {
        $series[$col] = [];
    }

    // Processar resultados
    foreach ($result as $row) {
        $categories[] = $row['date_label'];

        foreach ($columnsList as $col) {
            $series[$col][] = (int)($row[$col] ?? 0);
        }
    }

    // Buscar dados do período anterior se necessário
    $previousSeries = [];
    if ($comparePeriod && $previousDateStart && $previousDateEnd) {
        // Reconstruir WHERE para período anterior
        $previousWhereConditions = [];
        $previousParams = [];

        // Filtro de canal/valor
        if ($valueField && $selectValue && $selectValue !== '' && $selectValue !== 'todos') {
            $previousWhereConditions[] = "$valueField = ?";
            $previousParams[] = $selectValue;
        }

        // Filtro de data do período anterior
        $previousWhereConditions[] = "$dateField >= ?";
        $previousParams[] = $previousDateStart;
        $previousWhereConditions[] = "$dateField <= ?";
        $previousParams[] = $previousDateEnd;

        $previousWhereClause = 'WHERE ' . implode(' AND ', $previousWhereConditions);

        // Executar query do período anterior
        $previousSql = "SELECT
                    $dateFormat as date_group,
                    $dateLabel as date_label,
                    $selectColumnsStr
                FROM $table
                $previousWhereClause
                GROUP BY date_group
                ORDER BY date_group ASC";

        $previousResult = $db->query($previousSql, $previousParams);

        // Inicializar arrays de series anteriores
        foreach ($columnsList as $col) {
            $previousSeries[$col] = [];
        }

        // Processar resultados anteriores
        foreach ($previousResult as $row) {
            foreach ($columnsList as $col) {
                $previousSeries[$col][] = (int)($row[$col] ?? 0);
            }
        }
    }

    // Converter para formato ApexCharts
    $apexSeries = [];
    foreach ($columnsList as $col) {
        $colName = ucfirst(str_replace('_', ' ', $col));

        // Série atual
        $apexSeries[] = [
            'name' => $comparePeriod ? "$colName (Atual)" : $colName,
            'data' => $series[$col]
        ];

        // Série anterior (se houver comparação)
        if ($comparePeriod && !empty($previousSeries[$col])) {
            $apexSeries[] = [
                'name' => "$colName (Anterior)",
                'data' => $previousSeries[$col]
            ];
        }
    }

    // Retornar JSON
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'series' => $apexSeries,
        'meta' => [
            'table' => $table,
            'columns' => $columnsList,
            'group_by' => $groupBy,
            'date_range' => [
                'start' => $dateStart,
                'end' => $dateEnd
            ],
            'filter' => [
                'field' => $valueField,
                'value' => $selectValue
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

/**
 * Buscar colunas válidas de uma tabela (protege contra SQL injection)
 */
function getTableColumns($db, $table) {
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $result = $db->query("SHOW COLUMNS FROM `{$table}`");
        $columns = array_column($result, 'Field');
        $cache[$table] = $columns;
        return $columns;
    } catch (Exception $e) {
        return [];
    }
}
