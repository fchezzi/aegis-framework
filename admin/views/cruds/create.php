<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Criar CRUD - <?= ADMIN_NAME ?></title>
	<style>
		.field-builder {
			background: #f8f9fa;
			padding: 20px;
			border-radius: 8px;
			margin-bottom: 20px;
		}
		.field-item {
			background: white;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-bottom: 10px;
			position: relative;
		}
		.field-item__remove {
			position: absolute;
			top: 10px;
			right: 10px;
			background: #dc3545;
			color: white;
			border: none;
			padding: 5px 10px;
			border-radius: 3px;
			cursor: pointer;
			font-size: 12px;
		}
		.field-item__grid {
			display: grid;
			grid-template-columns: 1fr 1fr 1fr 1fr;
			gap: 15px;
			margin-top: 10px;
		}
		.btn-add-field {
			background: #28a745;
			color: white;
			border: none;
			padding: 10px 20px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
		}
		.config-section {
			background: #fff;
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-bottom: 20px;
		}
		.checkbox-group {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 15px;
		}
	</style>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

		<div class="m-pagebase__header">
			<h1>Criar Novo CRUD</h1>
			<a href="<?= url('/admin/cruds') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
				<i data-lucide="arrow-left"></i> Voltar
			</a>
		</div>

		<div class="m-admins__form-container">

			<?php if (isset($_SESSION['error'])): ?>
				<div class="m-admins__alert-error">
					<?= htmlspecialchars($_SESSION['error']) ?>
				</div>
				<?php unset($_SESSION['error']); ?>
			<?php endif; ?>

		<form method="POST" action="<?= url('/admin/cruds') ?>" id="crudForm">
			<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

			<!-- BÁSICO -->
			<div class="config-section">
				<h3>Informações Básicas</h3>

				<div class="m-pagebase__form-group">
					<label class="m-pagebase__label">Nome do CRUD *</label>
					<input type="text" name="name" class="m-pagebase__input" required placeholder="Ex: Banner Hero" />
					<small>Nome humanizado para exibição</small>
				</div>

				<div class="m-pagebase__form-group">
					<label class="m-pagebase__label">Nome da Tabela *</label>
					<input type="text" name="table_name" class="m-pagebase__input" required placeholder="Ex: tbl_banner_hero" pattern="^tbl_[a-z_]+$" />
					<small>Deve começar com tbl_ e usar apenas letras minúsculas e underscore</small>
				</div>
			</div>

			<!-- CAMPOS DINÂMICOS -->
			<div class="field-builder">
				<h3>Campos da Tabela</h3>
				<p>Adicione os campos que sua tabela terá (além de id, created_at, updated_at que são automáticos)</p>

				<div id="fieldsContainer"></div>

				<button type="button" class="btn-add-field" onclick="addField()">+ Adicionar Campo</button>
			</div>

			<!-- CONFIGURAÇÕES -->
			<div class="config-section">
				<h3>Configurações</h3>

				<div class="checkbox-group">
					<label>
						<input type="checkbox" name="has_ordering" value="1" />
						Campo de ordenação manual (order)
					</label>

					<label>
						<input type="checkbox" name="has_status" value="1" checked />
						Campo de status (ativo/inativo)
					</label>

					<label>
						<input type="checkbox" name="has_slug" value="1" id="hasSlugCheck" onchange="toggleSlugSource()" />
						Gerar slug automaticamente
					</label>

					<label>
						<input type="checkbox" name="has_frontend" value="1" id="hasFrontendCheck" onchange="toggleFrontendOptions()" />
						Display no frontend
					</label>
				</div>

				<div class="m-pagebase__form-group" id="slugSourceDiv" style="display:none;">
					<label class="m-pagebase__label">Campo base para slug</label>
					<input type="text" name="slug_source" class="m-pagebase__input" placeholder="Ex: titulo" />
				</div>

				<div class="m-pagebase__form-group" id="frontendOptionsDiv" style="display:none;">
					<label class="m-pagebase__label">Formato de Saída Frontend</label>
					<select name="frontend_format" class="m-pagebase__input">
						<option value="grid">Grid/Cards - Ideal para produtos, portfólio, blog</option>
						<option value="carousel">Carrossel/Slider - Ideal para banners, depoimentos</option>
						<option value="list">Lista Simples - Ideal para notícias, eventos</option>
						<option value="table">Tabela - Ideal para dados estruturados</option>
					</select>
					<small>Define como os dados serão exibidos no frontend</small>
				</div>
			</div>

			<!-- AÇÕES -->
			<button type="submit" class="m-admins__form-submit">
				<i data-lucide="save"></i> Criar CRUD
			</button>

		</form>

		</div>

	</main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();

		let fieldCount = 0;

		function addField() {
			fieldCount++;
			const container = document.getElementById('fieldsContainer');

			const fieldHtml = `
				<div class="field-item" id="field-${fieldCount}">
					<button type="button" class="field-item__remove" onclick="removeField(${fieldCount})">× Remover</button>

					<div class="field-item__grid">
						<div>
							<label>Nome do Campo *</label>
							<input type="text" name="fields[${fieldCount}][name]" class="m-pagebase__input" required placeholder="Ex: titulo" pattern="^[a-z_]+$" />
						</div>

						<div>
							<label>Tipo *</label>
							<select name="fields[${fieldCount}][type]" class="m-pagebase__input" required onchange="handleTypeChange(${fieldCount}, this.value)">
								<option value="">Selecione...</option>
								<option value="string">String (VARCHAR)</option>
								<option value="text">Text (TEXT)</option>
								<option value="int">Inteiro (INT)</option>
								<option value="decimal">Decimal</option>
								<option value="date">Data</option>
								<option value="datetime">Data/Hora</option>
								<option value="upload">Upload de Arquivo</option>
								<option value="fk">Relacionamento (FK)</option>
							</select>
						</div>

						<div>
							<label>Obrigatório?</label>
							<select name="fields[${fieldCount}][required]" class="m-pagebase__input">
								<option value="0">Não</option>
								<option value="1">Sim</option>
							</select>
						</div>

						<div id="extraConfig-${fieldCount}">
							<label>Max Length</label>
							<input type="number" name="fields[${fieldCount}][max_length]" class="m-pagebase__input" placeholder="255" />
						</div>
					</div>

					<div id="typeSpecific-${fieldCount}" style="margin-top: 10px;"></div>
				</div>
			`;

			container.insertAdjacentHTML('beforeend', fieldHtml);
		}

		function removeField(id) {
			document.getElementById('field-' + id).remove();
		}

		function handleTypeChange(fieldId, type) {
			const container = document.getElementById(`typeSpecific-${fieldId}`);
			container.innerHTML = '';

			if (type === 'upload') {
				container.innerHTML = `
					<label>Tipos de arquivo permitidos (separados por vírgula)</label>
					<input type="text" name="fields[${fieldId}][mime_types]" class="m-pagebase__input" placeholder="image/jpeg,image/png,image/webp" />
					<small>Ex: image/jpeg,image/png ou application/pdf</small>
				`;
			} else if (type === 'fk') {
				container.innerHTML = `
					<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
						<div>
							<label>Tabela relacionada *</label>
							<select name="fields[${fieldId}][fk_table]" class="m-pagebase__input" required>
								<option value="">Selecione...</option>
								<?php foreach ($tables as $table): ?>
									<option value="<?= array_values($table)[0] ?>"><?= array_values($table)[0] ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label>Coluna de referência *</label>
							<input type="text" name="fields[${fieldId}][fk_column]" class="m-pagebase__input" value="id" required />
						</div>
						<div>
							<label>Campo para exibir</label>
							<input type="text" name="fields[${fieldId}][display_field]" class="m-pagebase__input" placeholder="Ex: name, titulo" />
						</div>
					</div>
				`;
			}
		}

		function toggleSlugSource() {
			const checkbox = document.getElementById('hasSlugCheck');
			const div = document.getElementById('slugSourceDiv');
			div.style.display = checkbox.checked ? 'block' : 'none';
		}

		function toggleFrontendOptions() {
			const checkbox = document.getElementById('hasFrontendCheck');
			const div = document.getElementById('frontendOptionsDiv');
			div.style.display = checkbox.checked ? 'block' : 'none';
		}

		// Adicionar 1 campo inicial
		addField();
  </script>

</body>
</html>
