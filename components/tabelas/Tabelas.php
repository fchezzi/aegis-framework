<?php
/**
 * AEGIS Framework - Tabelas Component
 *
 * Tabela responsiva e estilizada com recursos opcionais de ordenação, busca e paginação
 *
 * @package AEGIS
 * @version 1.0.0
 * @since 9.1.0
 */

class Tabelas {
    /**
     * Renderizar componente Tabelas
     *
     * @param array $data Dados de configuração
     * @return string HTML do componente
     */
    public static function render(array $data): string {
        // Valores padrão
        $defaults = [
            'filter_group' => 'default',
            'title' => '',
            'style' => 'default',
            'header_color' => 'primary',
            'columns' => '["Coluna 1", "Coluna 2", "Coluna 3"]',
            'rows' => '[["Dado 1.1", "Dado 1.2", "Dado 1.3"], ["Dado 2.1", "Dado 2.2", "Dado 2.3"]]',
            'sortable' => 'no',
            'searchable' => 'no',
            'pagination' => 'no',
            'rows_per_page' => 10,
            'data_source' => 'database',
            'data_source_url' => '',
            'database_table' => '',
            'database_columns' => '',
            'order_by' => '',
            'order_direction' => 'DESC',
            'limit' => 50
        ];

        // Merge com defaults
        $data = array_merge($defaults, $data);

        // Se fonte de dados for 'database' OU 'dynamic' OU 'database_condicional', buscar do banco
        // Usar ?: para pegar fallback mesmo com string vazia
        $dbTable = !empty($data['database_table']) ? $data['database_table'] : ($data['table'] ?? '');
        if (($data['data_source'] === 'database' || $data['data_source'] === 'dynamic' || $data['data_source'] === 'database_condicional') && !empty($dbTable)) {
            $tableConfig = $data;
            $tableConfig['database_table'] = $dbTable;
            $tableConfig['limit'] = $data['limit'] ?? 50;
            $databaseData = self::fetchFromDatabase($tableConfig);
            $columns = $databaseData['columns'];
            $rows = $databaseData['rows'];
        } else {
            // Decodificar JSON (modo static)
            $columns = json_decode($data['columns'], true);
            $rows = json_decode($data['rows'], true);
        }

        // Validar JSON
        if (!is_array($columns) || !is_array($rows)) {
            return '<div class="aegis-table-error">Erro: Dados de tabela inválidos (JSON malformado)</div>';
        }

        // Sanitizar título
        $title = !empty($data['title']) ? htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') : '';

        // ID único para a tabela (necessário para JS)
        $tableId = 'aegis-table-' . uniqid();

        // Classes CSS
        $styleClass = 'table-' . $data['style'];
        $headerColorClass = 'header-' . $data['header_color'];

        // Iniciar HTML
        $html = '<div class="aegis-table-wrapper">';

        // Título
        if (!empty($title)) {
            $html .= "<h3 class=\"table-title\">{$title}</h3>";
        }

        // Campo de busca
        if ($data['searchable'] === 'yes') {
            $html .= <<<HTML
<div class="table-search-wrapper">
    <i data-lucide="search" class="lucide-search"></i>
    <input type="text" class="table-search" id="{$tableId}-search" placeholder="Buscar...">
</div>
HTML;
        }

        // Data attributes para fonte de dados
        // Usar url() para garantir base path correta (adicionar / no início)
        $sourceUrl = !empty($data['data_source_url']) ? url('/' . ltrim($data['data_source_url'], '/')) : '';
        $dataSourceAttr = $data['data_source'] === 'dynamic' && !empty($sourceUrl)
            ? 'data-source="dynamic" data-source-url="' . htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') . '"'
            : 'data-source="static"';

        // Data attribute para grupo de filtro
        $filterGroup = htmlspecialchars($data['filter_group'], ENT_QUOTES, 'UTF-8');
        $filterGroupAttr = 'data-filter-group="' . $filterGroup . '"';

        // Data attributes para colunas de filtro (AMBOS database E dynamic)
        $filterValueCol = htmlspecialchars($data['filter_value_column'] ?? '', ENT_QUOTES, 'UTF-8');
        $filterDateCol = htmlspecialchars($data['filter_date_column'] ?? '', ENT_QUOTES, 'UTF-8');
        $filterConfigAttrs = 'data-filter-value-col="' . $filterValueCol . '" data-filter-date-col="' . $filterDateCol . '"';

        // Data attributes para configuração da tabela (modo dynamic)
        $tableConfigAttrs = '';
        if ($data['data_source'] === 'dynamic') {
            $table = htmlspecialchars($data['table'] ?? '', ENT_QUOTES, 'UTF-8');
            $valueField = htmlspecialchars($data['value_field'] ?? '', ENT_QUOTES, 'UTF-8');
            $dateField = htmlspecialchars($data['date_field'] ?? '', ENT_QUOTES, 'UTF-8');

            // Fallback para database_columns
            $dbCols = $data['database_columns'] ?? '';
            if (empty($dbCols) && !empty($data['columns'])) {
                $customCols = json_decode($data['columns'], true);
                if (is_array($customCols)) {
                    $dbCols = implode(',', $customCols);
                }
            }
            $columnsForApi = htmlspecialchars($dbCols, ENT_QUOTES, 'UTF-8');

            $tableConfigAttrs = 'data-table="' . $table . '" ';
            $tableConfigAttrs .= 'data-value-field="' . $valueField . '" ';
            $tableConfigAttrs .= 'data-date-field="' . $dateField . '" ';
            $tableConfigAttrs .= 'data-columns="' . $columnsForApi . '"';
        } elseif (($data['data_source'] === 'database' || $data['data_source'] === 'database_condicional') && !empty($data['database_columns'])) {
            // No modo database, armazenar os nomes das colunas do banco para filtros client-side
            // IMPORTANTE: Incluir também colunas escondidas (necessárias para filtros)
            $allColumns = array_map('trim', explode(',', $data['database_columns']));

            // Adicionar colunas escondidas
            $hiddenColumnsRaw = $data['hidden_columns'] ?? '';
            if (!empty($hiddenColumnsRaw)) {
                $hiddenColumns = array_map('trim', explode(',', $hiddenColumnsRaw));
                $hiddenColumns = array_filter($hiddenColumns);
                foreach ($hiddenColumns as $hiddenCol) {
                    if (!in_array($hiddenCol, $allColumns)) {
                        $allColumns[] = $hiddenCol;
                    }
                }
            }

            $dbColumns = htmlspecialchars(implode(',', $allColumns), ENT_QUOTES, 'UTF-8');
            $filterValueColumn = htmlspecialchars($data['filter_value_column'] ?? '', ENT_QUOTES, 'UTF-8');

            $tableConfigAttrs = 'data-db-columns="' . $dbColumns . '"';
            if (!empty($filterValueColumn)) {
                $tableConfigAttrs .= ' data-filter-value-column="' . $filterValueColumn . '"';
            }

            // Adicionar condição adicional se for database_condicional
            if ($data['data_source'] === 'database_condicional') {
                if (!empty($data['condition_column'])) {
                    $tableConfigAttrs .= ' data-condition-column="' . htmlspecialchars($data['condition_column'], ENT_QUOTES, 'UTF-8') . '"';
                }
                if (!empty($data['condition_operator'])) {
                    $tableConfigAttrs .= ' data-condition-operator="' . htmlspecialchars($data['condition_operator'], ENT_QUOTES, 'UTF-8') . '"';
                }
                if (!empty($data['condition_value'])) {
                    $tableConfigAttrs .= ' data-condition-value="' . htmlspecialchars($data['condition_value'], ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }

        // Tabela
        $html .= <<<HTML
<div class="table-container">
    <table class="aegis-table {$styleClass} {$headerColorClass}" id="{$tableId}" {$dataSourceAttr} {$filterGroupAttr} {$filterConfigAttrs} {$tableConfigAttrs}>
        <thead>
            <tr>
HTML;

        // Cabeçalhos
        // Colunas escondidas (para filtro interno, não exibir)
        $hiddenColumns = array_map('trim', explode(',', $data['hidden_columns'] ?? ''));
        $hiddenColumns = array_filter($hiddenColumns); // Remover vazios

        // Array de TODAS as colunas (incluindo escondidas) - para calcular índices corretamente
        $columnsArray = array_map('trim', explode(',', $data['database_columns'] ?? ''));
        foreach ($hiddenColumns as $hiddenCol) {
            if (!in_array($hiddenCol, $columnsArray)) {
                $columnsArray[] = $hiddenCol;
            }
        }

        // Garantir que filter_value_column está no array (necessário para calcular índice)
        if (!empty($data['filter_value_column']) && !in_array($data['filter_value_column'], $columnsArray)) {
            $columnsArray[] = $data['filter_value_column'];
        }

        foreach ($columns as $index => $column) {
            $columnSafe = htmlspecialchars($column, ENT_QUOTES, 'UTF-8');
            $sortable = $data['sortable'] === 'yes' ? 'sortable' : '';
            $dataIndex = "data-index=\"{$index}\"";

            // Verificar se esta coluna deve ser escondida (por NOME, não índice)
            $columnName = $columnsArray[$index] ?? '';
            $hiddenClass = in_array($columnName, $hiddenColumns) ? 'hidden-filter-column' : '';

            // Adicionar ícone Lucide se sortable
            $sortIcon = $data['sortable'] === 'yes'
                ? '<i data-lucide="arrow-up-down" class="sort-icon"></i>'
                : '';

            $html .= "<th class=\"{$sortable} {$hiddenClass}\" {$dataIndex}>{$columnSafe}{$sortIcon}</th>";
        }

        $html .= <<<HTML
            </tr>
        </thead>
        <tbody>
HTML;

        // Linhas
        // Guardar valores da coluna de filtro em data attribute
        $filterValueColumnIndex = !empty($data['filter_value_column'])
            ? array_search($data['filter_value_column'], $columnsArray)
            : -1;

        foreach ($rows as $rowIndex => $row) {
            $filterValue = ($filterValueColumnIndex >= 0 && isset($row[$filterValueColumnIndex]))
                ? htmlspecialchars($row[$filterValueColumnIndex], ENT_QUOTES, 'UTF-8')
                : '';

            $html .= '<tr data-filter-value="' . $filterValue . '">';

            foreach ($row as $cellIndex => $cell) {
                $cellSafe = htmlspecialchars($cell, ENT_QUOTES, 'UTF-8');

                // Verificar se esta coluna deve ser escondida (por NOME)
                $columnName = $columnsArray[$cellIndex] ?? '';
                $hiddenClass = in_array($columnName, $hiddenColumns) ? 'hidden-filter-column' : '';

                $html .= "<td class=\"{$hiddenClass}\">{$cellSafe}</td>";
            }
            $html .= '</tr>';
        }

        $html .= <<<HTML
        </tbody>
    </table>
</div>
HTML;

        // Paginação
        if ($data['pagination'] === 'yes') {
            $html .= <<<HTML
<div class="table-pagination" id="{$tableId}-pagination">
    <button class="page-btn prev" disabled>← Anterior</button>
    <span class="page-info">Página <span class="current-page">1</span> de <span class="total-pages">1</span></span>
    <button class="page-btn next">Próximo →</button>
</div>
HTML;
        }

        $html .= '</div>'; // Close wrapper

        // Incluir JavaScript de filtros (se tiver grupo)
        if (!empty($data['filter_group'])) {
            static $filtrosScriptAdded = false;
            if (!$filtrosScriptAdded) {
                $html .= '<script src="' . url('/assets/js/tabela-filtros.js') . '"></script>';
                $filtrosScriptAdded = true;
            }
        }

        // CSS para esconder colunas marcadas como hidden
        $html .= <<<CSS
<style>
#$tableId .hidden-filter-column {
    display: none !important;
}
#$tableId .search-hidden {
    display: none !important;
}
</style>
CSS;

        // CSS
        $html .= self::getStyles();

        // JavaScript (se features interativas ativadas OU se tiver filtros)
        $needsScript = (
            $data['sortable'] === 'yes' ||
            $data['searchable'] === 'yes' ||
            $data['pagination'] === 'yes' ||
            !empty($data['filter_group'])  // SEMPRE carregar se tiver grupo de filtro
        );

        if ($needsScript) {
            $scriptContent = self::getScript($tableId, $data);
            $html .= $scriptContent;
        }

        return $html;
    }

    /**
     * Obter estilos CSS
     * CSS agora é carregado via SASS externo em assets/sass/components/_c-tabelas.sass
     */
    private static function getStyles(): string {
        // CSS inline desabilitado - usando SASS externo
        // Estilos agora estão em: assets/sass/components/_c-tabelas.sass
        return '';
    }

    /**
     * Obter script JavaScript
     */
    private static function getScript(string $tableId, array $data): string {
        $rowsPerPage = (int) $data['rows_per_page'];
        $sortable = $data['sortable'] === 'yes' ? 'true' : 'false';
        $searchable = $data['searchable'] === 'yes' ? 'true' : 'false';
        $pagination = $data['pagination'] === 'yes' ? 'true' : 'false';

        $script = <<<'JSCODE'
<script>
(function() {
    const table = document.getElementById('TABLEID_PLACEHOLDER');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    let filteredRows = [...allRows];
    let currentPage = 1;
    const rowsPerPage = ROWSPERPAGE_PLACEHOLDER;
    const sortable = SORTABLE_PLACEHOLDER;
    const searchable = SEARCHABLE_PLACEHOLDER;
    const pagination = PAGINATION_PLACEHOLDER;

    // Ordenação
    if (sortable) {
        const headers = table.querySelectorAll('th.sortable');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                const isAsc = this.classList.contains('asc');

                // Remover classes e resetar ícones
                headers.forEach(h => {
                    h.classList.remove('asc', 'desc');
                    const icon = h.querySelector('.sort-icon');
                    if (icon) {
                        icon.setAttribute('data-lucide', 'arrow-up-down');
                    }
                });

                // Adicionar classe e ícone apropriado
                this.classList.add(isAsc ? 'desc' : 'asc');
                const currentIcon = this.querySelector('.sort-icon');
                if (currentIcon) {
                    currentIcon.setAttribute('data-lucide', isAsc ? 'arrow-down' : 'arrow-up');
                }

                // Re-inicializar Lucide icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                // Ordenar linhas
                filteredRows.sort((a, b) => {
                    const aVal = a.cells[index].textContent;
                    const bVal = b.cells[index].textContent;
                    const compare = aVal.localeCompare(bVal, undefined, {numeric: true});
                    return isAsc ? -compare : compare;
                });

                renderTable();
            });
        });
    }

