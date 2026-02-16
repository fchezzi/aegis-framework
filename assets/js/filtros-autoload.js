/**
 * AEGIS Framework - Auto-Aplicação de Filtros
 * Aplica automaticamente "Todos" + "Últimos 30 dias" em todos os grupos de filtro
 *
 * PADRÃO GLOBAL: Todas as páginas com filtros carregam com:
 * - Canal/Seleção: "Todos"
 * - Data: Últimos 30 dias (hoje - 30 dias até hoje)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Aguardar 300ms para garantir que todos os componentes carregaram
    setTimeout(function() {
        // Encontrar todos os grupos de filtro únicos na página
        const filterGroups = new Set();
        document.querySelectorAll('[data-filter-group]').forEach(el => {
            filterGroups.add(el.dataset.filterGroup);
        });

        console.log('🔄 Auto-aplicando filtros nos grupos:', Array.from(filterGroups));

        // Para cada grupo, aplicar filtros padrão
        filterGroups.forEach(function(groupName) {
            autoAplicarFiltrosPadrao(groupName);
        });
    }, 300);
});

function autoAplicarFiltrosPadrao(filterGroup) {
    const filters = {};

    // 1. CANAL/SELECT: Deixar como "todos" (não adicionar ao objeto filters)
    const selectFilter = document.querySelector('[data-filter-group="' + filterGroup + '"] select[name="filter_select"]');
    if (selectFilter) {
        selectFilter.value = 'todos';
        console.log('✅ [' + filterGroup + '] Canal definido como: Todos');
    }

    // 2. DATA: Últimos 30 dias
    const hoje = new Date();
    const dataFim = new Date(hoje);
    dataFim.setHours(0, 0, 0, 0);

    const dataInicio = new Date(hoje);
    dataInicio.setDate(dataInicio.getDate() - 30);
    dataInicio.setHours(0, 0, 0, 0);

    const dateStart = dataInicio.toISOString().split('T')[0];
    const dateEnd = dataFim.toISOString().split('T')[0];

    filters.date_start = dateStart;
    filters.date_end = dateEnd;

    // Tentar preencher campos de data se existirem
    const inputDateStart = document.querySelector('[data-filter-group="' + filterGroup + '"] input[name="filter_date_start"]');
    const inputDateEnd = document.querySelector('[data-filter-group="' + filterGroup + '"] input[name="filter_date_end"]');

    if (inputDateStart) inputDateStart.value = dateStart;
    if (inputDateEnd) inputDateEnd.value = dateEnd;

    // Marcar botão "Últimos 30 dias" como ativo, se existir
    const presetButtons = document.querySelectorAll('[data-filter-group="' + filterGroup + '"] .filter-preset-btn');
    presetButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.days === '30') {
            btn.classList.add('active');
        }
    });

    console.log('✅ [' + filterGroup + '] Filtros padrão aplicados:', filters);

    // 3. DISPARAR EVENTO
    const event = new CustomEvent('aegisFilterApplied', {
        detail: {
            filterGroup: filterGroup,
            filters: filters
        }
    });
    document.dispatchEvent(event);

    console.log('🚀 [' + filterGroup + '] Evento aegisFilterApplied disparado');
}
