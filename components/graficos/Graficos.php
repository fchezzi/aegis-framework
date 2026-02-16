<?php
/**
 * AEGIS Framework - Componente de Gráficos
 *
 * Renderiza gráficos usando ApexCharts com dados dinâmicos
 *
 * @package AEGIS
 * @version 1.0.0
 */

class Graficos {

    private static $allowedTables = [
        'tbl_youtube',
        'tbl_insta',
        'tbl_tiktok',
        'tbl_website',
        'tbl_facebook',
        'youtube_extra'
    ];

    /**
     * Renderizar gráfico
     *
     * @param array $data Configurações do gráfico
     * @return string HTML do gráfico
     */
    public static function render(array $data): string {
        // Validar dados obrigatórios
        $requiredFields = ['chart_id', 'chart_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return '<div class="error">Erro: Campo obrigatório ausente: ' . $field . '</div>';
            }
        }

        // Configurações
        $userChartId = Security::sanitize($data['chart_id']);

        // Gerar ID único baseado no ID do usuário + timestamp + random
        // Isso garante que NUNCA haverá duplicatas, mesmo com cache
        $chartId = $userChartId . '-' . substr(md5(microtime() . rand()), 0, 8);

        $chartType = $data['chart_type']; // line, area, bar, donut, pie, radialBar
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $height = $data['height'] ?? '350';

        // Dados dinâmicos
        $table = $data['table'] ?? '';
        $columns = $data['columns'] ?? ''; // Ex: "visualizacoes,seguidores"
        $dateField = $data['date_field'] ?? 'data';
        $groupBy = $data['group_by'] ?? 'day'; // day, week, month, year
        $filterGroup = $data['filter_group'] ?? '';
        $filterValueField = $data['filter_value_field'] ?? ''; // Ex: canal_id
        $comparePeriod = $data['compare_period'] ?? 'no';

        // Validar tabela
        if ($table && !in_array($table, self::$allowedTables)) {
            return '<div class="error">Erro: Tabela não autorizada</div>';
        }

        // Cores (separadas por vírgula) - cores mais vibrantes e saturadas
        // IMPORTANTE: Todos os gráficos usam a mesma sequência de cores
        $colorsStr = $data['colors'] ?? '#008FFB,#00E396,#FEB019,#FF4560,#775DD0,#3F51B5,#03A9F4,#4CAF50';
        $colors = array_map('trim', explode(',', $colorsStr));

        // Incluir biblioteca ApexCharts (apenas uma vez)
        static $scriptAdded = false;
        $apexScript = '';
        if (!$scriptAdded) {
            $apexScript = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';
            $scriptAdded = true;
        }

        // Incluir script de integração (apenas uma vez)
        static $integrationScriptAdded = false;
        $integrationScript = '';
        if (!$integrationScriptAdded) {
            $integrationScript = '<script src="' . url('/assets/js/graficos-filtros.js') . '"></script>';
            $integrationScriptAdded = true;
        }

        // HTML Container com data attributes
        $dataAttrs = self::generateDataAttributes($data, $chartId);

        $html = $apexScript . $integrationScript;
        $html .= '<div class="chart-card" ' . $dataAttrs . '>';

