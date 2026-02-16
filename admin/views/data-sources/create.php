<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Nova Fonte de Dados - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Nova Fonte de Dados</h1>
      <a href="<?= url('/admin/data-sources') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
        <i data-lucide="arrow-left"></i> Voltar
      </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert--error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="m-pagebase__card">
      <form method="POST" action="<?= url('/admin/data-sources/store') ?>" id="dataSourceForm">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-label">Nome da Fonte *</label>
          <input type="text" name="name" class="m-pagebase__form-input" required placeholder="Ex: YouTube Views Fevereiro 2026">
        </div>

        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-label">Descrição</label>
          <textarea name="description" class="m-pagebase__form-textarea" rows="2" placeholder="Descrição opcional"></textarea>
        </div>

        <div class="datasource-form-row">
          <div class="m-pagebase__form-group">
            <label class="m-pagebase__form-label">Tabela *</label>
            <select name="table_name" id="tableSelect" class="m-pagebase__form-select" required>
              <option value="">Selecione...</option>
              <?php foreach ($tables as $tableName => $tableInfo): ?>
                <option value="<?= $tableName ?>"><?= htmlspecialchars($tableInfo['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="m-pagebase__form-group">
            <label class="m-pagebase__form-label">Operação *</label>
            <select name="operation" id="operationSelect" class="m-pagebase__form-select" required>
              <option value="">Selecione...</option>
              <?php foreach ($operations as $op => $label): ?>
                <option value="<?= $op ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="m-pagebase__form-group" id="columnGroup">
            <label class="m-pagebase__form-label">Coluna *</label>
            <select name="column_name" id="columnSelect" class="m-pagebase__form-select">
              <option value="">Selecione a tabela primeiro...</option>
            </select>
          </div>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

        <h3 style="margin-bottom: 15px;">Filtros de Data</h3>
        <div id="dateFiltersContainer"></div>
        <button type="button" class="m-pagebase__btn m-pagebase__btn--secondary m-pagebase__btn--widthauto" onclick="addDateFilter()">
          <i data-lucide="plus"></i> Adicionar Filtro de Data
        </button>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

        <h3 style="margin-bottom: 15px;">Outras Condições WHERE</h3>
        <div id="conditionsContainer"></div>
        <button type="button" class="m-pagebase__btn m-pagebase__btn--secondary m-pagebase__btn--widthauto" onclick="addCondition()">
          <i data-lucide="plus"></i> Adicionar Condição
        </button>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

        <div style="display: flex; gap: 10px;">
          <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">Salvar Fonte de Dados</button>
          <button type="button" class="m-pagebase__btn m-pagebase__btn--secondary m-pagebase__btn--widthauto" onclick="previewQuery()">
            <i data-lucide="eye"></i> Preview
          </button>
        </div>
      </form>

      <div id="previewBox" class="datasource-preview">
        <h4>Preview da Query:</h4>
        <pre id="previewSql"></pre>
        <p><strong>Resultado:</strong> <span id="previewResult"></span></p>
      </div>
    </div>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();

    // Dados das tabelas/colunas/operadores vindos do PHP
    const tablesData = <?= json_encode($tables) ?>;
    const operators = <?= json_encode($operators) ?>;

    let conditionCounter = 0;
    let dateFilterCounter = 0;

    // Quando tabela mudar, carregar colunas
    document.getElementById('tableSelect').addEventListener('change', function() {
      const tableName = this.value;
      const columnSelect = document.getElementById('columnSelect');

      columnSelect.innerHTML = '<option value="">Selecione...</option>';

      if (tableName && tablesData[tableName]) {
        const columns = tablesData[tableName].columns;
        for (const [colName, colInfo] of Object.entries(columns)) {
          const option = document.createElement('option');
          option.value = colName;
          option.textContent = colInfo.label;
          columnSelect.appendChild(option);
        }
      }
    });

    // Quando operação mudar para COUNT, desabilitar coluna
    document.getElementById('operationSelect').addEventListener('change', function() {
      const columnGroup = document.getElementById('columnGroup');
      if (this.value === 'COUNT') {
        columnGroup.style.opacity = '0.5';
        document.getElementById('columnSelect').disabled = true;
      } else {
        columnGroup.style.opacity = '1';
        document.getElementById('columnSelect').disabled = false;
      }
    });

    // Adicionar filtro de data
    function addDateFilter() {
      const tableName = document.getElementById('tableSelect').value;
      if (!tableName) {
        alert('Selecione uma tabela primeiro');
        return;
      }

      const columns = tablesData[tableName].columns;
      const dateColumns = Object.entries(columns).filter(([name, info]) =>
        info.type === 'date' || info.type === 'datetime'
      );

      if (dateColumns.length === 0) {
        alert('Esta tabela não possui colunas de data');
        return;
      }

      const container = document.getElementById('dateFiltersContainer');
      const filterId = dateFilterCounter;

      const filterDiv = document.createElement('div');
      filterDiv.className = 'datasource-date-filter';
      filterDiv.id = 'dateFilter_' + filterId;

      filterDiv.innerHTML = `
        <div class="datasource-filter-controls">
          <select name="date_filters[${filterId}][column]" class="m-pagebase__form-select">
            <option value="">Coluna de data...</option>
            ${dateColumns.map(([name, info]) =>
              `<option value="${name}">${info.label}</option>`
            ).join('')}
          </select>
          <select name="date_filters[${filterId}][type]" class="m-pagebase__form-select" onchange="toggleDateFilterType(${filterId})">
            <option value="month">Por Mês</option>
            <option value="period">Período Personalizado</option>
          </select>
          <button type="button" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" onclick="removeDateFilter(${filterId})">
            <i data-lucide="trash-2"></i>
          </button>
        </div>
        <div id="dateFilterInputs_${filterId}">
          <div class="datasource-month-inputs month-inputs">
            <select name="date_filters[${filterId}][month]" class="m-pagebase__form-select">
              <option value="01">Janeiro</option>
              <option value="02">Fevereiro</option>
              <option value="03">Março</option>
              <option value="04">Abril</option>
              <option value="05">Maio</option>
              <option value="06">Junho</option>
              <option value="07">Julho</option>
              <option value="08">Agosto</option>
              <option value="09">Setembro</option>
              <option value="10">Outubro</option>
              <option value="11">Novembro</option>
              <option value="12">Dezembro</option>
            </select>
            <select name="date_filters[${filterId}][year]" class="m-pagebase__form-select">
              ${generateYearOptions()}
            </select>
          </div>
          <div class="datasource-period-inputs period-inputs" style="display: none;">
            <input type="date" name="date_filters[${filterId}][start_date]" class="m-pagebase__form-input">
            <span>até</span>
            <input type="date" name="date_filters[${filterId}][end_date]" class="m-pagebase__form-input">
          </div>
        </div>
      `;

      container.appendChild(filterDiv);
      dateFilterCounter++;
      lucide.createIcons();
    }

    function generateYearOptions() {
      const currentYear = new Date().getFullYear();
      let options = '';
      for (let year = currentYear + 1; year >= 2020; year--) {
        const selected = year === currentYear ? 'selected' : '';
        options += `<option value="${year}" ${selected}>${year}</option>`;
      }
      return options;
    }

    function toggleDateFilterType(filterId) {
      const filterDiv = document.getElementById('dateFilter_' + filterId);
      const typeSelect = filterDiv.querySelector('select[name="date_filters[' + filterId + '][type]"]');
      const monthInputs = filterDiv.querySelector('.month-inputs');
      const periodInputs = filterDiv.querySelector('.period-inputs');

      if (typeSelect.value === 'month') {
        monthInputs.style.display = 'flex';
        periodInputs.style.display = 'none';
      } else {
        monthInputs.style.display = 'none';
        periodInputs.style.display = 'flex';
      }
    }

    function removeDateFilter(filterId) {
      const filterDiv = document.getElementById('dateFilter_' + filterId);
      if (filterDiv) {
        filterDiv.remove();
      }
    }

    // Adicionar condição
    function addCondition() {
      const tableName = document.getElementById('tableSelect').value;
      if (!tableName) {
        alert('Selecione uma tabela primeiro');
        return;
      }

      const columns = tablesData[tableName].columns;
      const container = document.getElementById('conditionsContainer');

      const conditionDiv = document.createElement('div');
      conditionDiv.className = 'datasource-condition';
      conditionDiv.style.display = 'flex';
      conditionDiv.style.gap = '10px';

      conditionDiv.innerHTML = `
        <select name="conditions[${conditionCounter}][column]" class="m-pagebase__form-select" style="flex: 1;">
          <option value="">Coluna...</option>
          ${Object.entries(columns).map(([name, info]) =>
            `<option value="${name}">${info.label}</option>`
          ).join('')}
        </select>
        <select name="conditions[${conditionCounter}][operator]" class="m-pagebase__form-select" style="flex: 1;">
          <option value="">Operador...</option>
          ${Object.entries(operators).map(([op, label]) =>
            `<option value="${op}">${label}</option>`
          ).join('')}
        </select>
        <input type="text" name="conditions[${conditionCounter}][value]" placeholder="Valor" class="m-pagebase__form-input" style="flex: 2;">
        <button type="button" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" onclick="this.parentElement.remove(); lucide.createIcons();">
          <i data-lucide="trash-2"></i>
        </button>
      `;

      container.appendChild(conditionDiv);
      conditionCounter++;
      lucide.createIcons();
    }

    // Preview da query
    function previewQuery() {
      const formData = new FormData(document.getElementById('dataSourceForm'));

      fetch('<?= url('/admin/data-sources/preview') ?>', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          document.getElementById('previewSql').textContent = data.sql;
          document.getElementById('previewResult').textContent = data.result;
          document.getElementById('previewBox').style.display = 'block';
        } else {
          alert('Erro: ' + data.error);
        }
      })
      .catch(e => alert('Erro ao carregar preview'));
    }
  </script>
</body>
</html>
