<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Template - Relatórios</title>
</head>

<body class="m-reportsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-pagebase">

		<div class="m-pagebase__header">
			<h1>Editar Template de Relatório</h1>
			<a href="<?= url('/admin/reports') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
				<i data-lucide="arrow-left"></i> Voltar
			</a>
		</div>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error">
				<?= htmlspecialchars($_SESSION['error']) ?>
			</div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<div class="m-pagebase__form-container">
		<form method="POST" action="<?= url('/admin/reports/' . $template['id'] . '/update') ?>" enctype="multipart/form-data" class="m-pagebase__form">
			<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

			<div class="m-pagebase__form-group">
				<label class="m-pagebase__form-label">Nome do Relatório *</label>
				<input type="text" name="name" class="m-pagebase__form-input" value="<?= htmlspecialchars($template['name']) ?>" required>
			</div>

			<div class="m-pagebase__form-group">
				<label class="m-pagebase__form-label">Descrição</label>
				<textarea name="description" class="m-pagebase__form-textarea"><?= htmlspecialchars($template['description'] ?? '') ?></textarea>
			</div>

			<div class="m-pagebase__form-group">
				<label class="m-pagebase__form-label">Arquivo Excel (.xlsx ou .xls)</label>
				<div class="m-reports__current-file">
					<i data-lucide="file-text"></i>
					Arquivo atual: <strong><?= basename($template['file_path']) ?></strong>
				</div>
				<input type="file" name="excel_file" class="m-pagebase__form-input" accept=".xlsx,.xls">
				<div class="m-pagebase__form-help">Deixe em branco para manter o arquivo atual. Upload de novo arquivo substituirá o existente.</div>
			</div>

			<div class="m-pagebase__form-group">
				<label class="m-pagebase__form-label">
					<input type="checkbox" name="visible" id="visible" value="1" <?= $template['visible'] ? 'checked' : '' ?>>
					Visível na página de downloads
				</label>
			</div>

			<div class="m-reports__cell-mapping">
				<h3>Mapeamento de Células</h3>
				<div class="m-pagebase__form-help" style="margin-bottom: 15px;">Configure quais células do Excel serão preenchidas com quais dados.</div>

				<input type="text" id="filter-cells" class="m-reports__filter-input" placeholder="Filtrar por aba, célula ou fonte de dados...">

				<div id="cell-mappings">
					<?php if (empty($cells)): ?>
						<div class="m-reports__cell-row">
							<div>
								<input type="text" name="sheets[]" placeholder="Ex: 2025">
								<small>Aba (vazio = primeira)</small>
							</div>
							<div>
								<input type="text" name="cells[]" placeholder="Ex: B5" pattern="[A-Z]{1,3}[0-9]{1,7}">
								<small>Célula</small>
							</div>
							<div style="flex: 1;">
								<select name="data_sources[]">
									<option value="">Selecione a fonte de dados</option>
									<?php foreach ($dataSources as $key => $source): ?>
										<?php if (strpos($key, 'custom_') === 0): ?>
											<option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($source['label']) ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<small>Fonte de dados</small>
							</div>
							<button type="button" onclick="removeRow(this)">×</button>
						</div>
					<?php else: ?>
						<?php foreach ($cells as $cell): ?>
							<div class="m-reports__cell-row">
								<div>
									<input type="text" name="sheets[]" value="<?= htmlspecialchars($cell['sheet_name'] ?? '') ?>" placeholder="Ex: 2025">
									<small>Aba (vazio = primeira)</small>
								</div>
								<div>
									<input type="text" name="cells[]" value="<?= htmlspecialchars($cell['cell_ref']) ?>" placeholder="Ex: B5" pattern="[A-Z]{1,3}[0-9]{1,7}">
									<small>Célula</small>
								</div>
								<div style="flex: 1;">
									<select name="data_sources[]">
										<option value="">Selecione a fonte de dados</option>
										<?php foreach ($dataSources as $key => $source): ?>
											<?php if (strpos($key, 'custom_') === 0): ?>
												<option value="<?= htmlspecialchars($key) ?>" <?= $cell['data_source_key'] === $key ? 'selected' : '' ?>>
													<?= htmlspecialchars($source['label']) ?>
												</option>
											<?php endif; ?>
										<?php endforeach; ?>
									</select>
									<small>Fonte de dados</small>
								</div>
								<button type="button" onclick="removeRow(this)">×</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<button type="button" class="m-reports__btn-add" onclick="addCellRow()">
					<i data-lucide="plus"></i> Adicionar Célula
				</button>
			</div>

			<div class="m-pagebase__form-actions">
				<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
					<i data-lucide="save"></i> Salvar Alterações
				</button>
				<a href="<?= url('/admin/reports') ?>" class="m-pagebase__btn-secondary">
					<i data-lucide="x"></i> Cancelar
				</a>
			</div>
		</form>
		</div>

	</main>

	<script>
		const dataSourcesOptions = `
			<option value="">Selecione a fonte de dados</option>
			<?php foreach ($dataSources as $key => $source): ?>
				<?php if (strpos($key, 'custom_') === 0): ?>
					<option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($source['label']) ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		`;

		function addCellRow() {
			const container = document.getElementById('cell-mappings');
			const row = document.createElement('div');
			row.className = 'm-reports__cell-row';
			row.innerHTML = `
				<div>
					<input type="text" name="sheets[]" placeholder="Ex: 2025">
					<small>Aba (vazio = primeira)</small>
				</div>
				<div>
					<input type="text" name="cells[]" placeholder="Ex: B5" pattern="[A-Z]{1,3}[0-9]{1,7}">
					<small>Célula</small>
				</div>
				<div style="flex: 1;">
					<select name="data_sources[]">${dataSourcesOptions}</select>
					<small>Fonte de dados</small>
				</div>
				<button type="button" onclick="removeRow(this)">×</button>
			`;
			container.appendChild(row);
		}

		function removeRow(btn) {
			const row = btn.parentElement;
			if (document.querySelectorAll('.m-reports__cell-row').length > 1) {
				row.remove();
			} else {
				alert('É necessário ter pelo menos uma célula configurada.');
			}
		}

		// Filtro de células
		document.getElementById('filter-cells').addEventListener('input', function(e) {
			const filter = e.target.value.toLowerCase();
			const rows = document.querySelectorAll('.m-reports__cell-row');

			rows.forEach(row => {
				const sheet = row.querySelector('input[name="sheets[]"]').value.toLowerCase();
				const cell = row.querySelector('input[name="cells[]"]').value.toLowerCase();
				const dataSource = row.querySelector('select[name="data_sources[]"]');
				const dataSourceText = dataSource.options[dataSource.selectedIndex].text.toLowerCase();

				const matches = sheet.includes(filter) ||
				               cell.includes(filter) ||
				               dataSourceText.includes(filter);

				row.classList.toggle('hidden', !matches);
			});
		});
	</script>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>

</body>
</html>
