<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Novo BigBanner - <?= ADMIN_NAME ?></title>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

    <div class="m-pagebase__header">
      <h1>Novo BigBanner</h1>
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

			<form method="POST" action="<?= url('/admin/bigbanner') ?>" enctype="multipart/form-data">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Image *</label>
					<input type="file" name="iamge" class="m-admins__form-input" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Title *</label>
					<input type="text" name="title" class="m-admins__form-input" maxlength="255" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Subtitle</label>
					<input type="text" name="subtitle" class="m-admins__form-input" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Cta</label>
					<input type="text" name="cta" class="m-admins__form-input" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Cta link</label>
					<input type="text" name="cta_link" class="m-admins__form-input" maxlength="255">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Ordem</label>
					<input type="number" name="order" class="m-admins__form-input" value="0">
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-checkbox">
						<input type="checkbox" name="ativo" value="1" checked>
						Ativo
					</label>
				</div>

				<button type="submit" class="m-admins__form-submit">
					<i data-lucide="save"></i> Criar BigBanner
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
