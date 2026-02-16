/**
 * MetricCard Filter - VERSÃO SIMPLES
 */

/**
 * Listener FORA do DOMContentLoaded para garantir que sempre escuta
 */
document.addEventListener('aegisFilterApplied', function(e) {
        try {
        fetch('/aegis/api/log.php?msg=[FILTER] EVENTO RECEBIDO: ' + JSON.stringify(e.detail));

        const filterGroup = e.detail.filterGroup;
        const selectValue = e.detail.filters.select || '';
        const dateStart = e.detail.filters.date_start || '';
        const dateEnd = e.detail.filters.date_end || '';

        console.log('🎯 Filtro recebido:', {
            group: filterGroup,
            select: selectValue,
            dateStart: dateStart,
            dateEnd: dateEnd
        });

        // Buscar TODOS os cards do grupo
        const cards = document.querySelectorAll(`.metric-card[data-filter-group="${filterGroup}"]`);

        fetch('/aegis/api/log.php?msg=[FILTER] Cards encontrados: ' + cards.length);

        if (cards.length === 0) return;

        // Atualizar cada card
        cards.forEach(card => {

        fetch('/aegis/api/log.php?msg=[FILTER] Processando card: ' + card.id);

        // Pegar config do card
        const cardType = card.dataset.cardType || 'metrica';
        const operation = card.dataset.operation;
        const format = card.dataset.format || 'number';
        const comparePeriod = card.dataset.comparePeriod || 'no';

        fetch('/aegis/api/log.php?msg=[FILTER] CardType=' + cardType + ' Operation=' + operation);

        // Montar URL base
        let url = `/aegis/api/metriccard-data.php?operation=${operation}&format=${format}&compare_period=${comparePeriod}`;

        // Se é multi-table, adicionar todos os source_X_*
        if (cardType === 'metrica_multi_table') {
            for (let i = 1; i <= 10; i++) {
                const sourceTable = card.getAttribute(`data-source-${i}-table`);
                const sourceColumn = card.getAttribute(`data-source-${i}-column`);
                const sourceDateField = card.getAttribute(`data-source-${i}-date-field`);

                if (sourceTable) url += `&source_${i}_table=${encodeURIComponent(sourceTable)}`;
                if (sourceColumn) url += `&source_${i}_column=${encodeURIComponent(sourceColumn)}`;
                if (sourceDateField) url += `&source_${i}_date_field=${encodeURIComponent(sourceDateField)}`;
            }
        } else {
            // Cards normais
            const table = card.dataset.table;
            const column = card.dataset.column;
            const dateField = card.dataset.dateField || '';
            const filterValueField = card.dataset.filterValueField || '';

            url += `&table=${table}&column=${column}`;

            // Adicionar filtro de canal se existir
            if (selectValue && filterValueField) {
                url += `&value_field=${filterValueField}&select=${selectValue}`;
            }

            // Adicionar filtros de data se existirem
            if (dateField && dateStart) {
                url += `&date_field=${dateField}&date_start=${dateStart}`;
            }
            if (dateField && dateEnd) {
                url += `&date_end=${dateEnd}`;
            }
        }

        // Adicionar filtros de data (multi-table sempre usa date_start/date_end da URL)
        if (cardType === 'metrica_multi_table') {
            if (dateStart) url += `&date_start=${dateStart}`;
            if (dateEnd) url += `&date_end=${dateEnd}`;
        }

        // Elemento do valor (detectar layout)
        // Layout default: .metric-card__value
        // Layout split: .metric-card__title p
        let valorEl = card.querySelector('.metric-card__value');
        if (!valorEl) {
            valorEl = card.querySelector('.metric-card__title p');
        }

        if (!valorEl) {
            fetch('/aegis/api/log.php?msg=[FILTER] ERRO: Elemento valor nao encontrado em ' + card.id);
            return;
        }

        // Loading
        valorEl.textContent = '...';

        fetch('/aegis/api/log.php?msg=[FILTER] URL: ' + url);

        // Fetch
        fetch(url)
            .then(r => r.json())
            .then(data => {
                fetch('/aegis/api/log.php?msg=[FILTER] Resposta: ' + JSON.stringify(data));
                // Atualizar debug info no card
                if (data.debug) {
                    const debugDetails = card.querySelector('details pre');
                    if (debugDetails) {
                        debugDetails.textContent = JSON.stringify(data.debug, null, 2);
                    }
                }

                if (data.success) {
                    valorEl.textContent = data.current_formatted;

                    // Atualizar percentual de mudança (se existir)
                    // Layout default: .metric-card__change
                    // Layout split: .metric-card__value p
                    let percentEl = card.querySelector('.metric-card__change');
                    if (!percentEl) {
                        percentEl = card.querySelector('.metric-card__value p');
                    }

                    if (percentEl && data.percent_change !== null && data.percent_change !== undefined) {
                        const isPositive = data.percent_change >= 0;
                        const changeSymbol = isPositive ? '+ ' : '- ';
                        const previousValue = data.previous_formatted || '0';

                        // Formatar datas do período anterior (COMENTADO - deixado para uso futuro)
                        // let dateRange = '';
                        // if (data.previous_start_date && data.previous_end_date) {
                        //     const formatDate = (d) => {
                        //         const [y, m, day] = d.split('-');
                        //         return day + '/' + m;
                        //     };
                        //     dateRange = ' [' + formatDate(data.previous_start_date) + ' a ' + formatDate(data.previous_end_date) + ']';
                        // }

                        percentEl.classList.remove('metric-card__value--positive', 'metric-card__value--negative');
                        percentEl.classList.add(isPositive ? 'metric-card__value--positive' : 'metric-card__value--negative');
                        percentEl.textContent = changeSymbol + Math.abs(Math.round(data.percent_change)) + '% (' + previousValue + ')';
                    } else if (percentEl) {
                        percentEl.textContent = '-';
                        percentEl.classList.remove('metric-card__value--positive', 'metric-card__value--negative');
                    }
                } else {
                    valorEl.textContent = 'Erro';
                }
            })
            .catch(err => {
                fetch('/aegis/api/log.php?msg=[FILTER] ERRO FETCH: ' + err.message);
                valorEl.textContent = 'Erro';
            });
    }); // Fechar forEach

    } catch(error) {
        fetch('/aegis/api/log.php?msg=[FILTER] ERRO GERAL: ' + error.message + ' | Stack: ' + error.stack);
    }
});
