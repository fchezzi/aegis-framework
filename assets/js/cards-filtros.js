/**
 * Sistema de Filtros para Cards
 */

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.metric-card[data-filter-group]');

    cards.forEach(function(card) {
        const filterGroup = card.dataset.filterGroup;

        document.addEventListener('aegisFilterApplied', function(e) {
            const eventData = e.detail;

            console.log('Card recebeu evento:', eventData.filterGroup, 'esperava:', filterGroup);

            if (eventData.filterGroup !== filterGroup) {
                return;
            }

            console.log('Card vai aplicar filtro:', eventData.filters);
            aplicarFiltroCard(card, eventData.filters);
        });
    });
});

function aplicarFiltroCard(card, filters) {
    const apiUrl = card.dataset.apiUrl;
    const cardType = card.dataset.cardType || 'metrica';
    const operation = card.dataset.operation;
    const format = card.dataset.format || 'number';
    const comparePeriod = card.dataset.comparePeriod || 'no';

    const selectValue = filters.select || '';
    const dateStart = filters.date_start || '';
    const dateEnd = filters.date_end || '';

    // Montar URL base
    let url = apiUrl + '?operation=' + operation + '&format=' + format + '&compare_period=' + comparePeriod;

    // Se é multi-table
    if (cardType === 'metrica_multi_table') {
        for (let i = 1; i <= 10; i++) {
            const sourceTable = card.getAttribute('data-source-' + i + '-table');
            const sourceColumn = card.getAttribute('data-source-' + i + '-column');
            const sourceDateField = card.getAttribute('data-source-' + i + '-date-field');

            if (sourceTable) url += '&source_' + i + '_table=' + encodeURIComponent(sourceTable);
            if (sourceColumn) url += '&source_' + i + '_column=' + encodeURIComponent(sourceColumn);
            if (sourceDateField) url += '&source_' + i + '_date_field=' + encodeURIComponent(sourceDateField);
        }
        if (dateStart) url += '&date_start=' + dateStart;
        if (dateEnd) url += '&date_end=' + dateEnd;
    } else {
        // Cards normais
        const table = card.dataset.table;
        const column = card.dataset.column;
        const dateField = card.dataset.dateField || '';
        const filterValueField = card.dataset.filterValueField || '';

        // Condição adicional (para métrica condicional)
        const conditionColumn = card.dataset.conditionColumn || '';
        const conditionOperator = card.dataset.conditionOperator || '';
        const conditionValue = card.dataset.conditionValue || '';

        url += '&table=' + table + '&column=' + column;

        // Adicionar condição adicional se existir
        if (conditionColumn && conditionValue) {
            url += '&condition_column=' + encodeURIComponent(conditionColumn);
            url += '&condition_operator=' + encodeURIComponent(conditionOperator);
            url += '&condition_value=' + encodeURIComponent(conditionValue);
        }

        // Adicionar filtro de canal se existir
        if (selectValue && filterValueField) {
            url += '&value_field=' + filterValueField + '&select=' + selectValue;
        }

        // Adicionar filtros de data se existirem
        if (dateField && dateStart) {
            url += '&date_field=' + dateField + '&date_start=' + dateStart;
        }
        if (dateField && dateEnd) {
            url += '&date_end=' + dateEnd;
        }
    }

    // Elemento do valor (detectar layout)
    // Layout split: valor está em .metric-card__title p
    // Layout default: valor está em .metric-card__value
    let valorEl = card.querySelector('.metric-card__title p');
    if (!valorEl) {
        valorEl = card.querySelector('.metric-card__value');
    }

    if (!valorEl) {
        return;
    }

    valorEl.textContent = '...';

    // Fetch
    fetch(url)
        .then(function(r) {
            return r.text();
        })
        .then(function(text) {
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                valorEl.textContent = 'Erro JSON';
                return;
            }

            if (data.success) {
                valorEl.textContent = data.current_formatted;

                // Atualizar percentual de mudança
                let percentEl = card.querySelector('.metric-card__change');
                if (!percentEl) {
                    percentEl = card.querySelector('.metric-card__value p');
                }

                if (percentEl && data.percent_change !== null && data.percent_change !== undefined) {
                    const isPositive = data.percent_change >= 0;
                    const changeSymbol = isPositive ? '+ ' : '- ';
                    const previousValue = data.previous_formatted || '0';

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
        .catch(function(err) {
            valorEl.textContent = 'Erro';
        });
}
