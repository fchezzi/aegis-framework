<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Item de Menu - <?= ADMIN_NAME ?></title>
</head>

<body class="m-menubody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-menu">

		<div class="m-pagebase__header">
				<div>
					<h1>Editar Item de Menu</h1>
				</div>
				<a href="<?= url('/admin/menu/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
					<i data-lucide="plus"></i> Novo Item
				</a>
			</div>	
			
			
		<div class="m-menu__form-container">



			<?php if (isset($_SESSION['error'])): ?>
				<div class="m-menu__alert-error">
					<?= htmlspecialchars($_SESSION['error']) ?>
				</div>
				<?php unset($_SESSION['error']); ?>
			<?php endif; ?>

			<form method="POST" action="<?= url('/admin/menu/' . $item['id']) ?>" accept-charset="UTF-8">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<div class="m-menu__form-group">
					<label class="m-menu__form-label">Tipo de Item:</label>
					<select name="type" id="type" class="m-menu__form-select" required>
						<option value="page" <?= $item['type'] === 'page' ? 'selected' : '' ?>>Página Interna</option>
						<option value="module" <?= $item['type'] === 'module' ? 'selected' : '' ?>>Módulo</option>
						<option value="link" <?= $item['type'] === 'link' ? 'selected' : '' ?>>Link Externo</option>
						<option value="category" <?= $item['type'] === 'category' ? 'selected' : '' ?>>Categoria (sem link)</option>
					</select>
				</div>

				<div class="m-menu__form-group">
					<label class="m-menu__form-label">Label (texto exibido):</label>
					<input type="text" name="label" class="m-menu__form-input" value="<?= htmlspecialchars($item['label']) ?>" required>
				</div>

				<div class="m-menu__form-group">
					<label class="m-menu__form-label">Ícone (opcional):</label>
					<input type="text" name="icon" class="m-menu__form-input" value="<?= htmlspecialchars($item['icon'] ?? '') ?>" placeholder="Ex: home, file, book">
					<small class="m-menu__form-help">Nome do ícone Lucide (sem o "data-lucide")</small>
				</div>

				<div class="m-menu__form-group m-menu__conditional" id="field-page">
					<label class="m-menu__form-label">Página:</label>
					<select name="page_slug" class="m-menu__form-select">
						<option value="">Selecione uma página</option>
						<?php foreach ($pages as $page): ?>
							<option value="<?= htmlspecialchars($page['slug']) ?>" <?= $item['page_slug'] === $page['slug'] ? 'selected' : '' ?>>
								<?= htmlspecialchars($page['title']) ?> (<?= htmlspecialchars($page['slug']) ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="m-menu__form-group m-menu__conditional" id="field-module">
					<label class="m-menu__form-label">Módulo:</label>
					<select name="module_name" id="module_name" class="m-menu__form-select">
						<option value="">Selecione um módulo</option>
						<?php foreach ($modules as $module): ?>
							<option value="<?= htmlspecialchars($module['name']) ?>"
								data-url="<?= htmlspecialchars($module['public_url'] ?? '') ?>"
								<?= ($item['module_name'] ?? '') === $module['name'] ? 'selected' : '' ?>>
								<?= htmlspecialchars($module['label']) ?>
								<?php if (!empty($module['description'])): ?>
									- <?= htmlspecialchars($module['description']) ?>
								<?php endif; ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small class="m-menu__form-help">URL será preenchida automaticamente com a URL pública do módulo</small>
				</div>

				<div class="m-menu__form-group m-menu__conditional" id="field-link">
					<label class="m-menu__form-label">URL:</label>
					<input type="text" name="url" class="m-menu__form-input" value="<?= htmlspecialchars($item['url'] ?? '') ?>" placeholder="https://exemplo.com ou /caminho">
				</div>

				<div class="m-menu__form-group">
					<label class="m-menu__form-label">Item Pai (deixe vazio para raiz):</label>
					<select name="parent_id" class="m-menu__form-select">
						<option value="">Nenhum (item raiz)</option>
						<?php foreach ($menuItems as $mi): ?>
							<option value="<?= htmlspecialchars($mi['id']) ?>" <?= $item['parent_id'] === $mi['id'] ? 'selected' : '' ?>>
								<?= htmlspecialchars($mi['label']) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="m-menu__form-group">
					<label class="m-menu__form-label">Visibilidade:</label>
					<select name="permission_type" id="permission_type" class="m-menu__form-select" required>
						<option value="public" <?= $item['permission_type'] === 'public' ? 'selected' : '' ?>>Público (todos podem ver)</option>
						<option value="authenticated" <?= $item['permission_type'] === 'authenticated' ? 'selected' : '' ?>>Apenas usuários autenticados</option>
						<?php if (Core::membersEnabled()): ?>
							<option value="group" <?= $item['permission_type'] === 'group' ? 'selected' : '' ?>>Grupo específico</option>
							<option value="member" <?= $item['permission_type'] === 'member' ? 'selected' : '' ?>>Member específico</option>
						<?php endif; ?>
					</select>
					<small class="m-menu__form-help">Define quem pode ver este item no menu</small>
				</div>

				<?php if (Core::membersEnabled()): ?>
					<div class="m-menu__form-group m-menu__conditional" id="field-group">
						<label class="m-menu__form-label">Grupos (selecione um ou mais):</label>
						<?php
							$selectedGroups = [];
							if (!empty($item['group_id'])) {
								$selectedGroups = explode(',', $item['group_id']);
							}
						?>
						<div class="m-menu__checkbox-list">
							<?php foreach ($groups as $group): ?>
								<div class="m-menu__checkbox-group">
									<input type="checkbox"
										name="group_ids[]"
										id="group_<?= htmlspecialchars($group['id']) ?>"
										value="<?= htmlspecialchars($group['id']) ?>"
										<?= in_array($group['id'], $selectedGroups) ? 'checked' : '' ?>>
									<label for="group_<?= htmlspecialchars($group['id']) ?>">
										<?= htmlspecialchars($group['name']) ?>
									</label>
								</div>
							<?php endforeach; ?>
						</div>
						<small class="m-menu__form-help">Usuários que pertencem a QUALQUER um dos grupos selecionados poderão ver este item</small>
					</div>
				<?php endif; ?>

				<div class="m-menu__form-group">
					<div class="m-menu__checkbox-group">
						<input type="checkbox" name="visible" id="visible" value="1" <?= $item['visible'] ? 'checked' : '' ?>>
						<label for="visible">Visível</label>
					</div>
				</div>

				<button type="submit" class="m-menu__form-submit" id="submitBtn">Salvar Alterações</button>
			</form>

		</div>
	</main>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		const typeSelect = document.getElementById('type');
		const permissionTypeSelect = document.getElementById('permission_type');
		const form = document.querySelector('form');
		const submitBtn = document.getElementById('submitBtn');

		// Prevenir submissão duplicada
		form.addEventListener('submit', function(e) {
			if (form.dataset.submitting === 'true') {
				e.preventDefault();
				return false;
			}
			form.dataset.submitting = 'true';
			submitBtn.disabled = true;
			submitBtn.textContent = 'Salvando...';
		});

		// Mostrar/ocultar campos baseado no tipo
		typeSelect.addEventListener('change', function() {
			// Resetar todos os condicionais
			document.querySelectorAll('.m-menu__conditional').forEach(el => el.style.display = 'none');

			if (this.value === 'page') {
				document.getElementById('field-page').style.display = 'block';
			} else if (this.value === 'module') {
				document.getElementById('field-module').style.display = 'block';
			} else if (this.value === 'link') {
				document.getElementById('field-link').style.display = 'block';
			}

			// Atualizar visibilidade do campo de grupo
			updatePermissionFields();
		});

		// Mostrar/ocultar campos baseado no permission_type
		if (permissionTypeSelect) {
			permissionTypeSelect.addEventListener('change', updatePermissionFields);
		}

		function updatePermissionFields() {
			const fieldGroup = document.getElementById('field-group');

			if (fieldGroup && permissionTypeSelect) {
				if (permissionTypeSelect.value === 'group') {
					fieldGroup.style.display = 'block';
				} else {
					fieldGroup.style.display = 'none';
				}
			}
		}

		// Preencher URL automaticamente quando seleciona módulo
		const moduleSelect = document.getElementById('module_name');
		const urlInput = document.querySelector('input[name="url"]');

		if (moduleSelect) {
			moduleSelect.addEventListener('change', function() {
				const selectedOption = this.options[this.selectedIndex];
				const moduleUrl = selectedOption.getAttribute('data-url');
				if (moduleUrl && urlInput) {
					urlInput.value = moduleUrl;
				}
			});
		}

		// Trigger inicial
		typeSelect.dispatchEvent(new Event('change'));
		updatePermissionFields();

		// Inicializar ícones Lucide
		if (typeof lucide !== 'undefined') {
			lucide.createIcons();
		}
	</script>
</body>
</html>
