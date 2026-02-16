/**
 * Debug para listar TODOS os filtros da página
 */
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const allFilters = document.querySelectorAll('.aegis-filter-wrapper');

        let debugDiv = document.createElement('div');
        debugDiv.style.cssText = 'position:fixed;top:10px;left:10px;background:red;color:white;padding:15px;border:3px solid white;border-radius:5px;font-family:monospace;font-size:12px;z-index:99999;max-width:600px;';

        let html = '<strong>🚨 TOTAL DE FILTROS NA PÁGINA: ' + allFilters.length + '</strong><br><br>';

        allFilters.forEach(function(filter, index) {
            const tipo = filter.dataset.filterType || 'desconhecido';
            const grupos = filter.dataset.filterGroup || 'sem grupo';

            html += '<div style="background:rgba(0,0,0,0.3);padding:5px;margin:5px 0;">';
            html += '<strong>FILTRO #' + (index + 1) + '</strong><br>';
            html += '<strong>Tipo:</strong> ' + tipo + '<br>';
            html += '<strong>Grupos:</strong> ' + grupos + '<br>';
            html += '</div>';
        });

        debugDiv.innerHTML = html;
        document.body.appendChild(debugDiv);
    }, 500);
});