        if ($title || $subtitle) {
            $html .= '<div class="chart-header">';
            if ($title) {
                $html .= '<div class="chart-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            if ($subtitle) {
                $html .= '<div class="chart-subtitle">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div id="' . $chartId . '" class="chart-container"></div>';
        $html .= '</div>';

        // Renderizar configuração JavaScript
        $html .= self::renderChartConfig($chartId, $chartType, $height, $colors, $data);

        return $html;
    }

    /**
     * Gerar atributos data-* para integração com filtros
     */
    private static function generateDataAttributes(array $data, string $uniqueChartId): string {
        $table = $data['table'] ?? '';
        $columns = $data['columns'] ?? '';
        $dateField = $data['date_field'] ?? 'data';
        $groupBy = $data['group_by'] ?? 'day';
        $filterGroup = $data['filter_group'] ?? '';
        $filterValueField = $data['filter_value_field'] ?? '';
        $comparePeriod = $data['compare_period'] ?? 'no';

        $attributes = [
            'data-chart-id="' . $uniqueChartId . '"',
            'data-api-url="' . htmlspecialchars(url('/api/chart-data.php'), ENT_QUOTES, 'UTF-8') . '"',
            'data-table="' . htmlspecialchars($table, ENT_QUOTES, 'UTF-8') . '"',
            'data-columns="' . htmlspecialchars($columns, ENT_QUOTES, 'UTF-8') . '"',
            'data-date-field="' . htmlspecialchars($dateField, ENT_QUOTES, 'UTF-8') . '"',
            'data-group-by="' . htmlspecialchars($groupBy, ENT_QUOTES, 'UTF-8') . '"',
            'data-filter-group="' . htmlspecialchars($filterGroup, ENT_QUOTES, 'UTF-8') . '"',
            'data-filter-value-field="' . htmlspecialchars($filterValueField, ENT_QUOTES, 'UTF-8') . '"',
            'data-compare-period="' . htmlspecialchars($comparePeriod, ENT_QUOTES, 'UTF-8') . '"'
        ];

        return implode(' ', $attributes);
    }

    /**
     * Renderizar configuração do gráfico
     */
    private static function renderChartConfig(string $chartId, string $chartType, string $height, array $colors, array $data): string {
        $colorsJson = json_encode($colors);
        $curve = $data['curve'] ?? 'smooth';
        $showLabels = ($data['show_labels'] ?? 'no') === 'yes';

        $options = [
            'chart' => [
                'type' => $chartType,
                'height' => (int)$height,
                'fontFamily' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'toolbar' => ['show' => true],
                'zoom' => ['enabled' => true]
            ],
            'colors' => $colors,
            'dataLabels' => ['enabled' => $showLabels],
            'stroke' => [
                'curve' => $curve,
                'width' => 3
            ],
            'grid' => [
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 5
            ],
            'xaxis' => [
                'categories' => [],
                'labels' => [
                    'style' => [
                        'fontSize' => '12px',
                        'fontWeight' => 500,
                        'colors' => '#718096'
                    ]
                ]
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontSize' => '12px',
                        'fontWeight' => 500,
                        'colors' => '#718096'
                    ]
                ]
            ],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '13px',
                'fontWeight' => 500
            ]
        ];

        // Configurações específicas por tipo
        if (in_array($chartType, ['area', 'line'])) {
            $options['fill'] = [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'opacityFrom' => 0.8,
                    'opacityTo' => 0.3,
                    'stops' => [0, 100]
                ]
            ];
        }

        if ($chartType === 'bar') {
            $options['plotOptions'] = [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '55%',
                    'borderRadius' => 8
                ]
            ];
        }

        if (in_array($chartType, ['pie', 'donut'])) {
            if ($chartType === 'donut') {
                $options['plotOptions'] = [
                    'pie' => [
                        'donut' => [
                            'size' => '65%'
                        ]
                    ]
                ];
            }
            // Pie e Donut não usam xaxis
            unset($options['xaxis']);
            unset($options['grid']);
        }

        $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);

        return <<<JS
        <script>
        (function() {
            // Configuração base do gráfico
            window.chartConfigs = window.chartConfigs || {};
            window.chartConfigs['$chartId'] = $optionsJson;

            // Aguardar DOM estar pronto antes de inicializar
            function initChart() {
                const element = document.querySelector("#$chartId");
                if (!element) {
                    if (typeof logDebug === 'function') {
                        logDebug('❌ Elemento #$chartId não encontrado', 'error');
                    }
                    return;
                }

                // Verificar se já foi inicializado
                window.charts = window.charts || {};
                if (window.charts['$chartId']) {
                    if (typeof logDebug === 'function') {
                        logDebug('⚠️ $chartId já inicializado', 'warning');
                    }
                    return;
                }

                // Inicializar com dados vazios (será preenchido pelo filtro ou carregamento inicial)
                const options = {...window.chartConfigs['$chartId']};
                options.series = [];

                window.charts['$chartId'] = new ApexCharts(element, options);
                window.charts['$chartId'].render();

                if (typeof logDebug === 'function') {
                    logDebug('✨ $chartId renderizado', 'success');
                }
            }

            // Executar quando DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
                initChart();
            }
        })();
        </script>
JS;
    }
}
