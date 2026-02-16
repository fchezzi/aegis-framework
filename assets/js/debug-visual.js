/**
 * Debug Visual - Mostra console.log na tela
 */

(function() {
    // Criar div de debug
    const debugDiv = document.createElement('div');
    debugDiv.id = 'visual-debug';
    debugDiv.style.cssText = `
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        max-height: 300px;
        background: #000;
        color: #0f0;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        padding: 10px;
        overflow-y: auto;
        border-top: 3px solid #0f0;
        z-index: 999999;
    `;

    // Botão de minimizar/maximizar
    const toggleBtn = document.createElement('button');
    toggleBtn.textContent = '▼ Minimizar';
    toggleBtn.style.cssText = `
        position: fixed;
        bottom: 300px;
        right: 10px;
        background: #0f0;
        color: #000;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
        z-index: 9999999;
        font-weight: bold;
    `;

    let minimized = false;
    toggleBtn.onclick = function() {
        minimized = !minimized;
        if (minimized) {
            debugDiv.style.maxHeight = '40px';
            toggleBtn.textContent = '▲ Maximizar';
            toggleBtn.style.bottom = '40px';
        } else {
            debugDiv.style.maxHeight = '300px';
            toggleBtn.textContent = '▼ Minimizar';
            toggleBtn.style.bottom = '300px';
        }
    };

    const logContainer = document.createElement('div');
    debugDiv.appendChild(logContainer);

    function addLog(msg, color = '#0f0') {
        const timestamp = new Date().toLocaleTimeString('pt-BR');
        const logLine = document.createElement('div');
        logLine.style.color = color;
        logLine.style.marginBottom = '3px';
        logLine.style.borderLeft = `3px solid ${color}`;
        logLine.style.paddingLeft = '5px';
        logLine.innerHTML = `<span style="color: #666;">[${timestamp}]</span> ${msg}`;
        logContainer.appendChild(logLine);
        debugDiv.scrollTop = debugDiv.scrollHeight;
    }

    // Esperar DOM carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            document.body.appendChild(debugDiv);
            document.body.appendChild(toggleBtn);
        });
    } else {
        document.body.appendChild(debugDiv);
        document.body.appendChild(toggleBtn);
    }

    // Interceptar console.log
    const originalLog = console.log;
    console.log = function(...args) {
        originalLog.apply(console, args);
        const msg = args.map(a => {
            if (typeof a === 'object') {
                return '<pre style="margin: 0; padding: 5px; background: #111;">' + JSON.stringify(a, null, 2) + '</pre>';
            }
            return String(a);
        }).join(' ');
        addLog(msg, '#0ff');
    };

    // Interceptar console.error
    const originalError = console.error;
    console.error = function(...args) {
        originalError.apply(console, args);
        const msg = args.map(a => String(a)).join(' ');
        addLog('❌ ' + msg, '#f00');
    };

    // Interceptar console.warn
    const originalWarn = console.warn;
    console.warn = function(...args) {
        originalWarn.apply(console, args);
        const msg = args.map(a => String(a)).join(' ');
        addLog('⚠️ ' + msg, '#fa0');
    };

    // Log inicial
    setTimeout(() => {
        addLog('🚀 Debug Visual ativado!', '#0f0');
    }, 100);

    // Escutar eventos aegisFilterApplied
    document.addEventListener('aegisFilterApplied', function(e) {
        addLog('🎯 <strong>EVENTO aegisFilterApplied!</strong>', '#0f0');
        addLog('Grupo: ' + e.detail.filterGroup, '#0ff');
        addLog('Filtros: <pre style="margin: 0; padding: 5px; background: #111;">' + JSON.stringify(e.detail.filters, null, 2) + '</pre>', '#0ff');
    });
})();
