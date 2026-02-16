<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../../includes/_admin-head.php';
	?>
	<title>Sistema de CRUDs - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<div class="m-pagebase">

		<div class="m-pagebase__header">
			<h1>Sistema de CRUDs</h1>
			<a href="<?= url('/admin/cruds/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">+ Criar Novo CRUD</a>
		</div>

		<?php if (isset($_SESSION['success'])): ?>
			<div class="alert alert--success"><?= htmlspecialchars($_SESSION['success']) ?></div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<?php if (empty($cruds)): ?>
			<div class="m-pagebase__empty">
				<p>Nenhum CRUD cadastrado ainda.</p>
				<a href="<?= url('/admin/cruds/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">Criar Primeiro CRUD</a>
			</div>
		<?php else: ?>
			<table class="m-pagebase__table">
				<thead>
					<tr>
						<th>Nome</th>
						<th>Tabela</th>
						<th>Campos</th>
						<th>Status</th>
						<th>Criado em</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($cruds as $crud): ?>
					<tr>
						<td><strong><?= htmlspecialchars($crud['name']) ?></strong></td>
						<td><code><?= htmlspecialchars($crud['table_name']) ?></code></td>
						<td><?= (int)$crud['fields_count'] ?> campos</td>
						<td>
							<?php if ($crud['status'] === 'generated' || $crud['status'] === 'active'): ?>
								<span class="m-pagebase__badge m-pagebase__badge--success">GERADO</span>
							<?php elseif ($crud['status'] === 'draft'): ?>
								<span class="m-pagebase__badge m-pagebase__badge--warning">RASCUNHO</span>
							<?php else: ?>
								<span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
							<?php endif; ?>
						</td>
						<td><?= date('d/m/Y H:i', strtotime($crud['created_at'])) ?></td>
						<td class="m-pagebase__actions">
							<a href="<?= url('/admin/' . htmlspecialchars($crud['route'])) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto"><i data-lucide="external-link"></i> Acessar</a>
							<form method="POST" action="<?= url('/admin/cruds/' . htmlspecialchars($crud['id']) . '/delete') ?>" style="display:inline;">
								<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
								<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--danger" onclick="return confirm('⚠️ ATENÇÃO!\n\nIsso vai deletar TUDO:\n- Tabela do banco de dados\n- Todos os arquivos gerados (controller, views, frontend)\n- Todos os registros e uploads\n- Rotas\n\nTem CERTEZA?')"><i data-lucide="trash-2"></i> Deletar Tudo</button>
							</form>
						</td>
					</tr>
				
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

	</div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>
</html>
