<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Administrador - <?= ADMIN_NAME ?></title>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

    <div class="m-pagebase__header">
      <h1>Editar Admin</h1>
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

			<form method="POST" action="<?= url('/admin/admins/' . $admin['id']) ?>">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Nome:</label>
					<input type="text" name="name" class="m-admins__form-input" value="<?= htmlspecialchars($admin['name']) ?>" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Email:</label>
					<input type="email" name="email" class="m-admins__form-input" value="<?= htmlspecialchars($admin['email']) ?>" required>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Nova Senha (deixe vazio para manter):</label>
					<input type="password" name="password" class="m-admins__form-input">
					<small class="m-admins__form-help">Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 especial</small>
				</div>

				<div class="m-admins__form-group">
					<label class="m-admins__form-label">Status:</label>
					<select name="ativo" class="m-admins__form-select">
						<option value="1" <?= $admin['ativo'] == 1 ? 'selected' : '' ?>>Ativo</option>
						<option value="0" <?= $admin['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
					</select>
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
