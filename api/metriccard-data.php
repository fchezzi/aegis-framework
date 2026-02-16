<?php
/**
 * AEGIS Framework - MetricCard Data API
 *
 * Endpoint para calcular métricas dinamicamente (usado pelo JavaScript para reload)
 *
 * @package AEGIS
 * @version 1.0.0
 * @since 9.0.4
 */

// Carregar framework
require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Debug autenticação
$authCheck = Auth::check();
$memberCheck = MemberAuth::check();

// Requer autenticação
if (!$authCheck && !$memberCheck) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Autenticação necessária',
        'debug' => [
            'session_id' => session_id(),
            'auth_check' => $authCheck,
            'member_check' => $memberCheck,
            'has_member_id' => isset($_SESSION['member_id'])
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Headers JSON
header('Content-Type: application/json');

try {
    $reqId = substr(md5(uniqid()), 0, 6);
    error_log("[$reqId] === NOVA REQUISIÇÃO API ===");
    error_log("[$reqId] GET: " . json_encode($_GET));

    // Validar parâmetros obrigatórios
    $table = $_GET['table'] ?? '';
    $column = $_GET['column'] ?? '';
    $operation = strtoupper($_GET['operation'] ?? 'SUM');
    $format = $_GET['format'] ?? 'number';
    $comparePeriod = $_GET['compare_period'] ?? 'yes';
    $dateField = $_GET['date_field'] ?? '';

    // Detectar se é multi-table (verifica se tem source_1_table)
    $isMultiTable = !empty($_GET['source_1_table']);

    error_log("[$reqId] isMultiTable: " . ($isMultiTable ? 'SIM' : 'NÃO'));

    if ($isMultiTable) {
        if (empty($operation)) {
            throw new Exception('Parâmetros obrigatórios ausentes: operation');
        }
    } else {
        if (empty($table) || empty($column) || empty($operation)) {
            throw new Exception('Parâmetros obrigatórios ausentes: table, column, operation');
        }
    }

    // Whitelist de segurança
    $allowedTables = ['tbl_youtube', 'tbl_insta', 'tbl_tiktok', 'tbl_website', 'tbl_facebook', 'tbl_instagram', 'tbl_x', 'tbl_x_inscritos', 'tbl_app', 'tbl_twitch', 'youtube_extra', 'users', 'pages', 'modules'];
    $allowedOperations = ['SUM', 'COUNT', 'AVG', 'MAX', 'MIN', 'LAST'];

    if ($isMultiTable) {
        // Extrair e validar todas as tabelas
        $sourcesArray = extractMultiTableSourcesFromGet();
        if (empty($sourcesArray)) {
            throw new Exception('Nenhuma fonte de dados configurada');
        }
        foreach ($sourcesArray as $source) {
            if (!in_array($source['table'], $allowedTables)) {
                throw new Exception('Tabela não autorizada: ' . $source['table']);
            }
        }
    } else {
        if (!in_array($table, $allowedTables)) {
            throw new Exception('Tabela não autorizada');
        }
    }

    if (!in_array($operation, $allowedOperations)) {
        throw new Exception('Operação não permitida');
    }

    // Conectar banco
    $db = DB::connect();

    // Se é multi-table, processar de forma diferente
    if ($isMultiTable) {
        $result = calculateMultiTableMetric($db, $operation, $comparePeriod);

        if ($result === false) {
            throw new Exception('Erro ao calcular métrica multi-tabela');
        }

        // Formatar valores
        $currentValue = $result['current'];
        $previousValue = $result['previous'];
        $percentChange = $result['percent_change'];
        $formattedValue = formatValue($currentValue, $format);
        $previousFormatted = $previousValue !== null ? formatValue($previousValue, $format) : null;

        // Retornar JSON
        echo json_encode([
            'success' => true,
            'current' => $currentValue,
            'current_formatted' => $formattedValue,
            'previous' => $previousValue,
            'previous_formatted' => $previousFormatted,
            'percent_change' => $percentChange
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // SEGURANÇA: Validar nomes de colunas contra schema real
    $validColumns = getTableColumns($db, $table);

    if (!in_array($column, $validColumns)) {
        throw new Exception('Coluna não existe na tabela');
    }

    if ($dateField && !in_array($dateField, $validColumns)) {
        throw new Exception('Campo de data não existe na tabela');
    }

    // Condição adicional (para métrica condicional)
    $conditionColumn = !empty($_GET['condition_column']) ? $_GET['condition_column'] : null;

    if ($conditionColumn && !in_array($conditionColumn, $validColumns)) {
        throw new Exception('Campo de condição não existe na tabela');
    }
    $conditionOperator = !empty($_GET['condition_operator']) ? $_GET['condition_operator'] : '=';
    $conditionValue = !empty($_GET['condition_value']) ? Security::sanitize($_GET['condition_value']) : null;

    // Validar operador
    $allowedConditionalOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
    if (!in_array($conditionOperator, $allowedConditionalOperators)) {
        $conditionOperator = '=';
    }

    // Pegar filtros da URL (MESMA LÓGICA que table-data.php)
    $valueField = !empty($_GET['value_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['value_field']) : null;
    $selectValue = !empty($_GET['select']) ? Security::sanitize($_GET['select']) : null;

    $dateStart = !empty($_GET['date_start']) ? Security::sanitize($_GET['date_start']) : null;
    $dateEnd = !empty($_GET['date_end']) ? Security::sanitize($_GET['date_end']) : null;

    // Construir WHERE
    $whereConditions = [];
    $params = [];

    // Condição adicional (SEMPRE primeira, se existir)
    if ($conditionColumn && $conditionValue) {
        $whereConditions[] = "$conditionColumn $conditionOperator ?";
        $params[] = $conditionValue;
    }

    // Filtro de valor (dropdown) - filtro direto por ID
    if ($valueField && $selectValue && $selectValue !== '') {
        $whereConditions[] = "$valueField = ?";
        $params[] = $selectValue;
    }

    // Filtro de data início (incluir dia completo desde 00:00:00)
    if ($dateField && $dateStart && $dateStart !== '') {
        $whereConditions[] = "$dateField >= ?";
        $params[] = $dateStart . ' 00:00:00';
    }

    // Filtro de data fim (incluir dia completo até 23:59:59)
    if ($dateField && $dateEnd && $dateEnd !== '') {
        $whereConditions[] = "$dateField <= ?";
        $params[] = $dateEnd . ' 23:59:59';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Lógica especial para operação LAST (dados mensais)
    if ($operation === 'LAST') {
        // Precisa de date_end para saber qual mês buscar
        if (!$dateEnd || !$dateField) {
            throw new Exception('Operação LAST requer date_field e date_end');
        }

        // Extrair ano e mês do date_end
        $endDate = new DateTime($dateEnd);
        $targetYear = $endDate->format('Y');
        $targetMonth = $endDate->format('m');

        // Substituir filtros de data por filtro de ano/mês específico
        $whereConditions = array_filter($whereConditions, function($cond) use ($dateField) {
            return strpos($cond, $dateField) === false;
        });

        $whereConditions[] = "YEAR($dateField) = ?";
        $whereConditions[] = "MONTH($dateField) = ?";

        // Reconstruir params removendo date_start e date_end
        $params = [];

        // Verificar se tem canal específico
        $hasSpecificChannel = false;
        if ($valueField && $selectValue && $selectValue !== '' && $selectValue !== 'todos') {
            $params[] = $selectValue;
            $hasSpecificChannel = true;
        }

        $params[] = $targetYear;
        $params[] = $targetMonth;

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        // Se tem canal específico: buscar última linha daquele canal
        // Se é "todos": somar todos os canais do mês
        if ($hasSpecificChannel) {
            $sql = "SELECT $column as value FROM $table $whereClause ORDER BY $dateField DESC LIMIT 1";
        } else {
            $sql = "SELECT SUM($column) as value FROM $table $whereClause";
        }
    } else {
        // Operações normais (SUM, AVG, etc.)
        $sql = "SELECT $operation($column) as value FROM $table $whereClause";
    }

    // Executar query
    try {
        $result = $db->query($sql, $params);
        $currentValue = $result[0]['value'] ?? null;
    } catch (Exception $e) {
        $currentValue = null;
    }

    // Comparar com período anterior?
    $previousValue = null;
    $percentChange = null;
    $previousStartDate = null;
    $previousEndDate = null;

    if ($comparePeriod === 'yes' && $dateField && $dateStart && $dateEnd) {
        // Lógica diferente para LAST (mensal)
        if ($operation === 'LAST') {
            $previousResult = calculatePreviousMonth(
                $db,
                $table,
                $column,
                $dateField,
                $dateStart,
                $dateEnd,
                $valueField,
                $selectValue
            );
        } else {
            // Operações normais (SUM, AVG, etc.)
            $previousResult = calculatePreviousPeriod(
                $db,
                $table,
                $column,
                $operation,
                $dateField,
                $dateStart,
                $dateEnd,
                $valueField,
                $selectValue,
                $conditionColumn,
                $conditionOperator,
                $conditionValue
            );
        }

        if ($previousResult !== false && is_array($previousResult)) {
            $previousValue = $previousResult['value'];
            $previousStartDate = $previousResult['start_date'] ?? null;
            $previousEndDate = $previousResult['end_date'] ?? null;

            // Calcular percentual de mudança
            if ($previousValue > 0) {
                $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
            } elseif ($currentValue > 0) {
                $percentChange = 100;
            } else {
                $percentChange = 0;
            }
        }
    }

    // Formatar valor
    $formattedValue = formatValue($currentValue, $format);
    $previousFormatted = $previousValue !== null ? formatValue($previousValue, $format) : null;

    // Retornar JSON
    echo json_encode([
        'success' => true,
        'current' => $currentValue,
        'current_formatted' => $formattedValue,
        'previous' => $previousValue,
        'previous_formatted' => $previousFormatted,
        'previous_start_date' => $previousStartDate,
        'previous_end_date' => $previousEndDate,
        'percent_change' => $percentChange
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
 * Calcular valor do mês anterior (para operação LAST)
 */
function calculatePreviousMonth($db, $table, $column, $dateField, $dateStart, $dateEnd, $valueField = null, $selectValue = null) {
    try {
        // Extrair ano e mês do date_end
        $endDate = new DateTime($dateEnd);
        $targetYear = $endDate->format('Y');
        $targetMonth = $endDate->format('m');

        // Verificar se é filtro anual (diferença >= 365 dias)
        $start = new DateTime($dateStart);
        $end = new DateTime($dateEnd);
        $daysDiff = $start->diff($end)->days;
        $isYearlyFilter = ($daysDiff >= 365);

        if ($isYearlyFilter) {
            // Comparar com mesmo mês do ano anterior
            $prevYear = $targetYear - 1;
            $prevMonth = $targetMonth;
        } else {
            // Comparar com mês anterior (usar 'first day of last month' para evitar bug do PHP)
            $prevDate = clone $endDate;
            $prevDate->modify('first day of last month');
            $prevYear = $prevDate->format('Y');
            $prevMonth = $prevDate->format('m');
        }

        // Construir WHERE
        $whereConditions = [];
        $params = [];

        // Verificar se tem canal específico PRIMEIRO
        $hasSpecificChannel = false;
        if ($valueField && $selectValue && $selectValue !== '' && $selectValue !== 'todos') {
            $whereConditions[] = "$valueField = ?";
            $params[] = $selectValue;
            $hasSpecificChannel = true;
        }

        // Adicionar filtros de ano/mês
        $whereConditions[] = "YEAR($dateField) = ?";
        $whereConditions[] = "MONTH($dateField) = ?";
        $params[] = $prevYear;
        $params[] = $prevMonth;

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        // Se tem canal específico: buscar última linha daquele canal
        // Se é "todos": somar todos os canais do mês
        if ($hasSpecificChannel) {
            $sql = "SELECT $column as value FROM $table $whereClause ORDER BY $dateField DESC LIMIT 1";
        } else {
            $sql = "SELECT SUM($column) as value FROM $table $whereClause";
        }

        $result = $db->query($sql, $params);

        return [
            'value' => $result[0]['value'] ?? null,
            'start_date' => "$prevYear-$prevMonth-01",
            'end_date' => "$prevYear-$prevMonth-31"
        ];

    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calcular métrica do período anterior
 */
function calculatePreviousPeriod($db, $table, $column, $operation, $dateField, $dateStart, $dateEnd, $valueField = null, $selectValue = null, $conditionColumn = null, $conditionOperator = '=', $conditionValue = null) {
    try {
        // Calcular duração do período
        $start = new DateTime($dateStart);
        $end = new DateTime($dateEnd);
        $interval = $start->diff($end);
        $days = $interval->days;

        // Calcular período anterior (CORRETO)
        $prevStart = clone $start;
        $prevStart->modify("-" . ($days + 1) . " days");
        $prevEnd = clone $end;
        $prevEnd->modify("-" . ($days + 1) . " days");

        // Construir WHERE (mesma ordem que o cálculo atual!)
        $whereConditions = [];
        $params = [];

        // Condição adicional PRIMEIRO (se for métrica condicional)
        if ($conditionColumn && $conditionValue) {
            $whereConditions[] = "$conditionColumn $conditionOperator ?";
            $params[] = $conditionValue;
        }

        // Filtro de valor (dropdown)
        if ($valueField && $selectValue && $selectValue !== '') {
            $whereConditions[] = "$valueField = ?";
            $params[] = $selectValue;
        }

        // Datas por último (incluir dia completo até 23:59:59)
        $whereConditions[] = "$dateField >= ?";
        $whereConditions[] = "$dateField <= ?";
        $params[] = $prevStart->format('Y-m-d') . ' 00:00:00';
        $params[] = $prevEnd->format('Y-m-d') . ' 23:59:59';

        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        // Executar query
        $sql = "SELECT $operation($column) as value FROM $table $whereClause";
        $result = $db->query($sql, $params);

        return [
            'value' => $result[0]['value'] ?? 0,
            'start_date' => $prevStart->format('Y-m-d'),
            'end_date' => $prevEnd->format('Y-m-d')
        ];

    } catch (Exception $e) {
        return false;
    }
}

/**
 * Detectar campo de filtro automaticamente
 */
function detectFilterField($table) {
    $mapping = [
        'tbl_youtube' => 'canal',
        'tbl_insta' => 'account',
        'tbl_tiktok' => 'account'
    ];

    return $mapping[$table] ?? null;
}

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

/**
 * Formatar número de acordo com o formato especificado
 */
function formatValue($value, $format) {
    // Se valor é null, retornar "n/a"
    if ($value === null) {
        return 'n/a';
    }

    switch ($format) {
        case 'decimal':
            return number_format($value, 2, ',', '.');
        case 'currency':
            return '$' . number_format($value, 2, ',', '.');
        case 'number':
        default:
            return number_format($value, 0, ',', '.');
    }
}

/**
 * Extrair fontes de dados do $_GET
 */
function extractMultiTableSourcesFromGet() {
    $sources = [];

    for ($i = 1; $i <= 10; $i++) {
        $table = $_GET["source_{$i}_table"] ?? '';
        $column = $_GET["source_{$i}_column"] ?? '';
        $dateField = $_GET["source_{$i}_date_field"] ?? '';

        if (!empty($table) && !empty($column)) {
            $sources[] = [
                'table' => $table,
                'column' => $column,
                'date_field' => $dateField
            ];
        }
    }

    return $sources;
}

/**
 * Calcular métrica de múltiplas tabelas
 */
function calculateMultiTableMetric($db, $operation, $comparePeriod) {
    try {
        // Extrair fontes
        $sources = extractMultiTableSourcesFromGet();

        // Pegar filtros da URL
        $dateStart = $_GET['date_start'] ?? '';
        $dateEnd = $_GET['date_end'] ?? '';

        // Construir UNION ALL
        $unionParts = [];
        $params = [];

        foreach ($sources as $source) {
            $table = $source['table'];
            $column = $source['column'];
            $dateField = $source['date_field'] ?? '';
            $whereConditions = [];

            // Filtro de data (usa o campo específico de cada fonte)
            if ($dateField && $dateStart) {
                $whereConditions[] = "$dateField >= ?";
                $params[] = $dateStart . ' 00:00:00';
            }
            if ($dateField && $dateEnd) {
                $whereConditions[] = "$dateField <= ?";
                $params[] = $dateEnd . ' 23:59:59';
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            $unionParts[] = "SELECT $column as value FROM $table $whereClause";
        }

        // Montar SQL final
        $unionSQL = implode(' UNION ALL ', $unionParts);
        $sql = "SELECT $operation(value) as total FROM ($unionSQL) combined";

        // Executar query
        $result = $db->query($sql, $params);
        $currentValue = $result[0]['total'] ?? 0;

        // Comparar com período anterior?
        $previousValue = null;
        $percentChange = null;

        if ($comparePeriod === 'yes' && $dateStart && $dateEnd) {
            $previousResult = calculateMultiTablePreviousPeriod(
                $db,
                $sources,
                $operation,
                $dateStart,
                $dateEnd
            );

            if ($previousResult !== false && is_array($previousResult)) {
                $previousValue = $previousResult['value'];

                // Calcular percentual
                if ($previousValue > 0) {
                    $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
                } elseif ($currentValue > 0) {
                    $percentChange = 100;
                } else {
                    $percentChange = 0;
                }
            }
        }

        return [
            'current' => $currentValue,
            'previous' => $previousValue,
            'percent_change' => $percentChange
        ];

    } catch (Exception $e) {
        error_log("MultiTable API Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcular período anterior para múltiplas tabelas
 */
function calculateMultiTablePreviousPeriod($db, $sources, $operation, $dateStart, $dateEnd) {
    try {
        // Calcular duração do período
        $start = new DateTime($dateStart);
        $end = new DateTime($dateEnd);
        $interval = $start->diff($end);
        $days = $interval->days;

        // Calcular período anterior
        $prevStart = clone $start;
        $prevStart->modify("-" . ($days + 1) . " days");
        $prevEnd = clone $end;
        $prevEnd->modify("-" . ($days + 1) . " days");

        // Construir UNION ALL
        $unionParts = [];
        $params = [];

        foreach ($sources as $source) {
            $table = $source['table'];
            $column = $source['column'];
            $dateField = $source['date_field'] ?? '';
            $whereConditions = [];

            if ($dateField) {
                $whereConditions[] = "$dateField >= ?";
                $params[] = $prevStart->format('Y-m-d') . ' 00:00:00';
                $whereConditions[] = "$dateField <= ?";
                $params[] = $prevEnd->format('Y-m-d') . ' 23:59:59';
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            $unionParts[] = "SELECT $column as value FROM $table $whereClause";
        }

        // Montar SQL final
        $unionSQL = implode(' UNION ALL ', $unionParts);
        $sql = "SELECT $operation(value) as total FROM ($unionSQL) combined";

        // Executar query
        $result = $db->query($sql, $params);
        $value = $result[0]['total'] ?? 0;

        return [
            'value' => $value,
            'start_date' => $prevStart->format('Y-m-d'),
            'end_date' => $prevEnd->format('Y-m-d')
        ];

    } catch (Exception $e) {
        error_log("MultiTable Previous Period Error: " . $e->getMessage());
        return false;
    }
}
