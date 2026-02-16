/**
 * AEGIS Tables - Conecta Filtros → API → Tabelas
 *
 * Este arquivo faz a ponte entre os componentes do Page Builder:
 * 1. Escuta eventos de filtros (aegisFilterApplied)
 * 2. Busca dados na API (/api/table-data.php)
 * 3. Popula tabelas dinâmicas com os dados
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // CARREGAR TABELAS DINÂMICAS AUTOMATICAMENTE
    // ============================================

    function loadTable(table, filters = {}) {
        const sourceUrl = table.dataset.sourceUrl;
        const tableName = table.dataset.table;
        const columns = table.dataset.columns;
        const valueField = table.dataset.valueField;
        const dateField = table.dataset.dateField;

        // Validar dados obrigatórios
        if (!sourceUrl || !tableName || !columns) {
            console.error('Tabela sem configuração completa:', table.id);
            return;
        }

        // Construir parâmetros da API
        const params = new URLSearchParams({
            table: tableName,
            columns: columns
        });

        // Adicionar campo de filtro se existir
        if (valueField) {
            params.set('value_field', valueField);
        }
        if (dateField) {
            params.set('date_field', dateField);
        }

        // Adicionar filtros recebidos
        if (filters.select) {
            params.set('select', filters.select);
        }
        if (filters.date_start) {
            params.set('date_start', filters.date_start);
        }
        if (filters.date_end) {
            params.set('date_end', filters.date_end);
        }

        // Mostrar loading
        const tbody = table.querySelector('tbody');
        const thead = table.querySelector('thead');
        const numCols = thead ? thead.querySelectorAll('th').length : 1;
        tbody.innerHTML = `<tr><td colspan="${numCols}" style="text-align: center; padding: 20px;">Carregando...</td></tr>`;

        // Fazer fetch
        fetch(sourceUrl + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';

                // Verificar se retornou dados
                if (!data || data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="${numCols}" style="text-align: center; padding: 20px; color: #666;">Nenhum registro encontrado</td></tr>`;

                    // Disparar evento mesmo sem dados (para resetar paginação)
                    const reloadEvent = new CustomEvent('aegisTableReloaded', {
                        detail: { tableId: table.id, rowCount: 0 }
                    });
                    document.dispatchEvent(reloadEvent);
                    return;
                }

                // Renderizar linhas
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    row.forEach(cellValue => {
                        const td = document.createElement('td');
                        td.textContent = cellValue || '';
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });

                console.log('✅ Tabela carregada:', table.id, '(' + data.length + ' registros)');

                // 🔄 DISPARAR EVENTO para avisar o componente de paginação que os dados mudaram
                const reloadEvent = new CustomEvent('aegisTableReloaded', {
                    detail: {
                        tableId: table.id,
                        rowCount: data.length
                    }
                });
                document.dispatchEvent(reloadEvent);
            })
            .catch(error => {
                console.error('Erro ao carregar tabela:', error);
                tbody.innerHTML = `<tr><td colspan="${numCols}" style="text-align: center; padding: 20px; color: red;">Erro ao carregar dados: ${error.message}</td></tr>`;
            });
    }

    // ============================================
    // CARREGAR TODAS AS TABELAS DINÂMICAS NA INICIALIZAÇÃO
    // ============================================

    const dynamicTables = document.querySelectorAll('table[data-source="dynamic"]');
    dynamicTables.forEach(table => {
        console.log('🔄 Carregando tabela inicial:', table.id);
        loadTable(table);
    });

    // ============================================
    // ESCUTAR EVENTOS DE FILTRO
    // ============================================
    // ⚠️ DESABILITADO: O componente Tabelas.php já gerencia filtros
    // Este arquivo só carrega dados iniciais. O reload por filtro é feito pelo componente.

    // document.addEventListener('aegisFilterApplied', function(event) {
    //     console.log('🔍 Evento de filtro recebido:', event.detail);
    //
    //     const filterGroup = event.detail.filterGroup;
    //     const filters = event.detail.filters;
    //
    //     // Encontrar tabelas do mesmo grupo
    //     const tables = document.querySelectorAll(`table[data-filter-group="${filterGroup}"][data-source="dynamic"]`);
    //
    //     if (tables.length === 0) {
    //         console.warn('Nenhuma tabela encontrada para o grupo:', filterGroup);
    //         return;
    //     }
    //
    //     // Recarregar cada tabela com os novos filtros
    //     tables.forEach(table => {
    //         console.log('🔄 Recarregando tabela:', table.id, 'com filtros:', filters);
    //         loadTable(table, filters);
    //     });
    // });

    console.log('✅ AEGIS Tables inicializado');
});
