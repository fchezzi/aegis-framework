<?php
/**
 * AEGIS Framework - Cards Component
 *
 * Cards dinâmicos (métrica, estatística, etc)
 *
 * @package AEGIS
 * @version 2.0.0
 * @since 9.0.3
 */

class Cards {
    /**
     * Tabelas permitidas (whitelist de segurança)
     */
    private static $allowedTables = [
        'tbl_youtube',
        'tbl_insta',
        'tbl_tiktok',
        'tbl_website',
        'tbl_facebook',
        'tbl_instagram',
        'tbl_x',
        'tbl_x_inscritos',
        'tbl_app',
        'tbl_twitch',
        'youtube_extra',
        'users',
        'pages',
        'modules'
    ];

    /**
     * Operações permitidas (whitelist de segurança)
     */
    private static $allowedOperations = ['SUM', 'COUNT', 'AVG', 'MAX', 'MIN'];

    /**
     * Renderizar componente Cards
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'filter_group' => 'default',
            'card_type' => 'metrica',
            'title' => 'Total',
            'table' => '',
            'column' => '',
            'operation' => 'SUM',
            'icon' => 'trending-up',
            'icon_color' => 'purple',
            'layout' => 'default',
            'date_field' => '',
            'filter_value_field' => '',
            'compare_period' => 'yes',
            'format' => 'number',
            'show_link' => 'no',
            'link_url' => '#',
            'link_text' => 'Ver Detalhes'
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Validar card_type
        $cardType = $data['card_type'] ?? '';

        // Se card_type está vazio, não renderizar (ainda não foi configurado)
        if (empty($cardType)) {
            return '';
        }

        // Renderizar baseado no tipo
        if ($cardType === 'metrica') {
            return self::renderMetricCard($data);
        } elseif ($cardType === 'dados_mensais') {
            return self::renderMonthlyDataCard($data);
        } elseif ($cardType === 'metrica_condicional') {
            return self::renderConditionalMetricCard($data);
        } elseif ($cardType === 'metrica_multi_table') {
            return self::renderMultiTableCard($data);
        }

        return '<div class="card-error">Erro: Tipo de card desconhecido</div>';
    }

    /**
     * Renderizar card de métrica
     */
    private static function renderMetricCard(array $data): string {
        // Validações
        if (empty($data['table']) || empty($data['column']) || empty($data['operation'])) {
            return '<div class="metric-card-error">Erro: Campos obrigatórios não preenchidos (table, column, operation)</div>';
        }

        // Validar tabela
        if (!in_array($data['table'], self::$allowedTables)) {
            return '<div class="metric-card-error">Erro: Tabela não autorizada</div>';
        }

        // Validar operação
        if (!in_array(strtoupper($data['operation']), self::$allowedOperations)) {
            return '<div class="metric-card-error">Erro: Operação não permitida</div>';
        }

        // Calcular métrica (sempre, com ou sem filtro)
        $result = self::calculateMetric($data);

        if ($result === false) {
            return '<div class="metric-card-error">Erro ao calcular métrica</div>';
        }

        // Renderizar HTML
        $html = self::renderCard($data, $result);

        // Incluir script de filtros (apenas uma vez por página)
        static $filtrosScriptAdded = false;
        if (!$filtrosScriptAdded) {
            $html .= '<script src="' . url('/assets/js/cards-filtros.js') . '"></script>';
            $filtrosScriptAdded = true;
        }

        return $html;
    }

    /**
     * Renderizar card de dados mensais
     */
    private static function renderMonthlyDataCard(array $data): string {
        // Validações
        if (empty($data['table']) || empty($data['column'])) {
            return '<div class="metric-card-error">Erro: Campos obrigatórios não preenchidos (table, column)</div>';
        }

        // Validar tabela
        if (!in_array($data['table'], self::$allowedTables)) {
            return '<div class="metric-card-error">Erro: Tabela não autorizada</div>';
        }

        // Calcular valor mensal (sempre, com ou sem filtro)
        $result = self::calculateMonthlyData($data);

        if ($result === false) {
            return '<div class="metric-card-error">Erro ao calcular dados mensais</div>';
        }

        // Renderizar HTML (usa mesma função de render do card de métrica)
        $html = self::renderCard($data, $result);

        // Incluir script de filtros (apenas uma vez por página)
        static $filtrosScriptAdded = false;
        if (!$filtrosScriptAdded) {
            $html .= '<script src="' . url('/assets/js/cards-filtros.js') . '"></script>';
            $filtrosScriptAdded = true;
        }

        return $html;
    }

