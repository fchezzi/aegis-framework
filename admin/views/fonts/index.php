<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Fontes Customizadas - <?= ADMIN_NAME ?></title>
</head>

<body class="m-fontsbody">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

	<main class="m-fonts">

		<div class="m-pagebase__header">
			<h1>Fontes Customizadas (<?= $stats['total_fonts'] ?>)</h1>
			<a href="<?= url('/admin/settings') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
				<i data-lucide="arrow-left"></i> Voltar para Settings
			</a>
		</div>

		<!-- Mensagens -->
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

		<!-- Estatísticas -->
		<div class="m-fonts__stats">
			<div class="m-fonts__stat-card">
				<div class="m-fonts__stat-value"><?= $stats['total_fonts'] ?></div>
				<div class="m-fonts__stat-label">Fontes instaladas</div>
			</div>
			<div class="m-fonts__stat-card">
				<div class="m-fonts__stat-value"><?= $stats['total_families'] ?></div>
				<div class="m-fonts__stat-label">Famílias de fontes</div>
			</div>
			<div class="m-fonts__stat-card">
				<div class="m-fonts__stat-value"><?= number_format($stats['total_size'] / 1024, 0) ?> KB</div>
				<div class="m-fonts__stat-label">Espaço em disco</div>
			</div>
		</div>

		<!-- Card de Upload -->
		<div class="m-fonts__upload-card">
			<form method="POST" action="<?= url('/admin/fonts') ?>" enctype="multipart/form-data" class="m-fonts__upload-form">
				<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

				<h3 class="m-fonts__upload-title">
					<i data-lucide="upload"></i> Enviar Nova Fonte
				</h3>

				<div class="m-fonts__file-input-wrapper">
					<input
						type="file"
						name="font_file"
						id="font_file"
						accept=".woff2,font/woff2"
						required
						class="m-fonts__file-input"
						onchange="document.getElementById('file-name-display').textContent = this.files[0]?.name || 'Nenhum arquivo selecionado'"
					>
					<label for="font_file" class="m-fonts__file-label">
						<i data-lucide="file-plus"></i> Escolher arquivo WOFF2
					</label>
				</div>

				<div id="file-name-display" class="m-fonts__file-name">Nenhum arquivo selecionado</div>

				<div class="m-fonts__upload-row">
					<input
						type="text"
						name="custom_name"
						placeholder="Nome customizado (opcional)"
						class="m-pagebase__form-input m-fonts__upload-input"
					>
					<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
						<i data-lucide="upload"></i> Enviar Fonte
					</button>
				</div>

				<p class="m-fonts__upload-help">
					Tamanho máximo: 2MB | Formato: WOFF2 apenas
				</p>
			</form>
		</div>

		<!-- Fontes Agrupadas por Família -->
		<?php if (empty($fontsByFamily)): ?>
			<div class="m-fonts__upload-card m-fonts__empty-card">
				<div class="m-fonts__empty-state">
					<i data-lucide="info"></i>
					<p>Nenhuma fonte instalada ainda. Envie sua primeira fonte WOFF2!</p>
				</div>
			</div>
		<?php else: ?>
			<?php foreach ($fontsByFamily as $family => $familyFonts): ?>
				<div class="m-fonts__family-group">
					<div class="m-fonts__family-header">
						<?= htmlspecialchars($family) ?>
						<span>(<?= count($familyFonts) ?> <?= count($familyFonts) === 1 ? 'variação' : 'variações' ?>)</span>
					</div>

					<table class="m-admins__table m-fonts__table">
						<thead>
							<tr>
								<th>Nome</th>
								<th>Weight</th>
								<th>Style</th>
								<th>Tamanho</th>
								<th>Upload</th>
								<th>Preview</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($familyFonts as $font): ?>
								<tr>
									<td><strong><?= htmlspecialchars($font['name']) ?></strong></td>
									<td><?= htmlspecialchars($font['weight']) ?></td>
									<td><?= htmlspecialchars($font['style']) ?></td>
									<td><?= number_format($font['file_size'] / 1024, 1) ?> KB</td>
									<td><?= date('d/m/Y H:i', strtotime($font['uploaded_at'])) ?></td>
									<td>
										<div class="m-fonts__preview-text" style="font-family: '<?= addslashes($font['family']) ?>', sans-serif; font-weight: <?= $font['weight'] ?>; font-style: <?= $font['style'] ?>;">
											ABCDEFGHIJKLM abcdefghijklm 0123456789
										</div>
									</td>
									<td>
										<div class="m-fonts__actions">
											<a href="<?= url('/admin/fonts/' . $font['id'] . '/download') ?>" class="m-pagebase__btn m-pagebase__btn--sm" title="Download">
												<i data-lucide="download"></i>
											</a>
											<form method="POST" action="<?= url('/admin/fonts/' . $font['id'] . '/delete') ?>" class="m-fonts__form-inline">
												<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
												<button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" onclick="return confirm('⚠️ ATENÇÃO!\n\nDeletar a fonte:\n<?= htmlspecialchars($font['name']) ?>\n(<?= htmlspecialchars($font['filename']) ?>)\n\nEsta ação NÃO pode ser desfeita!')" title="Deletar">
													<i data-lucide="trash-2"></i>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	</main>

	<!-- Load @font-face para preview -->
	<link rel="stylesheet" href="<?= url('/assets/fonts.php') ?>">

	<script src="https://unpkg.com/lucide@latest"></script>
	<script>
		lucide.createIcons();
	</script>

</body>
</html>
