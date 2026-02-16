<?php
/**
 * AEGIS Framework - Filtros Component
 *
 * Dropdown simples para filtrar dados
 *
 * MÚLTIPLOS GRUPOS:
 * O filtro de canal pode afetar múltiplos grupos simultaneamente.
 * Para isso, configure filter_group com grupos separados por vírgula.
 * Exemplo: filter_group = "grupo1,grupo2,grupo3"
 *
 * Caso de uso: Um filtro de canal único que afeta cards de diferentes seções,
 * cada uma com seu próprio filtro de data (mês/ano ou intervalo).
 *
 * @package AEGIS
 * @version 1.1.0
 * @since 9.1.0
 */

class Filtros {
    /**
     * Renderizar componente Filtros
     *
     * @param array $data Dados de configuração
     *   - filter_group (string): Nome do grupo ou múltiplos grupos separados por vírgula
     *   - filter_type (string): 'canal' ou 'data'
     *   - label (string): Label do filtro
     *   - platform (string): Plataforma para filtro de canal (youtube, instagram, etc)
     *   - show_presets (string): 'yes' ou 'no' para mostrar botões de preset no filtro de data
     *
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'filter_group' => 'default',
            'filter_group_2' => '',      // Grupo adicional opcional
            'filter_group_3' => '',      // Grupo adicional opcional
            'filter_type' => 'canal',    // 'canal' ou 'data'
            'label' => 'Filtro',
            'platform' => 'youtube',     // só para filter_type = 'canal'
            'show_presets' => 'yes'      // só para filter_type = 'data'
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Validações
        $filterType = $data['filter_type'] ?? '';

        // Se filter_type está vazio, não renderizar (ainda não foi configurado)
        if (empty($filterType)) {
            return '';
        }

        if (!in_array($filterType, ['canal', 'data'])) {
            return '<div class="aegis-filter-error">Erro: filter_type deve ser "canal" ou "data"</div>';
        }

        // Validar campos específicos de cada tipo
        if ($filterType === 'canal' && empty($data['platform'])) {
            return '<div class="aegis-filter-error">Erro: Campo "platform" é obrigatório para filter_type = "canal"</div>';
        }

        // ID único e grupos
        $filterId = 'aegis-filter-' . uniqid();

        // Coletar todos os grupos (principal + adicionais) - SEM htmlspecialchars para não quebrar a vírgula
        $filterGroups = [trim($data['filter_group'])];

        if (!empty($data['filter_group_2'])) {
            $filterGroups[] = trim($data['filter_group_2']);
        }
        if (!empty($data['filter_group_3'])) {
            $filterGroups[] = trim($data['filter_group_3']);
        }

        // Juntar todos os grupos com vírgula para o data-attribute
        $filterGroupsStr = implode(',', $filterGroups);

        // HTML
        $html = '<div class="aegis-filter-wrapper" id="' . $filterId . '" data-filter-group="' . $filterGroupsStr . '" data-filter-type="' . $filterType . '">';

        // Renderizar baseado no tipo
        if ($filterType === 'canal') {
            $html .= self::renderCanalFilter($data);
        } else if ($filterType === 'data') {
            $html .= self::renderDateFilter($data);
        }

        $html .= '</div>';

        // JavaScript
        $html .= self::renderJavaScript($filterId, $data);

        return $html;
    }

    /**
     * Renderizar JavaScript para funcionalidade completa
     */
    private static function renderJavaScript(string $filterId, array $data): string {
        // Não precisa mais de variáveis de configuração - cada componente envia seu próprio tipo
        return <<<JS
        <script>
        (function() {
            const wrapper = document.getElementById('{$filterId}');
            if (!wrapper) return;

            const filterGroup = wrapper.dataset.filterGroup || 'default';

            // Suporte a múltiplos grupos (SEMPRE separados por vírgula)
            const filterGroups = filterGroup.split(',').map(g => g.trim()).filter(g => g.length > 0);
            const primaryGroup = filterGroups[0];

            // Buscar inputs em TODOS os wrappers do grupo primário (não apenas neste)
            const allWrappers = document.querySelectorAll('[data-filter-group*="' + primaryGroup + '"]');
            let selectFilter = null;
            let dateStart = null;
            let dateEnd = null;
            let presetButtons = [];

            allWrappers.forEach(w => {
                const s = w.querySelector('select[name="filter_select"]');
                const ds = w.querySelector('input[name="filter_date_start"]');
                const de = w.querySelector('input[name="filter_date_end"]');
                const pb = w.querySelectorAll('.filter-preset-btn');

                if (s) selectFilter = s;
                if (ds) dateStart = ds;
                if (de) dateEnd = de;
                if (pb.length) presetButtons = Array.from(pb);
            });

            // Função para disparar evento de filtro
            function applyFilters() {
                const filters = {};

                // Canal (dropdown) - só adiciona se não for "todos"
                if (selectFilter && selectFilter.value && selectFilter.value !== 'todos') {
                    filters.select = selectFilter.value;
                }

                // Data (buscar nos campos locais OU preservar filtros existentes)
                if (dateStart && dateStart.value) {
                    filters.date_start = dateStart.value;
                }
                if (dateEnd && dateEnd.value) {
                    filters.date_end = dateEnd.value;
                }

                console.log('applyFilters() - Filtro:', filterGroup, '- Filtros:', filters);

                // Se não achou campos de data localmente, buscar filtros de mês/ano
                // APENAS se este grupo tiver filtro mês/ano associado
                if (!filters.date_start || !filters.date_end) {
                    // Para cada grupo, verificar se ELE tem filtro mês/ano
                    filterGroups.forEach(function(group) {
                        if (filters.date_start && filters.date_end) return;

                        // Buscar APENAS no grupo específico (não em outros grupos)
                        const monthSelect = document.querySelector('[data-filter-group="' + group + '"] [data-filter-type="month"]');
                        const yearSelect = document.querySelector('[data-filter-group="' + group + '"] [data-filter-type="year"]');

                        if (monthSelect && yearSelect && monthSelect.value && yearSelect.value) {
                            const month = monthSelect.value;
                            const year = yearSelect.value;

                            filters.date_start = year + '-' + month + '-01';
                            const lastDay = new Date(year, month, 0).getDate();
                            filters.date_end = year + '-' + month + '-' + String(lastDay).padStart(2, '0');
                            filters.month = month;
                            filters.year = year;
                        }
                    });
                }

                // Disparar evento customizado para cada grupo
                filterGroups.forEach(function(group) {
                    const event = new CustomEvent('aegisFilterApplied', {
                        detail: {
                            filterGroup: group,
                            filters: filters
                        }
                    });
                    document.dispatchEvent(event);
                });
            }

            // Presets de data
            if (presetButtons.length && dateStart && dateEnd) {
                presetButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Verificar se é botão de limpar
                        if (this.dataset.action === 'clear') {
                            // Limpar campos de data
                            dateStart.value = '';
                            dateEnd.value = '';

                            // Remover classe active de todos os botões
                            presetButtons.forEach(b => b.classList.remove('active'));

                            // Aplicar filtros (sem data)
                            applyFilters();
                            return;
                        }

                        let startDate, endDate;

                        // Verificar se é preset de período (mês ou ano)
                        if (this.dataset.period) {
                            const today = new Date();
                            endDate = new Date(); // Hoje
                            endDate.setHours(0, 0, 0, 0);

                            if (this.dataset.period === 'month') {
                                // Este mês: 01/MESATUAL/ANOATUAL até hoje
                                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                            } else if (this.dataset.period === 'year') {
                                // Este ano: 01/01/ANOATUAL até hoje
                                startDate = new Date(today.getFullYear(), 0, 1);
                            }
                        } else {
                            // Últimos X dias
                            const days = parseInt(this.dataset.days);
                            endDate = new Date();
                            endDate.setHours(0, 0, 0, 0);
                            startDate = new Date();
                            startDate.setDate(startDate.getDate() - days);
                            startDate.setHours(0, 0, 0, 0);
                        }

                        // Formatar para YYYY-MM-DD
                        dateStart.value = startDate.toISOString().split('T')[0];
                        dateEnd.value = endDate.toISOString().split('T')[0];

                        // Destacar botão clicado com classe .active
                        presetButtons.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');

                        // Aplicar filtros
                        applyFilters();
                    });
                });
            }

            // Dropdown muda
            if (selectFilter) {
                selectFilter.addEventListener('change', applyFilters);
            }

            // Data muda
            if (dateStart) {
                dateStart.addEventListener('change', applyFilters);
            }
            if (dateEnd) {
                dateEnd.addEventListener('change', applyFilters);
            }

            // AUTO-APLICAR "Últimos 7 dias" no carregamento inicial
            if (presetButtons.length > 0) {
                const preset7dias = Array.from(presetButtons).find(btn => btn.dataset.days === '7');
                if (preset7dias) {
                    // Pequeno delay para garantir que tudo está carregado
                    setTimeout(() => {
                        preset7dias.click();
                    }, 100);
                }
            }
        })();
        </script>
        JS;
    }

    /**
     * Obter label automático baseado na plataforma/tabela
     */
    private static function getAutoLabel(string $platform): string {
        $labels = [
            'youtube' => 'Canal',
            'instagram' => 'Conta Instagram',
            'tiktok' => 'Conta TikTok',
            'website' => 'Website',
            'site' => 'Website',
            'web' => 'Website'
        ];

        return $labels[$platform] ?? 'Canal';
    }

    /**
     * Renderizar filtro de canal (dropdown com plataforma)
     */
    private static function renderCanalFilter(array $data): string {
        $platform = strtolower($data['platform']);

        // Se label não foi definido, usar label automático baseado na plataforma
        if (empty($data['label']) || $data['label'] === 'Filtro') {
            $label = self::getAutoLabel($platform);
        } else {
            $label = $data['label'];
        }

        $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        $html = '<div class="filter-group filter-select-group">';
        $html .= '<label class="filter-label">' . $label . '</label>';
        $html .= '<select class="filter-select" name="filter_select">';
        $html .= '<option value="todos">Todos</option>';

        // Buscar canais da plataforma
        try {
            $db = DB::connect();
            $canais = $db->query(
                "SELECT id, nome FROM canais WHERE plataforma = ? ORDER BY nome",
                [$platform]
            );

            foreach ($canais as $canal) {
                $value = htmlspecialchars($canal['id'], ENT_QUOTES, 'UTF-8');
                $nome = htmlspecialchars($canal['nome'], ENT_QUOTES, 'UTF-8');
                $html .= "<option value=\"{$value}\">{$nome}</option>";
            }
        } catch (Exception $e) {
            $html .= '<option value="">Erro ao carregar canais</option>';
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderizar filtro dropdown (LEGACY - manter para compatibilidade)
     */
    private static function renderSelectFilter(array $data): string {
        $label = htmlspecialchars($data['select_label'], ENT_QUOTES, 'UTF-8');

        $html = '<div class="filter-group filter-select-group">';
        $html .= '<label class="filter-label">' . $label . '</label>';
        $html .= '<select class="filter-select" name="filter_select">';
        $html .= '<option value="todos">Todos</option>';

        // Buscar opções do banco
        try {
            $options = self::fetchOptions($data);

            foreach ($options as $option) {
                $value = htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
                $html .= "<option value=\"{$value}\">{$label}</option>";
            }
        } catch (Exception $e) {
            $html .= '<option value="">Erro ao carregar</option>';
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderizar filtro de data
     */
    private static function renderDateFilter(array $data): string {
        $label = htmlspecialchars($data['label'] ?? 'Período', ENT_QUOTES, 'UTF-8');
        $showPresets = ($data['show_presets'] === 'yes');

        $html = '<div class="filter-group filter-date-group">';
        $html .= '<label class="filter-label">' . $label . '</label>';

        // Presets (atalhos)
        if ($showPresets) {
            $html .= '<div class="filter-date-presets">';
            $html .= '<button type="button" class="filter-preset-btn filter-clear-btn" data-action="clear">Limpar datas</button>';
            $html .= '<button type="button" class="filter-preset-btn" data-days="7">Últimos 7 dias</button>';
            $html .= '<button type="button" class="filter-preset-btn" data-days="30">Últimos 30 dias</button>';
            $html .= '<button type="button" class="filter-preset-btn" data-days="90">Últimos 90 dias</button>';
            $html .= '<button type="button" class="filter-preset-btn" data-period="month">Este mês</button>';
            $html .= '<button type="button" class="filter-preset-btn" data-period="year">Este ano</button>';
            $html .= '</div>';
        }

        // Campos de data
        $html .= '<div class="filter-date-inputs">';
        $html .= '<div class="filter-date-field">';
        $html .= '<label class="filter-date-label">De</label>';
        $html .= '<input type="date" class="filter-date-input" name="filter_date_start" />';
        $html .= '</div>';
        $html .= '<div class="filter-date-field">';
        $html .= '<label class="filter-date-label">Até</label>';
        $html .= '<input type="date" class="filter-date-input" name="filter_date_end" />';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Buscar opções do banco
     */
    private static function fetchOptions(array $data): array {
        $table = $data['table'];
        $valueField = $data['value_field'];
        $labelField = $data['label_field'];
        $platformFilter = $data['platform_filter'];

        // Query base
        $query = "SELECT {$valueField} as value, {$labelField} as label
                  FROM {$table}";

        $params = [];

        // Se filtro de plataforma estiver ativo e não for "all"
        if ($platformFilter && $platformFilter !== 'all') {
            // Verificar se a tabela tem coluna 'plataforma'
            $columns = DB::query("SHOW COLUMNS FROM {$table} LIKE 'plataforma'");

            if (!empty($columns)) {
                $query .= " WHERE plataforma = ?";
                $params[] = $platformFilter;
            }
        }

        $query .= " ORDER BY {$labelField}";

        // Executar com ou sem parâmetros
        return DB::query($query, $params);
    }
}
