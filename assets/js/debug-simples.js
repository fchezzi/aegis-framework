alert('DEBUG CARREGOU');

const div = document.createElement('div');
div.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:red;color:white;padding:30px;font-size:20px;font-weight:bold;z-index:999999;border:5px solid yellow;';
div.innerHTML = 'TESTE - SE VOCÊ VÊ ISSO, O SCRIPT CARREGOU';
document.body.appendChild(div);

setTimeout(function() {
    const filtroCanal = document.querySelector('.aegis-filter-wrapper[data-filter-type="canal"]');

    div.innerHTML = 'Filtro encontrado: ' + (filtroCanal ? 'SIM' : 'NÃO');

    if (filtroCanal) {
        const grupos = filtroCanal.getAttribute('data-filter-group');
        div.innerHTML = 'GRUPOS DO FILTRO DE CANAL:<br><br>"' + grupos + '"';
    }
}, 2000);
