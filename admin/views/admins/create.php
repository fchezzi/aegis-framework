<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Novo Administrador - <?= ADMIN_NAME ?></title>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

    <div class="m-pagebase__header">
      <h1>Novo Admin</h1>
      <a href="<?= url('/admin/admins') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
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

			<form method="POST" action="<?= url('/admin/admins') ?>">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Nome:</label>
					<input type="text" name="name" class="m-admins__form-input" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Email:</label>
					<input type="email" name="email" class="m-admins__form-input" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Senha:</label>
					<input type="password" name="password" class="m-admins__form-input" required>
					<small class="m-admins__form-help">Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 especial</small>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-checkbox">
						<input type="checkbox" name="ativo" value="1" checked>
						Ativo
					</label>
					<small class="m-admins__form-help">Administradores inativos não conseguem fazer login</small>
				</div>

				<button type="submit" class="m-admins__form-submit">
					<i data-lucide="save"></i> Criar Administrador
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
