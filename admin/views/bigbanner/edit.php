<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar BigBanner - <?= ADMIN_NAME ?></title>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

    <div class="m-pagebase__header">
      <h1>Editar BigBanner</h1>
      <a href="<?= url('/admin/bigbanner') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
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

			<form method="POST" action="<?= url('/admin/bigbanner/' . htmlspecialchars($registro['id'])) ?>" enctype="multipart/form-data">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Image *</label>
					<?php if (!empty($registro['iamge'])): ?>
						<img src="<?= url($registro['iamge']) ?>" style="max-width: 200px; margin-bottom: 10px; display: block;" />
						<small class="m-admins__form-help">Arquivo atual</small>
					<?php endif; ?>
					<input type="file" name="iamge" class="m-admins__form-input">
					<small class="m-admins__form-help">Deixe vazio para manter o arquivo atual</small>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Title *</label>
					<input type="text" name="title" class="m-admins__form-input" value="<?= htmlspecialchars($registro['title'] ?? '') ?>" maxlength="255" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Subtitle</label>
					<input type="text" name="subtitle" class="m-admins__form-input" value="<?= htmlspecialchars($registro['subtitle'] ?? '') ?>" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Cta</label>
					<input type="text" name="cta" class="m-admins__form-input" value="<?= htmlspecialchars($registro['cta'] ?? '') ?>" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Cta link</label>
					<input type="text" name="cta_link" class="m-admins__form-input" value="<?= htmlspecialchars($registro['cta_link'] ?? '') ?>" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Ordem</label>
					<input type="number" name="order" class="m-admins__form-input" value="<?= htmlspecialchars($registro['order']) ?>">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-checkbox">
						<input type="checkbox" name="ativo" value="1" <?= $registro['ativo'] ? 'checked' : '' ?>>
						Ativo
					</label>
				</div>

				<button type="submit" class="m-admins__form-submit">
					<i data-lucide="save"></i> Salvar Alterações
				</button>
			</form>

		</div>

	</main>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>

</body>
</html>
