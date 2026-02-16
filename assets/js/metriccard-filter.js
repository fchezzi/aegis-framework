/**
 * MetricCard - Atualização dinâmica via filtros
 * Escuta evento aegisFilterApplied e atualiza cards automaticamente
 */

console.log('✅ MetricCard Filter Script CARREGADO!');

document.addEventListener('DOMContentLoaded', function() {

    console.log('✅ MetricCard Filter PRONTO para escutar eventos');

    // Escutar evento de filtros
    document.addEventListener('aegisFilterApplied', function(e) {
        const eventData = e.detail;
        const filterGroup = eventData.filterGroup;
        const filters = eventData.filters;
        const config = eventData.config;

        console.log('🎯 MetricCard recebeu filtro:', eventData);
        console.log('   - filterGroup:', filterGroup);
        console.log('   - filters:', filters);
        console.log('   - config:', config);

        // Encontrar todos os cards do mesmo grupo
        const cards = document.querySelectorAll(`.metric-card[data-filter-group="${filterGroup}"]`);

        console.log('   - Cards encontrados:', cards.length);
        if (cards.length > 0) {
            console.log('   - Primeiro card:', cards[0]);
        }

        if (cards.length === 0) {
            console.warn('❌ Nenhum MetricCard encontrado para o grupo:', filterGroup);
            return;
        }

        // Atualizar cada card
        cards.forEach(card => {
            console.log('🔄 Iniciando atualização do card:', card.id);
            updateCard(card, filters, config);
        });
    });

    function updateCard(card, filters, config) {
        // Pegar dados do card
        const apiUrl = card.dataset.apiUrl;
        const table = card.dataset.table;
        const column = card.dataset.column;
        const operation = card.dataset.operation;
        const format = card.dataset.format || 'number';
        const comparePeriod = card.dataset.comparePeriod || 'no';
        const dateField = card.dataset.dateField || '';

        if (!apiUrl || !table || !column || !operation) {
            console.error('Card sem configuração completa:', card.dataset);
            return;
        }

        // Construir URL da API
        const url = new URL(apiUrl, window.location.origin);
        url.searchParams.append('table', table);
        url.searchParams.append('column', column);
        url.searchParams.append('operation', operation);
        url.searchParams.append('format', format);
        url.searchParams.append('compare_period', comparePeriod);

        if (dateField) {
            url.searchParams.append('date_field', dateField);
        }

        // Adicionar filtros
        if (filters.select && config.value_field) {
            url.searchParams.append('value_field', config.value_field);
            url.searchParams.append('select', filters.select);
        }

        if (filters.date_start) {
            url.searchParams.append('date_start', filters.date_start);
        }

        if (filters.date_end) {
            url.searchParams.append('date_end', filters.date_end);
        }

        // Elementos do card
        const valorElement = card.querySelector('.metric-card__title p');
        const percentElement = card.querySelector('.metric-card__value p');

        if (!valorElement) {
            console.error('Elemento de valor não encontrado no card');
            return;
        }

        // Mostrar loading
        valorElement.style.opacity = '0.5';
        valorElement.textContent = '...';

        console.log('🔄 Atualizando card:', url.toString());

        // Fazer fetch
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Dados recebidos:', data);

                if (!data.success) {
                    throw new Error(data.error || 'Erro desconhecido');
                }

                // Atualizar valor principal
                valorElement.style.opacity = '1';
                valorElement.textContent = data.current_formatted;

                // Atualizar percentual (se existir)
                if (percentElement && data.percent_change !== null && data.percent_change !== undefined) {
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

                console.log('✅ Card atualizado com sucesso!');
            })
            .catch(error => {
                console.error('❌ Erro ao atualizar card:', error);
                valorElement.style.opacity = '1';
                valorElement.textContent = 'Erro';
                valorElement.style.color = 'red';
            });
    }

    console.log('✅ MetricCard Filter inicializado');
});
