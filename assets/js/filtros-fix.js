/**
 * AEGIS Framework - Fix para Filtros
 * Corrige o problema de filtros de canal apagando filtros de data
 */

document.addEventListener('DOMContentLoaded', function() {
    // Aguardar um pouco para garantir que outros scripts carregaram
    setTimeout(function() {
        const filtrosCanal = document.querySelectorAll('.aegis-filter-wrapper[data-filter-type="canal"]');

        filtrosCanal.forEach(function(wrapper) {
            const filterGroup = wrapper.dataset.filterGroup;
            const selectFilter = wrapper.querySelector('select[name="filter_select"]');

            if (!selectFilter) return;

            // Suporte a múltiplos grupos (SEMPRE separados por vírgula)
            console.log('🔧 [FIX DEBUG] filterGroup RAW:', filterGroup);
            var filterGroups = filterGroup.split(',').map(function(g) { return g.trim(); }).filter(function(g) { return g.length > 0; });
            console.log('🔧 [FIX DEBUG] filterGroups ARRAY:', filterGroups);

            // Remover listeners antigos (se existirem) e adicionar novo
            const newSelect = selectFilter.cloneNode(true);
            selectFilter.parentNode.replaceChild(newSelect, selectFilter);

            // Adicionar listener corrigido
            newSelect.addEventListener('change', function() {
                const filters = {};

                // Canal selecionado
                if (newSelect.value && newSelect.value !== 'todos') {
                    filters.select = newSelect.value;
                }

                // Para cada grupo, disparar evento com seus filtros de data específicos
                filterGroups.forEach(function(group) {
                    const groupFilters = Object.assign({}, filters); // Copiar filtros base (canal)

                    // 🔧 PRESERVAR filtros de mês/ano do grupo específico
                    const monthSelect = document.querySelector('[data-filter-group="' + group + '"] [data-filter-type="month"]');
                    const yearSelect = document.querySelector('[data-filter-group="' + group + '"] [data-filter-type="year"]');

                    if (monthSelect && yearSelect && monthSelect.value && yearSelect.value) {
                        const month = monthSelect.value;
                        const year = yearSelect.value;

                        // Calcular date_start e date_end
                        groupFilters.date_start = year + '-' + month + '-01';
                        const lastDay = new Date(year, month, 0).getDate();
                        groupFilters.date_end = year + '-' + month + '-' + String(lastDay).padStart(2, '0');
                        groupFilters.month = month;
                        groupFilters.year = year;

                        console.log('✅ [FIX] Filtros de data preservados para grupo ' + group + ':', groupFilters.date_start, groupFilters.date_end);
                    }

                    // Disparar evento para este grupo específico
                    const event = new CustomEvent('aegisFilterApplied', {
                        detail: {
                            filterGroup: group,
                            filters: groupFilters
                        }
                    });
                    document.dispatchEvent(event);

                    console.log('✅ [FIX] Filtro de canal aplicado para grupo ' + group + ':', groupFilters);
                });
            });

            console.log('✅ [FIX] Filtro de canal corrigido para grupos:', filterGroups.join(', '));

            // 🔧 DEBUG VISUAL
            let debugDiv = document.getElementById('filter-debug');
            if (debugDiv) {
                let fixDebug = '<hr style="border-color:#ff0;margin:10px 0;">';
                fixDebug += '<strong style="color:#ff0;">🔧 DEBUG FILTROS-FIX.JS</strong><br>';
                fixDebug += '<strong style="color:#ff0;">filterGroup RAW:</strong> "' + filterGroup + '"<br>';
                fixDebug += '<strong style="color:#ff0;">filterGroups ARRAY:</strong> [' + filterGroups.join(', ') + ']<br>';
                fixDebug += '<strong style="color:#ff0;">Total de grupos:</strong> ' + filterGroups.length + '<br>';
                debugDiv.innerHTML += fixDebug;
            }
        });
    }, 200);
});
