<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Administradores - <?= ADMIN_NAME ?></title>
</head>

<body class="m-adminsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-admins">

		<div class="m-pagebase__header">
			<div>
				<h1>Administradores</h1>
			</div>
			<a href="<?= url('/admin/admins/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
				<i data-lucide="user-plus"></i> Novo Administrador
			</a>
		</div>

		<?php if (isset($_SESSION['success'])): ?>
			<div class="m-admins__alert-success">
				<?= htmlspecialchars($_SESSION['success']) ?>
			</div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="m-admins__alert-error">
				<?= htmlspecialchars($_SESSION['error']) ?>
			</div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<table class="m-admins__table">
			<thead>
				<tr>
					<th>Nome</th>
					<th>Email</th>
					<th>Status</th>
					<th>Criado em</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($admins as $admin): ?>
					<tr>
						<td><?= htmlspecialchars($admin['name']) ?></td>
						<td><?= htmlspecialchars($admin['email']) ?></td>
						<td>
							<?php if ($admin['ativo']): ?>
								<span class="m-admins__badge m-admins__badge--active">
									<i data-lucide="check-circle"></i> Ativo
								</span>
							<?php else: ?>
								<span class="m-admins__badge m-admins__badge--inactive">
									<i data-lucide="x-circle"></i> Inativo
								</span>
							<?php endif; ?>
						</td>
						<td><?= date('d/m/Y H:i', strtotime($admin['created_at'])) ?></td>
						<td>
							<div class="m-admins__actions">
								<a href="<?= url('/admin/admins/' . $admin['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
									<i data-lucide="edit-2"></i> Editar
								</a>
								<form method="POST" action="<?= url('/admin/admins/' . $admin['id'] . '/delete') ?>" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
									<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o administrador:&#10;<?= htmlspecialchars($admin['name']) ?>&#10;(<?= htmlspecialchars($admin['email']) ?>)&#10;&#10;Esta ação NÃO pode ser desfeita!">
										<i data-lucide="trash-2"></i> Deletar
									</button>
								</form>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="m-modules__back-container">
			<a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn-secondary">
				<i data-lucide="arrow-left"></i> Voltar ao Dashboard
			</a>
		</div>

	</main>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>

</body>
</html>
