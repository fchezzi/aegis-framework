<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../../includes/_admin-head.php';
	?>
	<title>BigBanners - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<div class="m-pagebase">

		<div class="m-pagebase__header">
			<h1>BigBanners (<?= $total ?>)</h1>
			<a href="<?= url('/admin/bigbanner/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">+ Novo</a>
		</div>

		<?php if (isset($_SESSION['success'])): ?>
			<div class="alert alert--success"><?= htmlspecialchars($_SESSION['success']) ?></div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<!-- Como usar no frontend -->
		<div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
			<div style="margin-bottom: 10px;">
				<strong style="color: #0369a1; font-size: 15px;">💡 Como usar no frontend</strong>
				<span style="margin-left: 15px; color: #64748b; font-size: 13px;">Formato: <strong>carousel</strong></span>
			</div>
			<div style="margin-bottom: 10px;">
				<strong style="color: #475569; font-size: 13px;">Implementação:</strong>
			</div>
			<pre style="background: #fff; padding: 12px; margin: 0 0 10px 0; border-radius: 4px; overflow-x: auto; border: 1px solid #cbd5e1; font-size: 13px;"><code>&lt;?php Core::requireInclude('frontend/views/partials/bigbanner.php'); ?&gt;</code></pre>
			<div style="margin-bottom: 10px;">
				<strong style="color: #475569; font-size: 13px;">Personalização (SASS):</strong>
			</div>
			<pre style="background: #fff; padding: 12px; margin: 0; border-radius: 4px; overflow-x: auto; border: 1px solid #cbd5e1; font-size: 13px;"><code>assets/sass/frontend/components/_bigbanner.sass</code></pre>
		</div>


		<?php if (!empty($registros)): ?>
		<table class="m-pagebase__table">
			<thead>
				<tr>
					<th>Imagem</th>
					<th>Title</th>
					<th>Subtitle</th>
					<th>Cta</th>
					<th>Cta link</th>
					<th>Ordem</th>
					<th>Status</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($registros as $item): ?>
				<tr>
					<td>
						<?php if (!empty($item['iamge'])): ?>
							<img src="<?= url($item['iamge']) ?>" class="m-pagebase__thumb" alt="" />
						<?php else: ?>
							<span>—</span>
						<?php endif; ?>
					</td>
					<td><?= htmlspecialchars($item['title'] ?? '—') ?></td>
					<td><?= htmlspecialchars($item['subtitle'] ?? '—') ?></td>
					<td><?= htmlspecialchars($item['cta'] ?? '—') ?></td>
					<td><?= htmlspecialchars($item['cta_link'] ?? '—') ?></td>
					<td><?= htmlspecialchars($item['order']) ?></td>
					<td>
						<?php if ($item['ativo']): ?>
							<span class="m-pagebase__badge m-pagebase__badge--success">ATIVO</span>
						<?php else: ?>
							<span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
						<?php endif; ?>
					</td>
					<td class="m-pagebase__actions">
						<a href="<?= url('/admin/bigbanner/' . htmlspecialchars($item['id']) . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--edit"><i data-lucide="pencil"></i> Editar</a>
						<form method="POST" action="<?= url('/admin/bigbanner/' . htmlspecialchars($item['id']) . '/delete') ?>" style="display:inline;">
							<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
							<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--danger" onclick="return confirm('Tem certeza?')"><i data-lucide="trash-2"></i> Deletar</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Paginação -->
		<?php if ($totalPages > 1): ?>
		<div class="m-pagebase__pagination">
			<?php if ($page > 1): ?>
				<a href="<?= url('/admin/bigbanner?page=' . ($page - 1) . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>" class="m-pagebase__btn m-pagebase__btn--sm">← Anterior</a>
			<?php endif; ?>

			<span>Página <?= $page ?> de <?= $totalPages ?></span>

			<?php if ($page < $totalPages): ?>
				<a href="<?= url('/admin/bigbanner?page=' . ($page + 1) . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>" class="m-pagebase__btn m-pagebase__btn--sm">Próxima →</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php else: ?>
			<p class="m-pagebase__empty">Nenhum registro encontrado. <a href="<?= url('/admin/bigbanner/create') ?>">Criar o primeiro?</a></p>
		<?php endif; ?>

	</div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>
</html>
