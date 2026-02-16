<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Relatórios - <?= ADMIN_NAME ?></title>
</head>

<body class="m-reportsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-pagebase">

		<div class="m-pagebase__header">
			<h1>Relatórios Excel</h1>
			<a href="<?= url('/admin/reports/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
				<i data-lucide="plus"></i> Novo Template
			</a>
		</div>

		<?php if (isset($_SESSION['success'])): ?>
			<div class="alert alert--success">
				<?= htmlspecialchars($_SESSION['success']) ?>
			</div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error">
				<?= htmlspecialchars($_SESSION['error']) ?>
			</div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<?php if (empty($templates)): ?>
			<div class="m-pagebase__empty">
				<p><strong>Nenhum template de relatório criado ainda.</strong></p>
				<p>Crie templates Excel personalizados com mapeamento automático de dados.</p>
				<a href="<?= url('/admin/reports/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
					<i data-lucide="file-plus"></i> Criar Primeiro Template
				</a>
			</div>
		<?php else: ?>
			<table class="m-pagebase__table">
				<thead>
					<tr>
						<th>Nome</th>
						<th>Descrição</th>
						<th>Arquivo</th>
						<th>Visibilidade</th>
						<th>Criado em</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($templates as $template): ?>
						<tr>
							<td><strong><?= htmlspecialchars($template['name']) ?></strong></td>
							<td><?= htmlspecialchars($template['description'] ?? '-') ?></td>
							<td><?= basename($template['file_path']) ?></td>
							<td>
								<?php if ($template['visible']): ?>
									<span class="m-pagebase__badge m-pagebase__badge--success m-pagebase__btn--widthauto">
										Visível
									</span>
								<?php else: ?>
									<span class="m-pagebase__badge m-pagebase__badge--secondary">
										Oculto
									</span>
								<?php endif; ?>
							</td>
							<td><?= date('d/m/Y H:i', strtotime($template['created_at'])) ?></td>
							<td class="m-pagebase__actions">
								<a href="<?= url('/admin/reports/' . $template['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
									<i data-lucide="pencil"></i> Editar
								</a>
								<form method="POST" action="<?= url('/admin/reports/' . $template['id'] . '/delete') ?>">
									<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
									<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o template:&#10;<?= htmlspecialchars($template['name']) ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
										<i data-lucide="trash-2"></i> Deletar
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</main>

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>

</body>
</html>
