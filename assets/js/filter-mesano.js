/**
 * AEGIS Framework - Filtro Mês/Ano
 * Integração com cards e tabelas
 */

document.addEventListener('DOMContentLoaded', function() {
    const filtros = document.querySelectorAll('.filter-mesano');

    filtros.forEach(function(filtro) {
        const filterGroup = filtro.dataset.filterGroup;
        const monthSelect = filtro.querySelector('[data-filter-type="month"]');
        const yearSelect = filtro.querySelector('[data-filter-type="year"]');

        // Selecionar mês atual -1 e ano correspondente
        if (monthSelect && yearSelect) {
            const hoje = new Date();
            let mesAtual = hoje.getMonth() + 1;
            let anoAtual = hoje.getFullYear();

            // Calcular mês anterior
            let mesAnterior, anoAnterior;
            if (mesAtual === 1) {
                mesAnterior = 12;
                anoAnterior = anoAtual - 1;
            } else {
                mesAnterior = mesAtual - 1;
                anoAnterior = anoAtual;
            }

            const mesAnteriorFormatado = String(mesAnterior).padStart(2, '0');

            monthSelect.value = mesAnteriorFormatado;
            yearSelect.value = String(anoAnterior);
        }

        // Aplicar ao mudar automaticamente
        if (monthSelect) monthSelect.addEventListener('change', () => aplicarFiltroMesAno(filterGroup, monthSelect, yearSelect));
        if (yearSelect) yearSelect.addEventListener('change', () => aplicarFiltroMesAno(filterGroup, monthSelect, yearSelect));

        // Auto-aplicar filtro ao carregar a página
        setTimeout(() => {
            aplicarFiltroMesAno(filterGroup, monthSelect, yearSelect);
        }, 100);
    });
});

function aplicarFiltroMesAno(filterGroup, monthSelect, yearSelect) {
    const month = monthSelect ? monthSelect.value : '';
    const year = yearSelect ? yearSelect.value : '';

    // Calcular date_start e date_end baseado em mês/ano
    let dateStart = '';
    let dateEnd = '';

    if (year && month) {
        // Mês e ano selecionados: primeiro dia até último dia do mês
        dateStart = year + '-' + month + '-01';
        const lastDay = new Date(year, month, 0).getDate();
        dateEnd = year + '-' + month + '-' + String(lastDay).padStart(2, '0');
    } else if (year && !month) {
        // Só ano: janeiro até dezembro
        dateStart = year + '-01-01';
        dateEnd = year + '-12-31';
    } else if (!year && month) {
        // Só mês: mês atual em todos os anos disponíveis
        const currentYear = new Date().getFullYear();
        dateStart = currentYear + '-' + month + '-01';
        const lastDay = new Date(currentYear, month, 0).getDate();
        dateEnd = currentYear + '-' + month + '-' + String(lastDay).padStart(2, '0');
    }

    // Preservar filtro de canal (select) se existir
    const filters = {
        date_start: dateStart,
        date_end: dateEnd,
        month: month,
        year: year
    };

    // Buscar filtro de canal do mesmo grupo
    const selectFilter = document.querySelector('[data-filter-group*="' + filterGroup + '"] select[name="filter_select"]');
    if (selectFilter && selectFilter.value && selectFilter.value !== 'todos') {
        filters.select = selectFilter.value;
    }

    // Emitir evento customizado para outros componentes (cards, tabelas)
    const event = new CustomEvent('aegisFilterApplied', {
        detail: {
            filterGroup: filterGroup,
            filters: filters
        }
    });

    document.dispatchEvent(event);
}
