/**
 * AEGIS Framework - Integração de Gráficos com Filtros
 *
 * Sistema que escuta eventos de filtro e atualiza gráficos dinamicamente
 */

// Criar painel de debug visual
function criarPainelDebug() {
    if (document.getElementById('aegis-debug-panel')) return;

    const panel = document.createElement('div');
    panel.id = 'aegis-debug-panel';
    panel.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        width: 450px;
        max-height: 80vh;
        overflow-y: auto;
        background: #1a202c;
        color: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        font-family: 'Courier New', monospace;
        font-size: 13px;
        z-index: 99999;
        line-height: 1.6;
    `;

    const btnCopiar = `
        <button onclick="copiarLogs()" style="
            background: #48bb78;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 10px;
            width: 100%;
        ">COPIAR TODOS OS LOGS</button>
    `;

    panel.innerHTML = `
        <div style="font-weight:bold; margin-bottom:15px; color:#48bb78; font-size: 16px;">📊 Debug Gráficos AEGIS</div>
        ${btnCopiar}
        <div id="aegis-debug-log"></div>
    `;
    document.body.appendChild(panel);
}

// Função global para copiar logs
window.copiarLogs = function() {
    const log = document.getElementById('aegis-debug-log');
    const textos = [];

    for (let i = log.children.length - 1; i >= 0; i--) {
        textos.push(log.children[i].textContent);
    }

    const textoCompleto = textos.join('\n');

    // Copiar para clipboard
    navigator.clipboard.writeText(textoCompleto).then(function() {
        alert('✅ Logs copiados! Cole aqui no chat para eu analisar.');
    }).catch(function() {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = textoCompleto;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('✅ Logs copiados! Cole aqui no chat para eu analisar.');
    });
};

function logDebug(mensagem, tipo = 'info') {
    // Debug desabilitado
    // console.log(mensagem);
}

// Garantir que o sistema de filtros está inicializado
window.aegisChartsSystem = window.aegisChartsSystem || {
    initialized: false,
    chartsToInit: []
};

// Criar painel de debug (desabilitado)
// criarPainelDebug();

// Inicializar sistema de filtros (apenas uma vez)
if (!window.aegisChartsSystem.initialized) {
    window.aegisChartsSystem.initialized = true;

    // Escutar evento de filtro aplicado
    document.addEventListener('aegisFilterApplied', function(e) {
        const eventData = e.detail;

        // Ignorar eventos sem filterGroup válido
        if (!eventData.filterGroup || eventData.filterGroup === 'chart-1') {
            logDebug('⚠️ Evento de filtro ignorado (grupo inválido)', 'warning');
            return;
        }

        const charts = document.querySelectorAll('.chart-card[data-filter-group="' + eventData.filterGroup + '"]');

        logDebug('🔄 Filtro aplicado no grupo: ' + eventData.filterGroup + ' (' + charts.length + ' gráficos)', 'info');

        charts.forEach(function(chartCard) {
            atualizarGrafico(chartCard, eventData.filters);
        });
    });

    // Quando DOM estiver pronto, inicializar todos os gráficos pendentes
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processarGraficosPendentes);
    } else {
        setTimeout(processarGraficosPendentes, 100);
    }
}

/**
 * Registrar um gráfico para inicialização
 */
function registrarGrafico(chartId) {
    if (!window.aegisChartsSystem.chartsToInit.includes(chartId)) {
        window.aegisChartsSystem.chartsToInit.push(chartId);
    }
}

/**
 * Processar todos os gráficos que foram registrados
 */
function processarGraficosPendentes() {
    const charts = document.querySelectorAll('.chart-card');
    logDebug('🔍 Encontrados ' + charts.length + ' gráficos na página', 'info');

    charts.forEach(function(chartCard, index) {
        const chartId = chartCard.dataset.chartId;
        logDebug('⏱️ Agendando carregamento do gráfico: ' + chartId, 'info');

        // Carregar dados iniciais com delay escalonado
        setTimeout(function() {
            carregarDadosIniciais(chartCard);
        }, 500 + (index * 200));
    });
}

/**
 * Carregar dados iniciais do gráfico (sem filtros)
 */
function carregarDadosIniciais(chartCard) {
    const chartId = chartCard.dataset.chartId;
    const filterGroup = chartCard.dataset.filterGroup;

    logDebug('🔍 Carregando dados: ' + chartId, 'info');

    // Se não tem filter_group definido, carregar sem filtros
    if (!filterGroup || filterGroup === '' || filterGroup === 'chart-1') {
        logDebug('📊 Sem grupo de filtro, carregando todos os dados', 'info');
        atualizarGrafico(chartCard, {});
        return;
    }

    // Buscar filtros de mês/ano se existirem
    const monthSelect = document.querySelector('[data-filter-group="' + filterGroup + '"] [data-filter-type="month"]');
    const yearSelect = document.querySelector('[data-filter-group="' + filterGroup + '"] [data-filter-type="year"]');

    let filters = {};

    if (monthSelect && yearSelect && monthSelect.value && yearSelect.value) {
        const month = monthSelect.value;
        const year = yearSelect.value;

        filters.date_start = year + '-' + month + '-01';
        const lastDay = new Date(year, month, 0).getDate();
        filters.date_end = year + '-' + month + '-' + String(lastDay).padStart(2, '0');

        logDebug('📅 Filtros: ' + month + '/' + year, 'info');
    } else {
        logDebug('⚠️ Sem filtros de data (grupo: ' + filterGroup + ')', 'warning');
    }

    atualizarGrafico(chartCard, filters);
}

/**
 * Atualizar gráfico com novos dados
 */
function atualizarGrafico(chartCard, filters) {
    const chartId = chartCard.dataset.chartId;
    const apiUrl = chartCard.dataset.apiUrl;
    const table = chartCard.dataset.table;
    const columns = chartCard.dataset.columns;
    const dateField = chartCard.dataset.dateField;
    const groupBy = chartCard.dataset.groupBy;
    const filterValueField = chartCard.dataset.filterValueField;
    const comparePeriod = chartCard.dataset.comparePeriod || 'no';

    const selectValue = filters.select || '';
    const dateStart = filters.date_start || '';
    const dateEnd = filters.date_end || '';

    logDebug('📊 ' + chartId + ' | Tabela: ' + table + ' | Cols: ' + columns, 'info');

    // Montar URL da API
    let url = apiUrl +
        '?table=' + encodeURIComponent(table) +
        '&columns=' + encodeURIComponent(columns) +
        '&date_field=' + encodeURIComponent(dateField) +
        '&group_by=' + encodeURIComponent(groupBy) +
        '&compare_period=' + encodeURIComponent(comparePeriod);

    // Adicionar filtro de canal se existir
    if (selectValue && selectValue !== 'todos' && filterValueField) {
        url += '&value_field=' + encodeURIComponent(filterValueField) + '&select=' + encodeURIComponent(selectValue);
    }

    // Adicionar filtros de data
    if (dateStart) {
        url += '&date_start=' + encodeURIComponent(dateStart);
    }
    if (dateEnd) {
        url += '&date_end=' + encodeURIComponent(dateEnd);
    }

    logDebug('🌐 Buscando API...', 'info');

    // Buscar dados da API
    fetch(url)
        .then(function(response) {
            logDebug('✅ Resposta da API recebida', 'success');
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Verificar se tem dados reais
                const temDados = data.categories.length > 0 && data.series.length > 0 &&
                                data.series.some(s => s.data && s.data.some(v => v > 0));

                logDebug('📦 ' + chartId + ': ' + data.categories.length + ' cats, ' + data.series.length + ' séries' +
                        (temDados ? ' ✅ COM DADOS' : ' ⚠️ SEM DADOS'),
                        temDados ? 'success' : 'warning');

                // Atualizar gráfico com novos dados
                const chart = window.charts && window.charts[chartId];
                if (chart) {
                    // Log detalhado dos dados
                    const valores = data.series.map(s => s.data.join(',')).join(' | ');
                    logDebug('📊 Atualizando com valores: [' + valores + ']', 'info');

                    chart.updateOptions({
                        xaxis: {
                            categories: data.categories
                        }
                    });
                    chart.updateSeries(data.series);
                    logDebug('🎉 ' + chartId + ' atualizado!', 'success');
                } else {
                    logDebug('⚠️ ' + chartId + ' não existe, retry...', 'warning');
                    // Tentar novamente após um delay
                    setTimeout(function() {
                        const retryChart = window.charts && window.charts[chartId];
                        if (retryChart) {
                            retryChart.updateOptions({
                                xaxis: {
                                    categories: data.categories
                                }
                            });
                            retryChart.updateSeries(data.series);
                            logDebug('🎉 ' + chartId + ' atualizado no retry!', 'success');
                        } else {
                            logDebug('❌ ' + chartId + ' não encontrado! Gráficos: ' + Object.keys(window.charts || {}).join(', '), 'error');
                        }
                    }, 500);
                }
            } else {
                logDebug('❌ Erro API: ' + data.error, 'error');
            }
        })
        .catch(function(error) {
            logDebug('❌ Erro requisição: ' + error.message, 'error');
        });
}