    // Busca
    if (searchable) {
        const searchInput = document.getElementById('{$tableId}-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();

                // Trabalhar DIRETAMENTE nas linhas do tbody, não em allRows
                // Isso respeita o que o filtro externo já fez
                const currentRows = tbody.querySelectorAll('tr');

                currentRows.forEach(row => {
                    // Ignorar linha de "nenhum resultado"
                    if (row.classList.contains('table-no-results')) {
                        return;
                    }

                    if (query === '') {
                        // Sem busca: mostrar todas (que não estejam escondidas por filtro externo)
                        // Não mexer no style.display se já está 'none' (filtro externo)
                        if (row.style.display !== 'none') {
                            row.style.display = '';
                        }
                        row.classList.remove('search-hidden');
                    } else {
                        // Com busca: filtrar por texto
                        const text = row.textContent.toLowerCase();
                        if (text.includes(query)) {
                            // Encontrou: mostrar (se não estiver escondida por filtro externo)
                            if (row.style.display !== 'none') {
                                row.style.display = '';
                            }
                            row.classList.remove('search-hidden');
                        } else {
                            // Não encontrou: esconder com classe
                            row.classList.add('search-hidden');
                        }
                    }
                });
            });
        }
    }

    // Paginação
    if (pagination) {
        const paginationDiv = document.getElementById('{$tableId}-pagination');
        const prevBtn = paginationDiv.querySelector('.prev');
        const nextBtn = paginationDiv.querySelector('.next');
        const currentPageSpan = paginationDiv.querySelector('.current-page');
        const totalPagesSpan = paginationDiv.querySelector('.total-pages');

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });

        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });

        function updatePagination() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

            // Esconder paginação se só tem 1 página
            if (totalPages <= 1) {
                paginationDiv.style.display = 'none';
                return;
            }

            paginationDiv.style.display = 'flex';
            currentPageSpan.textContent = currentPage;
            totalPagesSpan.textContent = totalPages;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage >= totalPages;
        }
    }

    // Renderizar tabela
    function renderTable() {
        tbody.innerHTML = '';

        let rowsToShow = filteredRows;

        if (pagination) {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            rowsToShow = filteredRows.slice(start, end);
            updatePagination();
        }

        rowsToShow.forEach(row => tbody.appendChild(row));

        if (rowsToShow.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100" class="table-no-results">Nenhum resultado encontrado</td></tr>';
        }
    }

    // Renderização inicial
    if (pagination) {
        renderTable();
    }

    // ========================================
    // SUPORTE A FILTROS (DATABASE e DYNAMIC)
    // ========================================

    const dataSource = table.dataset.source;
    const dataSourceUrl = table.dataset.sourceUrl;
    const tableFilterGroup = table.dataset.filterGroup || 'default';

    // Escutar evento de filtros aplicados (AMBOS OS MODOS!)
    document.addEventListener('aegisFilterApplied', function(e) {
        const eventData = e.detail;

        // Verificar se o evento é para o mesmo grupo
        if (eventData.filterGroup !== tableFilterGroup) {
            return;
        }

        // Modo DYNAMIC: recarregar via AJAX
        if (dataSource === 'dynamic' && dataSourceUrl) {
            reloadTableData(eventData.filters);
        }
        // Modo DATABASE/STATIC: filtrar client-side
        else {
            filterClientSide(eventData.filters);
        }
    });

    // Função para recarregar dados via AJAX
    function reloadTableData(filters, config) {
        // Mostrar loading
        tbody.innerHTML = '<tr><td colspan="100" class="table-loading"><i data-lucide="loader-2" class="table-loading-icon"></i> Carregando...</td></tr>';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Construir URL com parâmetros
        const url = new URL(dataSourceUrl, window.location.origin);

        // Usar config do evento (prioridade) ou data attributes da tabela
        const tableConfig = config || {
            table: table.dataset.table || '',
            value_field: table.dataset.valueField || '',
            date_field: table.dataset.dateField || ''
        };

        const columnsData = table.dataset.columns || '';

        // Adicionar configuração da tabela (OBRIGATÓRIOS para API genérica)
        if (tableConfig.table) url.searchParams.append('table', tableConfig.table);
        if (columnsData) url.searchParams.append('columns', columnsData);
        if (tableConfig.value_field) url.searchParams.append('value_field', tableConfig.value_field);
        if (tableConfig.date_field) url.searchParams.append('date_field', tableConfig.date_field);

        // Adicionar filtros como query params
        if (filters.select) {
            if (Array.isArray(filters.select)) {
                filters.select.forEach(v => url.searchParams.append('select[]', v));
            } else if (filters.select !== '') {
                url.searchParams.append('select', filters.select);
            }
        }

        if (filters.date_start) url.searchParams.append('date_start', filters.date_start);
        if (filters.date_end) url.searchParams.append('date_end', filters.date_end);

        // Fazer fetch
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Erro ao carregar dados');
                return response.json();
            })
            .then(data => {
                // Limpar tbody
                tbody.innerHTML = '';

                // data deve ser array de arrays: [["val1", "val2"], ["val3", "val4"]]
                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="100" class="table-no-results">Nenhum resultado encontrado</td></tr>';
                    return;
                }

                // Criar novas linhas
                allRows.length = 0; // Limpar array
                data.forEach(rowData => {
                    const tr = document.createElement('tr');
                    rowData.forEach(cellData => {
                        const td = document.createElement('td');
                        td.textContent = cellData;
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                    allRows.push(tr);
                });

                // Resetar filteredRows
                filteredRows = [...allRows];
                currentPage = 1;

                // Re-renderizar com paginação
                if (pagination) {
                    renderTable();
                }
            })
            .catch(error => {
                tbody.innerHTML = '<tr><td colspan="100" class="table-error">Erro ao carregar dados</td></tr>';
            });
    }

    // Função para filtrar client-side (modo DATABASE/STATIC)
    function filterClientSide(filters) {

        // Obter nomes das colunas do banco (para mapear índices)
        const dbColumnsStr = table.dataset.dbColumns;
        if (!dbColumnsStr) {
            return;
        }

        const dbColumns = dbColumnsStr.split(',').map(c => c.trim());

        // Encontrar índices das colunas de filtro
        let valueFieldIndex = -1;
        let dateFieldIndex = -1;

        // FILTRO DROPDOWN: usar a coluna configurada no PageBuilder
        const filterValueCol = table.dataset.filterValueCol;
        if (filterValueCol) {
            valueFieldIndex = dbColumns.indexOf(filterValueCol.trim());
        }

        // FILTRO DATA: usar a coluna configurada no PageBuilder
        const filterDateCol = table.dataset.filterDateCol;
        if (filterDateCol) {
            dateFieldIndex = dbColumns.indexOf(filterDateCol.trim());
        }

        // Filtrar linhas
        filteredRows = allRows.filter(row => {
            // Filtro de valor (dropdown/select) - usar data-filter-value
            if (filters.select) {
                const rowFilterValue = row.dataset.filterValue || '';

                // DEBUG: mostrar primeira linha para comparar
                if (row === allRows[0]) {
                }

                if (rowFilterValue !== filters.select) {
                    return false;
                }
            }

            // Filtro de data início
            if (filters.date_start && dateFieldIndex >= 0) {
                const cellDate = row.cells[dateFieldIndex]?.textContent.trim();
                // Converter para formato comparável (YYYY-MM-DD)
                const dateParts = cellDate.split(' ')[0]; // Remover hora se houver
                if (dateParts < filters.date_start) {
                    return false;
                }
            }

            // Filtro de data fim
            if (filters.date_end && dateFieldIndex >= 0) {
                const cellDate = row.cells[dateFieldIndex]?.textContent.trim();
                const dateParts = cellDate.split(' ')[0];
                if (dateParts > filters.date_end) {
                    return false;
                }
            }

            return true; // Linha passou em todos os filtros
        });


        // Resetar para primeira página
        currentPage = 1;

        // Re-renderizar
        renderTable();
    }

    // ========================================
    // ESCUTAR EVENTO DE RELOAD EXTERNO (aegis-tables.js)
    // ========================================
    document.addEventListener('aegisTableReloaded', function(e) {
        const eventTableId = e.detail.tableId;

        // Verificar se o evento é para esta tabela
        if (eventTableId !== '{$tableId}') {
            return;
        }


        // Reconstruir allRows e filteredRows a partir do tbody atual
        const currentRows = Array.from(tbody.querySelectorAll('tr'));

        // Ignorar linha de "nenhum resultado"
        if (currentRows.length === 1 && currentRows[0].cells.length === 1 && currentRows[0].textContent.includes('Nenhum')) {
            allRows.length = 0;
            filteredRows = [];
            currentPage = 1;
            if (pagination) {
                updatePagination();
            }
            return;
        }

        // Atualizar arrays
        allRows.length = 0;
        currentRows.forEach(row => allRows.push(row));
        filteredRows = [...allRows];
        currentPage = 1;

        // Re-renderizar com paginação
        if (pagination) {
            renderTable();
        }
    });
})();
</script>
JSCODE;

        // Substituir placeholders
        $script = str_replace('TABLEID_PLACEHOLDER', $tableId, $script);
        $script = str_replace('ROWSPERPAGE_PLACEHOLDER', $rowsPerPage, $script);
        $script = str_replace('SORTABLE_PLACEHOLDER', $sortable, $script);
        $script = str_replace('SEARCHABLE_PLACEHOLDER', $searchable, $script);
        $script = str_replace('PAGINATION_PLACEHOLDER', $pagination, $script);
        $script = str_replace('{$tableId}', $tableId, $script);

        return $script;
    }

    /**
     * Buscar dados do banco de dados (modo database)
     */
    private static function fetchFromDatabase(array $config): array {
        $table = $config['database_table'];

        // Fallback: se database_columns vazio, tentar usar columns
        $columnsRaw = $config['database_columns'];
        if (empty($columnsRaw) && !empty($config['columns'])) {
            // columns pode ser JSON, tentar decodificar
            $customColumns = json_decode($config['columns'], true);
            if (is_array($customColumns)) {
                $columnsRaw = implode(',', $customColumns);
            }
        }
        $orderBy = $config['order_by'];
        $orderDirection = strtoupper($config['order_direction']) === 'ASC' ? 'ASC' : 'DESC';
        $limit = min((int)$config['limit'], 1000); // Máximo 1000

        // Processar colunas
        $columns = array_map('trim', explode(',', $columnsRaw));
        $columns = array_filter($columns); // Remover vazios

        // Adicionar colunas escondidas (necessárias para filtros)
        $hiddenColumnsRaw = $config['hidden_columns'] ?? '';
        if (!empty($hiddenColumnsRaw)) {
            $hiddenColumns = array_map('trim', explode(',', $hiddenColumnsRaw));
            $hiddenColumns = array_filter($hiddenColumns);
            // Adicionar ao array de colunas (evitando duplicatas)
            foreach ($hiddenColumns as $hiddenCol) {
                if (!in_array($hiddenCol, $columns)) {
                    $columns[] = $hiddenCol;
                }
            }
        }

        // Garantir que filter_value_column está incluído (necessário para filtro de canal)
        $filterValueColumn = $config['filter_value_column'] ?? '';
        if (!empty($filterValueColumn) && !in_array($filterValueColumn, $columns)) {
            $columns[] = $filterValueColumn;
        }

        if (empty($columns)) {
            return [
                'columns' => ['Erro'],
                'rows' => [['Nenhuma coluna selecionada']]
            ];
        }

        // Sanitizar nomes de colunas (apenas alfanuméricos e underscore)
        $columns = array_map(function($col) {
            return preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        }, $columns);

        // Montar query
        $selectFields = implode(', ', $columns);
        $query = "SELECT {$selectFields} FROM {$table}";

        // Condição adicional (para database_condicional)
        $params = [];
        if (!empty($config['condition_column']) && !empty($config['condition_value'])) {
            $conditionColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $config['condition_column']);
            $conditionOperator = $config['condition_operator'] ?? '=';
            $conditionValue = $config['condition_value'];

            // Validar operador
            $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
            if (!in_array($conditionOperator, $allowedOperators)) {
                $conditionOperator = '=';
            }

            $query .= " WHERE {$conditionColumn} {$conditionOperator} ?";
            $params[] = $conditionValue;
        }

        // Adicionar ordenação se especificada
        if (!empty($orderBy)) {
            $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
            $query .= " ORDER BY {$orderBy} {$orderDirection}";
        }

        // Adicionar limite
        $query .= " LIMIT {$limit}";

        try {
            $results = DB::query($query, $params);

            // Formatar dados para a tabela
            $rows = [];
            foreach ($results as $row) {
                $rowData = [];
                foreach ($columns as $col) {
                    $rowData[] = $row[$col] ?? '';
                }
                $rows[] = $rowData;
            }

            // Usar nomes customizados de colunas se fornecidos
            $columnNames = $columns;
            if (!empty($config['columns'])) {
                $customNames = json_decode($config['columns'], true);
                if (is_array($customNames) && count($customNames) === count($columns)) {
                    $columnNames = $customNames;
                }
            }

            return [
                'columns' => $columnNames,
                'rows' => $rows
            ];

        } catch (Exception $e) {
            error_log('Tabelas fetchFromDatabase error: ' . $e->getMessage());
            return [
                'columns' => ['Erro'],
                'rows' => [['Erro ao buscar dados: ' . $e->getMessage()]]
            ];
        }
    }
}