    /**
     * Renderizar card de métrica condicional (com filtro adicional)
     */
    private static function renderConditionalMetricCard(array $data): string {
        // Validações
        if (empty($data['table']) || empty($data['column']) || empty($data['operation'])) {
            return '<div class="metric-card-error">Erro: Campos obrigatórios não preenchidos (table, column, operation)</div>';
        }

        // Validar tabela
        if (!in_array($data['table'], self::$allowedTables)) {
            return '<div class="metric-card-error">Erro: Tabela não autorizada</div>';
        }

        // Validar operação
        if (!in_array(strtoupper($data['operation']), self::$allowedOperations)) {
            return '<div class="metric-card-error">Erro: Operação não permitida</div>';
        }

        // Calcular métrica condicional
        $result = self::calculateConditionalMetric($data);

        if ($result === false) {
            return '<div class="metric-card-error">Erro ao calcular métrica condicional</div>';
        }

        // Renderizar HTML (usa mesma função de render)
        $html = self::renderCard($data, $result);

        // Incluir script de filtros (apenas uma vez por página)
        static $filtrosScriptAdded = false;
        if (!$filtrosScriptAdded) {
            $html .= '<script src="' . url('/assets/js/cards-filtros.js') . '"></script>';
            $filtrosScriptAdded = true;
        }

        return $html;
    }

