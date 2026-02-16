/**
 * Sistema de Filtros para Tabelas
 */

// Cache de filtros aplicados (key: tableId, value: {filters, timestamp})
const filterCache = new Map();

// Cache expira após 5 minutos
const CACHE_DURATION = 5 * 60 * 1000;

document.addEventListener('DOMContentLoaded', function() {
    // Encontrar TODAS as tabelas com filtro
    const tabelas = document.querySelectorAll('.aegis-table[data-filter-group]');

    tabelas.forEach(table => {
        const tableId = table.id;
        const filterGroup = table.dataset.filterGroup;
        const dataSource = table.dataset.source || 'static';
        const filterValueCol = table.dataset.filterValueCol;
        const filterDateCol = table.dataset.filterDateCol;

        // Suporte a múltiplos grupos separados por vírgula
        const filterGroups = filterGroup.split(',').map(g => g.trim()).filter(g => g.length > 0);

        // Escutar filtros
        document.addEventListener('aegisFilterApplied', function(e) {
            const eventData = e.detail;

            // Verificar se o evento é para algum dos grupos desta tabela
            if (!filterGroups.includes(eventData.filterGroup)) {
                return;
            }

            // Verificar cache
            const cacheKey = tableId + '_' + JSON.stringify(eventData.filters);
            const cached = filterCache.get(cacheKey);

            if (cached && (Date.now() - cached.timestamp < CACHE_DURATION)) {
                aplicarResultadoCache(table, cached.result);
                return;
            }

            // Aplicar filtro nas linhas
            aplicarFiltro(table, eventData.filters, filterValueCol, filterDateCol, cacheKey);
        });
    });
});

function aplicarFiltro(table, filters, filterValueCol, filterDateCol, cacheKey) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const dbColumns = table.dataset.dbColumns ? table.dataset.dbColumns.split(',') : [];

    // Mostrar loading indicator
    showLoading(table);

    // Usar setTimeout para não travar UI em tabelas grandes
    setTimeout(() => {
        let linhasVisiveis = 0;
        let linhasEscondidas = 0;
        const resultadoCache = []; // Para salvar qual linha mostrar/esconder

        // Normalizar e validar datas
        const dateStartNormalized = filters.date_start ? normalizeDate(filters.date_start) : null;
        const dateEndNormalized = filters.date_end ? normalizeDate(filters.date_end) : null;

        rows.forEach((row, rowIndex) => {
            let mostrar = true;
            const cells = row.querySelectorAll('td');

            // Filtro de canal (select)
            if (filters.select && filterValueCol) {
                const valorCelula = row.dataset.filterValue;
                if (valorCelula && valorCelula !== filters.select) {
                    mostrar = false;
                }
            }

            // Filtro de data
            if (mostrar && (dateStartNormalized || dateEndNormalized) && filterDateCol) {
                const colIndex = dbColumns.indexOf(filterDateCol);

                if (colIndex >= 0 && cells[colIndex]) {
                    const valorCelula = cells[colIndex].textContent.trim();
                    const dataCelulaNormalized = normalizeDate(valorCelula);

                    if (dataCelulaNormalized) {
                        if (dateStartNormalized && dataCelulaNormalized < dateStartNormalized) {
                            mostrar = false;
                        }
                        if (dateEndNormalized && dataCelulaNormalized > dateEndNormalized) {
                            mostrar = false;
                        }
                    }
                }
            }

            // Salvar resultado no cache
            resultadoCache.push(mostrar);

            // Mostrar/esconder linha
            row.style.display = mostrar ? '' : 'none';

            if (mostrar) {
                linhasVisiveis++;
            } else {
                linhasEscondidas++;
            }
        });

        // Salvar no cache
        if (cacheKey) {
            filterCache.set(cacheKey, {
                result: resultadoCache,
                timestamp: Date.now()
            });
        }

        // Remover loading
        hideLoading(table);

        // Mostrar mensagem se não houver resultados
        if (linhasVisiveis === 0) {
            showEmptyMessage(table);
        } else {
            hideEmptyMessage(table);
        }
    }, 10);
}

/**
 * Aplicar resultado do cache
 */
function aplicarResultadoCache(table, result) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    let linhasVisiveis = 0;

    rows.forEach((row, index) => {
        const mostrar = result[index];
        row.style.display = mostrar ? '' : 'none';
        if (mostrar) linhasVisiveis++;
    });

    // Mostrar/esconder mensagem de vazio
    if (linhasVisiveis === 0) {
        showEmptyMessage(table);
    } else {
        hideEmptyMessage(table);
    }
}

/**
 * Normalizar data para formato YYYY-MM-DD
 * Aceita: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY
 */
function normalizeDate(dateStr) {
    if (!dateStr) return null;

    const str = dateStr.trim();

    // Já está em formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        return str;
    }

    // Formato DD/MM/YYYY ou DD-MM-YYYY
    if (/^\d{2}[/-]\d{2}[/-]\d{4}$/.test(str)) {
        const parts = str.split(/[/-]/);
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    // Tentar parsear como Date
    const date = new Date(str);
    if (!isNaN(date.getTime())) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    return null;
}

/**
 * Mostrar loading indicator
 */
function showLoading(table) {
    let loader = table.parentElement.querySelector('.aegis-table-loading');
    if (!loader) {
        loader = document.createElement('div');
        loader.className = 'aegis-table-loading';
        loader.innerHTML = '<div class="spinner"></div><span>Filtrando...</span>';
        table.parentElement.style.position = 'relative';
        table.parentElement.appendChild(loader);
    }
    loader.style.display = 'flex';
}

/**
 * Esconder loading indicator
 */
function hideLoading(table) {
    const loader = table.parentElement.querySelector('.aegis-table-loading');
    if (loader) {
        loader.style.display = 'none';
    }
}

/**
 * Mostrar mensagem de nenhum resultado
 */
function showEmptyMessage(table) {
    let message = table.parentElement.querySelector('.aegis-table-empty');
    if (!message) {
        message = document.createElement('div');
        message.className = 'aegis-table-empty';
        message.innerHTML = '<p>📭 Nenhum resultado encontrado para os filtros selecionados</p>';
        table.parentElement.appendChild(message);
    }
    message.style.display = 'block';
}

/**
 * Esconder mensagem de nenhum resultado
 */
function hideEmptyMessage(table) {
    const message = table.parentElement.querySelector('.aegis-table-empty');
    if (message) {
        message.style.display = 'none';
    }
}
