/**
 * AEGIS MetricCards - Conecta Filtros → API → Cards
 *
 * Este arquivo faz a ponte entre os componentes do Page Builder:
 * 1. Escuta eventos de filtros (aegisFilterApplied)
 * 2. Busca dados na API (/api/metriccard-data.php)
 * 3. Atualiza os cards dinamicamente
 *
 * @package AEGIS
 * @version 1.0.0
 * @since 9.0.4
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // FUNÇÃO PARA RECARREGAR UM CARD
    // ============================================

    function reloadCard(card, data = {}) {
        const apiUrl = card.dataset.apiUrl;
        const table = card.dataset.table;
        const column = card.dataset.column;
        const operation = card.dataset.operation;
        const format = card.dataset.format;
        const comparePeriod = card.dataset.comparePeriod;
        const dateField = card.dataset.dateField;

        // Validar dados obrigatórios
        if (!apiUrl || !table || !column || !operation) {
            console.error('Card sem configuração completa:', card.id, card.dataset);
            return;
        }

        // Desempacotar filters e config
        const filters = data.filters || {};
        const config = data.config || {};

        // Construir parâmetros da API
        const params = new URLSearchParams({
            table: table,
            column: column,
            operation: operation,
            format: format || 'number',
            compare_period: comparePeriod || 'yes'
        });

        // Adicionar campo de data se existir
        if (dateField) {
            params.set('date_field', dateField);
        }

        // Adicionar filtros recebidos (MESMA LÓGICA que tabelas)
        if (filters.select && config.value_field) {
            params.set('value_field', config.value_field);  // Ex: "canal"
            params.set('select', filters.select);            // Ex: "cortes"
        }
        if (filters.date_start) {
            params.set('date_start', filters.date_start);
        }
        if (filters.date_end) {
            params.set('date_end', filters.date_end);
        }

        // Mostrar loading
        const valueElement = card.querySelector('.metric-card__title p');
        const percentElement = card.querySelector('.metric-card__value p');

        if (valueElement) {
            const originalValue = valueElement.textContent;
            valueElement.style.opacity = '0.5';
            valueElement.textContent = '...';
        }

        // Debug: mostrar URL completa
        console.log('🔍 Carregando:', apiUrl + '?' + params.toString());

        // Fazer fetch
        fetch(apiUrl + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('📦 Resposta da API:', data);

                if (!data.success) {
                    throw new Error(data.error || 'Erro desconhecido');
                }

                // Atualizar valor principal
                if (valueElement) {
                    valueElement.style.opacity = '1';
                    valueElement.textContent = data.current_formatted;
                }

                // Atualizar percentual
                if (percentElement && data.percent_change !== null) {
                    const isPositive = data.percent_change >= 0;
                    const changeSymbol = isPositive ? '+ ' : '- ';

                    // Remover classes antigas
                    percentElement.classList.remove('metric-card__value--positive', 'metric-card__value--negative');

                    // Adicionar classe correta
                    percentElement.classList.add(isPositive ? 'metric-card__value--positive' : 'metric-card__value--negative');

                    // Atualizar texto
                    percentElement.textContent = changeSymbol + Math.abs(Math.round(data.percent_change)) + '%';
                } else if (percentElement) {
                    percentElement.textContent = '-';
                    percentElement.classList.remove('metric-card__value--positive', 'metric-card__value--negative');
                }

                console.log('✅ Card atualizado:', card.id, data.current_formatted);

                // Disparar evento customizado (caso alguém precise saber)
                const reloadEvent = new CustomEvent('aegisMetricCardReloaded', {
                    detail: {
                        cardId: card.id,
                        value: data.current,
                        percentChange: data.percent_change
                    }
                });
                document.dispatchEvent(reloadEvent);

            })
            .catch(error => {
                console.error('❌ Erro ao recarregar card:', error);
                console.error('❌ Detalhes:', error.message, error.stack);
                if (valueElement) {
                    valueElement.style.opacity = '1';
                    valueElement.textContent = 'Erro';
                    valueElement.style.color = 'red';
                }
            });
    }

    // ============================================
    // ESCUTAR EVENTOS DE FILTRO
    // ============================================

    document.addEventListener('aegisFilterApplied', function(event) {
        console.log('🔍 Filtro aplicado - atualizando MetricCards:', event.detail);

        const filterGroup = event.detail.filterGroup;
        const filters = event.detail.filters;
        const config = event.detail.config;

        // Encontrar cards do mesmo grupo
        const cards = document.querySelectorAll(`.metric-card[data-filter-group="${filterGroup}"]`);

        if (cards.length === 0) {
            console.warn('Nenhum MetricCard encontrado para o grupo:', filterGroup);
            return;
        }

        // Recarregar cada card com os novos filtros (incluindo config)
        cards.forEach(card => {
            console.log('🔄 Recarregando card:', card.id, 'com filtros:', filters, 'config:', config);
            reloadCard(card, { filters, config });
        });
    });

    console.log('✅ AEGIS MetricCards inicializado');
});