    /**
     * Calcular métrica do banco de dados
     *
     * @param array $data Configuração
     * @return array|false Resultado com valor atual e comparação
     */
    private static function calculateMetric(array $data) {
        try {
            $db = DB::connect();

            // Sanitizar inputs
            $table = $data['table'];
            $column = Security::sanitize($data['column']);
            $operation = strtoupper($data['operation']);
            $dateField = Security::sanitize($data['date_field']);

            // Pegar filtros da URL (IGUAL aos cards manuais que funcionam!)
            $selectFilter = $_GET['select'] ?? '';  // Valor selecionado no filtro
            $dateStart = $_GET['date_start'] ?? '';
            $dateEnd = $_GET['date_end'] ?? '';

            // DEBUG
            error_log("=== CARD NORMAL DEBUG (table={$table}) ===");
            error_log("dateStart: " . ($dateStart ?: 'VAZIO'));
            error_log("dateEnd: " . ($dateEnd ?: 'VAZIO'));

            // Construir WHERE
            $whereConditions = [];
            $params = [];

            // Filtro de valor (dropdown) - filtro direto por ID
            if ($selectFilter && $selectFilter !== '' && $selectFilter !== 'todos') {
                $filterField = self::detectFilterField($table);
                if ($filterField) {
                    $whereConditions[] = "$filterField = ?";
                    $params[] = $selectFilter;
                }
            }

            // Filtro de data
            if ($dateField && $dateStart) {
                $whereConditions[] = "$dateField >= ?";
                $params[] = $dateStart;
            }
            if ($dateField && $dateEnd) {
                $whereConditions[] = "$dateField <= ?";
                $params[] = $dateEnd;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Montar SQL
            $sql = "SELECT $operation($column) as value FROM $table $whereClause";

            // Executar query
            try {
                $result = $db->query($sql, $params);
                $currentValue = $result[0]['value'] ?? 0;
            } catch (Exception $e) {
                $currentValue = 0;
            }

            // Comparar com período anterior?
            $previousValue = null;
            $percentChange = null;
            $previousStartDate = null;
            $previousEndDate = null;

            if ($data['compare_period'] === 'yes' && $dateField && $dateStart && $dateEnd) {
                $previousResult = self::calculatePreviousPeriod(
                    $db,
                    $table,
                    $column,
                    $operation,
                    $dateField,
                    $dateStart,
                    $dateEnd,
                    $selectFilter  // Filtro de canal/valor
                );

                if ($previousResult !== false && is_array($previousResult)) {
                    $previousValue = $previousResult['value'];
                    $previousStartDate = $previousResult['start_date'];
                    $previousEndDate = $previousResult['end_date'];

                    // Calcular percentual de mudança
                    if ($previousValue > 0) {
                        $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
                    } elseif ($currentValue > 0) {
                        $percentChange = 100; // Crescimento de 0 para algo = 100%
                    } else {
                        $percentChange = 0;
                    }
                }
            }

            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'percent_change' => $percentChange,
                'previous_start_date' => $previousStartDate,
                'previous_end_date' => $previousEndDate
            ];

        } catch (Exception $e) {
            error_log("MetricCard Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular métrica do período anterior
     *
     * @param object $db Conexão do banco
     * @param string $table Tabela
     * @param string $column Coluna
     * @param string $operation Operação
     * @param string $dateField Campo de data
     * @param string $dateStart Data inicial do período atual
     * @param string $dateEnd Data final do período atual
     * @param string $filterValue Filtro adicional
     * @return float|false Valor do período anterior
     */
    private static function calculatePreviousPeriod($db, $table, $column, $operation, $dateField, $dateStart, $dateEnd, $filterValue = '') {
        try {
            // Calcular duração do período
            $start = new DateTime($dateStart);
            $end = new DateTime($dateEnd);
            $interval = $start->diff($end);
            $days = $interval->days;

            // Calcular período anterior (mesma duração)
            $prevEnd = clone $start;
            $prevEnd->modify('-1 day');
            $prevStart = clone $prevEnd;
            $prevStart->modify("-{$days} days");

            // Construir WHERE
            $whereConditions = [
                "$dateField >= ?",
                "$dateField <= ?"
            ];
            $params = [
                $prevStart->format('Y-m-d'),
                $prevEnd->format('Y-m-d')
            ];

            // Filtro adicional
            if ($filterValue) {
                // Se for tbl_youtube, usar campo "canal" diretamente
                if ($table === 'tbl_youtube') {
                    $whereConditions[] = "canal = ?";
                    $params[] = $filterValue;
                } else {
                    // Outros casos: tentar detectar campo
                    $filterField = self::detectFilterField($table);
                    if ($filterField) {
                        $whereConditions[] = "$filterField = ?";
                        $params[] = $filterValue;
                    }
                }
            }

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
            error_log("MetricCard Previous Period Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular dados mensais (busca valor do mês específico)
     *
     * @param array $data Configuração
     * @return array|false Resultado com valor atual e comparação
     */
    private static function calculateMonthlyData(array $data) {
        try {
            $db = DB::connect();

            // Sanitizar inputs
            $table = $data['table'];
            $column = Security::sanitize($data['column']);
            $dateField = Security::sanitize($data['date_field']);

            // Pegar filtros da URL
            $selectFilter = $_GET['select'] ?? '';
            $dateStart = $_GET['date_start'] ?? '';
            $dateEnd = $_GET['date_end'] ?? '';

            // Se não tem date_end, não dá pra buscar
            if (!$dateEnd || !$dateField) {
                return ['current' => null, 'previous' => null, 'percent_change' => null];
            }

            // Extrair ano e mês do date_end
            $endDate = new DateTime($dateEnd);
            $targetYear = $endDate->format('Y');
            $targetMonth = $endDate->format('m');

            // Construir WHERE para o mês específico
            $whereConditions = [
                "YEAR($dateField) = ?",
                "MONTH($dateField) = ?"
            ];
            $params = [$targetYear, $targetMonth];

            // Filtro de valor (dropdown)
            $hasSpecificChannel = false;
            if ($selectFilter && $selectFilter !== '' && $selectFilter !== 'todos') {
                $filterField = self::detectFilterField($table);
                if ($filterField) {
                    $whereConditions[] = "$filterField = ?";
                    $params[] = $selectFilter;
                    $hasSpecificChannel = true;
                }
            }

            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

            // Se tem canal específico: buscar última linha
            // Se é "todos": somar todos os canais
            if ($hasSpecificChannel) {
                $sql = "SELECT $column as value FROM $table $whereClause ORDER BY $dateField DESC LIMIT 1";
            } else {
                $sql = "SELECT SUM($column) as value FROM $table $whereClause";
            }

            try {
                $result = $db->query($sql, $params);
                $currentValue = $result[0]['value'] ?? null;
            } catch (Exception $e) {
                $currentValue = null;
            }

            // Comparar com período anterior?
            $previousValue = null;
            $percentChange = null;

            if ($data['compare_period'] === 'yes' && $dateStart && $dateEnd) {
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
                    // Comparar com mês anterior
                    $prevDate = clone $endDate;
                    $prevDate->modify('-1 month');
                    $prevYear = $prevDate->format('Y');
                    $prevMonth = $prevDate->format('m');
                }

                // Buscar valor do período anterior
                $prevWhereConditions = [
                    "YEAR($dateField) = ?",
                    "MONTH($dateField) = ?"
                ];
                $prevParams = [$prevYear, $prevMonth];

                // Filtro de valor
                if ($selectFilter && $selectFilter !== '' && $selectFilter !== 'todos') {
                    $filterField = self::detectFilterField($table);
                    if ($filterField) {
                        $prevWhereConditions[] = "$filterField = ?";
                        $prevParams[] = $selectFilter;
                    }
                }

                $prevWhereClause = 'WHERE ' . implode(' AND ', $prevWhereConditions);

                // Usar mesma lógica: canal específico = LIMIT 1, todos = SUM
                if ($hasSpecificChannel) {
                    $prevSql = "SELECT $column as value FROM $table $prevWhereClause ORDER BY $dateField DESC LIMIT 1";
                } else {
                    $prevSql = "SELECT SUM($column) as value FROM $table $prevWhereClause";
                }

                try {
                    $prevResult = $db->query($prevSql, $prevParams);
                    $previousValue = $prevResult[0]['value'] ?? 0;

                    // Calcular percentual de mudança
                    if ($previousValue > 0) {
                        $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
                    } elseif ($currentValue > 0) {
                        $percentChange = 100;
                    } else {
                        $percentChange = 0;
                    }
                } catch (Exception $e) {
                    $previousValue = 0;
                    $percentChange = null;
                }
            }

            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'percent_change' => $percentChange
            ];

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Calcular métrica condicional (com filtro adicional)
     *
     * @param array $data Configuração
     * @return array|false Resultado com valor atual e comparação
     */
    private static function calculateConditionalMetric(array $data) {
        try {
            $db = DB::connect();

            // Sanitizar inputs
            $table = $data['table'];
            $column = Security::sanitize($data['column']);
            $operation = strtoupper($data['operation']);
            $dateField = Security::sanitize($data['date_field']);

            // Condição adicional
            $conditionColumn = Security::sanitize($data['condition_column'] ?? '');
            $conditionOperator = $data['condition_operator'] ?? '=';
            $conditionValue = $data['condition_value'] ?? '';

            // Validar operador (segurança)
            $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
            if (!in_array($conditionOperator, $allowedOperators)) {
                $conditionOperator = '=';
            }

            // Pegar filtros da URL
            $selectFilter = $_GET['select'] ?? '';
            $dateStart = $_GET['date_start'] ?? '';
            $dateEnd = $_GET['date_end'] ?? '';

            // Construir WHERE
            $whereConditions = [];
            $params = [];

            // Condição ADICIONAL (fixa, configurada no PageBuilder)
            if ($conditionColumn && $conditionValue) {
                $whereConditions[] = "$conditionColumn $conditionOperator ?";
                $params[] = $conditionValue;
            }

            // Filtro de valor (dropdown) - vem do filtro da página
            if ($selectFilter && $selectFilter !== '' && $selectFilter !== 'todos') {
                $filterField = self::detectFilterField($table);
                if ($filterField) {
                    $whereConditions[] = "$filterField = ?";
                    $params[] = $selectFilter;
                }
            }

            // Filtro de data
            if ($dateField && $dateStart) {
                $whereConditions[] = "$dateField >= ?";
                $params[] = $dateStart;
            }
            if ($dateField && $dateEnd) {
                $whereConditions[] = "$dateField <= ?";
                $params[] = $dateEnd;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Montar SQL
            $sql = "SELECT $operation($column) as value FROM $table $whereClause";

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

            if ($data['compare_period'] === 'yes' && $dateField && $dateStart && $dateEnd) {
                $previousResult = self::calculateConditionalPreviousPeriod(
                    $db,
                    $table,
                    $column,
                    $operation,
                    $dateField,
                    $dateStart,
                    $dateEnd,
                    $selectFilter,
                    $conditionColumn,
                    $conditionOperator,
                    $conditionValue
                );

                if ($previousResult !== false && is_array($previousResult)) {
                    $previousValue = $previousResult['value'];

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

            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'percent_change' => $percentChange
            ];

        } catch (Exception $e) {
            error_log("ConditionalMetricCard Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular métrica condicional do período anterior
     */
    private static function calculateConditionalPreviousPeriod($db, $table, $column, $operation, $dateField, $dateStart, $dateEnd, $filterValue, $conditionColumn, $conditionOperator, $conditionValue) {
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

            // Construir WHERE (mesma ordem que calculateConditionalMetric!)
            $whereConditions = [];
            $params = [];

            // Condição adicional PRIMEIRO (igual ao cálculo atual)
            if ($conditionColumn && $conditionValue) {
                $whereConditions[] = "$conditionColumn $conditionOperator ?";
                $params[] = $conditionValue;
            }

            // Filtro de valor
            if ($filterValue) {
                $filterField = self::detectFilterField($table);
                if ($filterField) {
                    $whereConditions[] = "$filterField = ?";
                    $params[] = $filterValue;
                }
            }

            // Datas por último
            $whereConditions[] = "$dateField >= ?";
            $whereConditions[] = "$dateField <= ?";
            $params[] = $prevStart->format('Y-m-d');
            $params[] = $prevEnd->format('Y-m-d');

            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

            // Executar query
            $sql = "SELECT $operation($column) as value FROM $table $whereClause";
            $result = $db->query($sql, $params);

            return [
                'value' => $result[0]['value'] ?? null
            ];

        } catch (Exception $e) {
            error_log("ConditionalMetricCard Previous Period Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detectar campo de filtro automaticamente
     * (melhorar no futuro para ser mais flexível)
     *
     * @param string $table Nome da tabela
     * @return string|null Nome do campo de filtro
     */
    private static function detectFilterField($table) {
        $mapping = [
            'tbl_youtube' => 'canal',
            'youtube_extra' => 'canal_id',
            'tbl_insta' => 'account',
            'tbl_tiktok' => 'account'
        ];

        return $mapping[$table] ?? null;
    }

    /**
     * Formatar número de acordo com o formato especificado
     *
     * @param float $value Valor
     * @param string $format Formato (number, decimal, currency)
     * @return string Valor formatado
     */
    private static function formatValue($value, $format) {
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
     * Renderizar HTML do card (router para diferentes layouts)
     *
     * @param array $data Configuração
     * @param array $result Resultado da métrica
     * @return string HTML
     */
    private static function renderCard(array $data, array $result): string {
        $layout = $data['layout'] ?? 'default';

        switch ($layout) {
            case 'split':
                return self::renderSplitLayout($data, $result);
            case 'default':
            default:
                return self::renderDefaultLayout($data, $result);
        }
    }

    /**
     * Gerar atributos data-* para o card (usado pelo JavaScript)
     *
     * @param array $data Configuração
     * @return string Atributos HTML
     */
    private static function generateDataAttributes(array $data): string {
        // Determinar operação: se é dados_mensais, usar LAST
        $operation = $data['operation'] ?? 'SUM';
        if (isset($data['card_type']) && $data['card_type'] === 'dados_mensais') {
            $operation = 'LAST';
        }

        $cardType = $data['card_type'] ?? 'metrica';

        $attributes = [
            'data-api-url="' . htmlspecialchars(url('/api/metriccard-data.php'), ENT_QUOTES, 'UTF-8') . '"',
            'data-operation="' . htmlspecialchars($operation, ENT_QUOTES, 'UTF-8') . '"',
            'data-format="' . htmlspecialchars($data['format'], ENT_QUOTES, 'UTF-8') . '"',
            'data-compare-period="' . htmlspecialchars($data['compare_period'], ENT_QUOTES, 'UTF-8') . '"',
            'data-filter-group="' . htmlspecialchars($data['filter_group'], ENT_QUOTES, 'UTF-8') . '"',
            'data-card-type="' . htmlspecialchars($cardType, ENT_QUOTES, 'UTF-8') . '"'
        ];

        // Para multi-table, adicionar source_X_* ao invés de table/column
        if ($cardType === 'metrica_multi_table') {
            for ($i = 1; $i <= 10; $i++) {
                if (!empty($data["source_{$i}_table"])) {
                    $attributes[] = 'data-source-' . $i . '-table="' . htmlspecialchars($data["source_{$i}_table"], ENT_QUOTES, 'UTF-8') . '"';
                }
                if (!empty($data["source_{$i}_column"])) {
                    $attributes[] = 'data-source-' . $i . '-column="' . htmlspecialchars($data["source_{$i}_column"], ENT_QUOTES, 'UTF-8') . '"';
                }
                if (!empty($data["source_{$i}_date_field"])) {
                    $attributes[] = 'data-source-' . $i . '-date-field="' . htmlspecialchars($data["source_{$i}_date_field"], ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        } else {
            // Cards normais (table e column únicos)
            $attributes[] = 'data-table="' . htmlspecialchars($data['table'], ENT_QUOTES, 'UTF-8') . '"';
            $attributes[] = 'data-column="' . htmlspecialchars($data['column'], ENT_QUOTES, 'UTF-8') . '"';
        }

        // Adicionar date_field se existir
        if (!empty($data['date_field'])) {
            $attributes[] = 'data-date-field="' . htmlspecialchars($data['date_field'], ENT_QUOTES, 'UTF-8') . '"';
        }

        // Adicionar filter_value_field se existir
        if (!empty($data['filter_value_field'])) {
            $attributes[] = 'data-filter-value-field="' . htmlspecialchars($data['filter_value_field'], ENT_QUOTES, 'UTF-8') . '"';
        }

        // Adicionar condição adicional se for métrica condicional
        if (isset($data['card_type']) && $data['card_type'] === 'metrica_condicional') {
            if (!empty($data['condition_column'])) {
                $attributes[] = 'data-condition-column="' . htmlspecialchars($data['condition_column'], ENT_QUOTES, 'UTF-8') . '"';
            }
            if (!empty($data['condition_operator'])) {
                $attributes[] = 'data-condition-operator="' . htmlspecialchars($data['condition_operator'], ENT_QUOTES, 'UTF-8') . '"';
            }
            if (!empty($data['condition_value'])) {
                $attributes[] = 'data-condition-value="' . htmlspecialchars($data['condition_value'], ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        // Gerar ID único
        if ($cardType === 'metrica_multi_table') {
            $cardId = 'metric-card-' . md5('multi' . $data['operation'] . $data['filter_group']);
        } else {
            $cardId = 'metric-card-' . md5($data['table'] . $data['column'] . $data['operation'] . $data['filter_group']);
        }
        $attributes[] = 'id="' . $cardId . '"';

        return implode(' ', $attributes);
    }

    /**
     * Renderizar layout padrão (original)
     *
     * @param array $data Configuração
     * @param array $result Resultado da métrica
     * @return string HTML
     */
    private static function renderDefaultLayout(array $data, array $result): string {
        // Dados
        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $icon = htmlspecialchars($data['icon'], ENT_QUOTES, 'UTF-8');
        $iconColor = htmlspecialchars($data['icon_color'], ENT_QUOTES, 'UTF-8');
        $showLink = ($data['show_link'] === 'yes');
        $linkUrl = htmlspecialchars($data['link_url'], ENT_QUOTES, 'UTF-8');
        $linkText = htmlspecialchars($data['link_text'], ENT_QUOTES, 'UTF-8');

        // Valores
        $currentValue = self::formatValue($result['current'], $data['format']);
        $percentChange = $result['percent_change'];

        // Atributos data-*
        $dataAttrs = self::generateDataAttributes($data);

        // HTML
        $html = '<div class="metric-card metric-card--' . $iconColor . '" ' . $dataAttrs . '>';

        // Ícone
        $html .= '<div class="metric-card__icon">';
        $html .= '<i data-lucide="' . $icon . '"></i>';
        $html .= '</div>';

        // Conteúdo
        $html .= '<div class="metric-card__content">';
        $html .= '<div class="metric-card__title">' . $title . '</div>';
        $html .= '<div class="metric-card__value">' . $currentValue . '</div>';
        $html .= '</div>';

        // Rodapé
        $html .= '<div class="metric-card__footer">';

        // Comparação
        if ($percentChange !== null) {
            $isPositive = $percentChange >= 0;
            $changeClass = $isPositive ? 'metric-card__change--positive' : 'metric-card__change--negative';
            $changeIcon = $isPositive ? 'trending-up' : 'trending-down';
            $changeSymbol = $isPositive ? '+' : '';

            $html .= '<div class="metric-card__change ' . $changeClass . '">';
            $html .= $changeSymbol . number_format($percentChange, 1, ',', '.') . '% ';
            $html .= '<i data-lucide="' . $changeIcon . '" class="metric-card__change-icon"></i>';
            $html .= '</div>';
        }

        // Link
        if ($showLink) {
            $html .= '<a href="' . $linkUrl . '" class="metric-card__link">' . $linkText . '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderizar layout "split" (35% ícone/valor + 65% título/detalhes)
     *
     * @param array $data Configuração
     * @param array $result Resultado da métrica
     * @return string HTML
     */
    private static function renderSplitLayout(array $data, array $result): string {
        // Dados
        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $icon = htmlspecialchars($data['icon'], ENT_QUOTES, 'UTF-8');
        $showLink = ($data['show_link'] === 'yes');
        $linkUrl = htmlspecialchars($data['link_url'], ENT_QUOTES, 'UTF-8');
        $linkText = htmlspecialchars($data['link_text'], ENT_QUOTES, 'UTF-8');

        // Valores
        $currentValue = self::formatValue($result['current'], $data['format']);
        $percentChange = $result['percent_change'];

        // Atributos data-*
        $dataAttrs = self::generateDataAttributes($data);

        // HTML - EXATAMENTE como você fez manualmente (sem modificadores extras)
        $html = '<div class="metric-card" ' . $dataAttrs . '>';

        // SEÇÃO 1: Ícone (35%)
        $html .= '<div class="metric-card__icon">';
        $html .= '<i class="metric-card__theicon" data-lucide="' . $icon . '"></i>';
        $html .= '</div>';

        // SEÇÃO 2: Título + Valor Principal (65%)
        $html .= '<div class="metric-card__title">';
        $html .= '<h5>' . $title . '</h5>';
        $html .= '<p>' . $currentValue . '</p>';
        $html .= '</div>';

        // SEÇÃO 3: Percentual de Mudança (35%)
        $html .= '<div class="metric-card__value">';
        if ($percentChange !== null) {
            $isPositive = $percentChange >= 0;
            $changeClass = $isPositive ? 'metric-card__value--positive' : 'metric-card__value--negative';
            $changeSymbol = $isPositive ? '+ ' : '- ';

            $html .= '<p class="' . $changeClass . '">';
            $html .= $changeSymbol . number_format(abs($percentChange), 0) . '%';
            $html .= '</p>';
        } else {
            $html .= '<p>-</p>';
        }
        $html .= '</div>';

        // SEÇÃO 4: Link "Ver Detalhes" (65%)
        $html .= '<div class="metric-card__details">';
        if ($showLink) {
            $html .= '<a href="' . $linkUrl . '">' . $linkText . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Extrair fontes de dados dos campos source_1_table, source_1_column, etc
     */
    private static function extractMultiTableSources($data) {
        $sources = [];

        for ($i = 1; $i <= 10; $i++) {
            $table = $data["source_{$i}_table"] ?? '';
            $column = $data["source_{$i}_column"] ?? '';
            $dateField = $data["source_{$i}_date_field"] ?? '';

            // Se tabela e coluna estão preenchidas, adicionar
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
     * Renderizar card de múltiplas tabelas
     */
    private static function renderMultiTableCard(array $data): string {
        // Validações
        if (empty($data['operation'])) {
            return '<div class="metric-card-error">Erro: Operação não preenchida</div>';
        }

        // Extrair fontes dos campos
        $sources = self::extractMultiTableSources($data);

        if (empty($sources)) {
            return '<div class="metric-card-error">Erro: Nenhuma fonte de dados configurada. Configure ao menos uma fonte (tabela + coluna)</div>';
        }

        // Validar todas as tabelas
        foreach ($sources as $source) {
            if (!in_array($source['table'], self::$allowedTables)) {
                return '<div class="metric-card-error">Erro: Tabela não autorizada: ' . htmlspecialchars($source['table']) . '</div>';
            }
        }

        // Validar operação
        $operation = strtoupper($data['operation']);
        if (!in_array($operation, self::$allowedOperations)) {
            return '<div class="metric-card-error">Erro: Operação não autorizada</div>';
        }

        // Calcular métrica
        $result = self::calculateMultiTableMetric($data);

        if ($result === false) {
            return '<div class="metric-card-error">Erro ao calcular métrica multi-tabela</div>';
        }

        // Renderizar com os métodos corretos (esperam array $data e array $result)
        $layout = $data['layout'] ?? 'default';

        if ($layout === 'split') {
            return self::renderSplitLayout($data, $result);
        } else {
            return self::renderDefaultLayout($data, $result);
        }
    }

    /**
     * Calcular métrica de múltiplas tabelas
     */
    private static function calculateMultiTableMetric(array $data) {
        try {
            $db = DB::connect();

            // Extrair fontes
            $sources = self::extractMultiTableSources($data);
            $operation = strtoupper($data['operation']);

            // Pegar filtros da URL
            $dateStart = $_GET['date_start'] ?? '';
            $dateEnd = $_GET['date_end'] ?? '';

            // DEBUG
            error_log("=== MULTI-TABLE DEBUG ===");
            error_log("dateStart: " . ($dateStart ?: 'VAZIO'));
            error_log("dateEnd: " . ($dateEnd ?: 'VAZIO'));
            error_log("Sources count: " . count($sources));
            foreach ($sources as $i => $source) {
                error_log("Source $i: table={$source['table']}, column={$source['column']}, date_field=" . ($source['date_field'] ?? 'VAZIO'));
            }

            // Construir UNION ALL para todas as fontes
            $unionParts = [];
            $params = [];

            foreach ($sources as $source) {
                $table = $source['table'];
                $column = Security::sanitize($source['column']);
                $dateField = Security::sanitize($source['date_field'] ?? '');
                $whereConditions = [];

                // Filtro de data (usa o campo específico de cada fonte)
                if ($dateField && $dateStart) {
                    $whereConditions[] = "$dateField >= ?";
                    $params[] = $dateStart;
                }
                if ($dateField && $dateEnd) {
                    $whereConditions[] = "$dateField <= ?";
                    $params[] = $dateEnd;
                }

                $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                $unionParts[] = "SELECT $column as value FROM $table $whereClause";
            }

            // Montar SQL final com UNION ALL e operação agregada
            $unionSQL = implode(' UNION ALL ', $unionParts);
            $sql = "SELECT $operation(value) as total FROM ($unionSQL) combined";

            // DEBUG
            error_log("SQL: $sql");
            error_log("Params: " . json_encode($params));

            // Executar query
            try {
                $result = $db->query($sql, $params);
                $currentValue = $result[0]['total'] ?? 0;
                error_log("Result: $currentValue");
            } catch (Exception $e) {
                error_log("Multi-table query error: " . $e->getMessage());
                $currentValue = 0;
            }

            // Comparar com período anterior?
            $previousValue = null;
            $percentChange = null;

            if ($data['compare_period'] === 'yes' && $dateStart && $dateEnd) {
                $previousResult = self::calculateMultiTablePreviousPeriod(
                    $db,
                    $sources,
                    $operation,
                    $dateStart,
                    $dateEnd
                );

                if ($previousResult !== false && is_array($previousResult)) {
                    $previousValue = $previousResult['value'];

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

            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'percent_change' => $percentChange
            ];

        } catch (Exception $e) {
            error_log("MultiTableMetric Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular período anterior para múltiplas tabelas
     */
    private static function calculateMultiTablePreviousPeriod($db, $sources, $operation, $dateStart, $dateEnd) {
        try {
            // Calcular duração do período
            $start = new DateTime($dateStart);
            $end = new DateTime($dateEnd);
            $interval = $start->diff($end);
            $days = $interval->days;

            // Calcular período anterior
            $previousEnd = (clone $start)->modify('-1 day');
            $previousStart = (clone $previousEnd)->modify("-{$days} days");

            $previousStartStr = $previousStart->format('Y-m-d');
            $previousEndStr = $previousEnd->format('Y-m-d');

            // Construir UNION ALL para todas as fontes
            $unionParts = [];
            $params = [];

            foreach ($sources as $source) {
                $table = $source['table'];
                $column = Security::sanitize($source['column']);
                $dateField = Security::sanitize($source['date_field'] ?? '');
                $whereConditions = [];

                if ($dateField && $previousStartStr) {
                    $whereConditions[] = "$dateField >= ?";
                    $params[] = $previousStartStr;
                }
                if ($dateField && $previousEndStr) {
                    $whereConditions[] = "$dateField <= ?";
                    $params[] = $previousEndStr;
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
                'start_date' => $previousStartStr,
                'end_date' => $previousEndStr
            ];

        } catch (Exception $e) {
            error_log("MultiTable Previous Period Error: " . $e->getMessage());
            return false;
        }
    }
}
