<?php
Auth::require();
$user = Auth::user();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '/../includes/_admin-head.php';
	?>
	<title>FTP Sync - <?= ADMIN_NAME ?></title>
</head>

<body>

	<?php require_once __DIR__ . '/../../admin/includes/header.php'; ?>

	<main class="m-pagebase m-ftp-sync">

		<!-- Cabeçalho -->
		<div class="m-pagebase__header">
			<h1><i data-lucide="upload-cloud"></i> FTP Sync</h1>
			<div class="m-pagebase__header-actions">
				<a href="<?= url('/admin') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
					<i data-lucide="arrow-left"></i> Voltar
				</a>
			</div>
		</div>

		<!-- Mensagens -->
		<?php if ($success): ?>
			<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
		<?php endif; ?>
		<?php if ($error): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<!-- Status FTP -->
		<div class="m-ftp-sync__status">
			<?php if ($ftpConfigured): ?>
				<div class="m-ftp-sync__status-ok">
					<i data-lucide="check-circle"></i>
					<span>FTP Configurado</span>
					<a href="<?= url('/admin/settings') ?>" class="m-ftp-sync__status-link">Editar</a>
				</div>
			<?php else: ?>
				<div class="m-ftp-sync__status-error">
					<i data-lucide="alert-circle"></i>
					<span>FTP não configurado</span>
					<a href="<?= url('/admin/settings') ?>" class="m-ftp-sync__status-link">Configurar agora</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- Arquivos Modificados -->
		<div class="m-ftp-sync__section">
			<div class="m-ftp-sync__section-header">
				<h2><i data-lucide="file-text"></i> Arquivos Modificados (últimos 7 dias)</h2>
				<div class="m-ftp-sync__section-actions">
					<button type="button" id="select-all" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
						<i data-lucide="check-square"></i> Selecionar Todos
					</button>
					<button type="button" id="unselect-all" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--builder">
						<i data-lucide="square"></i> Desmarcar Todos
					</button>
					<button type="button" id="upload-selected" class="m-pagebase__btn m-pagebase__btn--widthauto" <?= !$ftpConfigured ? 'disabled' : '' ?>>
						<i data-lucide="upload"></i> Enviar Selecionados
					</button>
				</div>
			</div>

			<?php if (empty($modifiedFiles)): ?>
				<div class="m-ftp-sync__empty">
					<i data-lucide="folder-open"></i>
					<p>Nenhum arquivo modificado nos últimos 7 dias</p>
				</div>
			<?php else: ?>
				<div class="m-ftp-sync__files">
					<table class="m-ftp-sync__table">
						<thead>
							<tr>
								<th width="40"><input type="checkbox" id="select-all-checkbox"></th>
								<th>Arquivo</th>
								<th width="100">Tamanho</th>
								<th width="150">Modificado</th>
								<th width="80">Tipo</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($modifiedFiles as $file): ?>
								<tr>
									<td>
										<input type="checkbox" class="file-checkbox" value="<?= htmlspecialchars($file['path']) ?>">
									</td>
									<td class="m-ftp-sync__file-path">
										<i data-lucide="file"></i>
										<span><?= htmlspecialchars($file['path']) ?></span>
									</td>
									<td class="m-ftp-sync__file-size">
										<?= number_format($file['size'] / 1024, 2) ?> KB
									</td>
									<td class="m-ftp-sync__file-date">
										<?= htmlspecialchars($file['modified']) ?>
									</td>
									<td class="m-ftp-sync__file-ext">
										<?= htmlspecialchars(strtoupper($file['extension'])) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="m-ftp-sync__summary">
						<strong><?= count($modifiedFiles) ?></strong> arquivo(s) modificado(s)
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Histórico de Transferências -->
		<div class="m-ftp-sync__section">
			<div class="m-ftp-sync__section-header">
				<h2><i data-lucide="history"></i> Histórico de Transferências</h2>
				<div class="m-ftp-sync__section-actions">
					<form method="POST" action="<?= url('/admin/ftp-sync/clean-logs') ?>" style="display: inline;" onsubmit="return confirm('Limpar logs antigos (+ 30 dias)?')">
						<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
						<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--danger">
							<i data-lucide="trash-2"></i> Limpar Logs Antigos
						</button>
					</form>
				</div>
			</div>

			<?php if (empty($history)): ?>
				<div class="m-ftp-sync__empty">
					<i data-lucide="inbox"></i>
					<p>Nenhuma transferência realizada ainda</p>
				</div>
			<?php else: ?>
				<div class="m-ftp-sync__history">
					<table class="m-ftp-sync__table">
						<thead>
							<tr>
								<th>Arquivo</th>
								<th width="100">Ação</th>
								<th width="100">Status</th>
								<th width="100">Tamanho</th>
								<th width="150">Data</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($history as $log): ?>
								<tr>
									<td class="m-ftp-sync__file-path">
										<i data-lucide="file"></i>
										<span><?= htmlspecialchars($log['file_path']) ?></span>
									</td>
									<td>
										<?php if ($log['action'] === 'upload'): ?>
											<span class="m-ftp-sync__badge m-ftp-sync__badge--upload">Upload</span>
										<?php elseif ($log['action'] === 'download'): ?>
											<span class="m-ftp-sync__badge m-ftp-sync__badge--download">Download</span>
										<?php else: ?>
											<span class="m-ftp-sync__badge m-ftp-sync__badge--delete">Delete</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($log['status'] === 'success'): ?>
											<span class="m-ftp-sync__badge m-ftp-sync__badge--success">Sucesso</span>
										<?php else: ?>
											<span class="m-ftp-sync__badge m-ftp-sync__badge--error">Erro</span>
										<?php endif; ?>
									</td>
									<td class="m-ftp-sync__file-size">
										<?= number_format($log['file_size'] / 1024, 2) ?> KB
									</td>
									<td class="m-ftp-sync__file-date">
										<?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>

	</main>

	<?php require_once __DIR__ . '/../includes/footer.php'; ?>

	<script>
	// Selecionar/Desmarcar todos
	document.getElementById('select-all')?.addEventListener('click', () => {
		document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = true);
	});

	document.getElementById('unselect-all')?.addEventListener('click', () => {
		document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
	});

	document.getElementById('select-all-checkbox')?.addEventListener('change', (e) => {
		document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = e.target.checked);
	});

	// Upload selecionados
	document.getElementById('upload-selected')?.addEventListener('click', async () => {
		const selected = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);

		if (selected.length === 0) {
			alert('Selecione ao menos 1 arquivo');
			return;
		}

		if (!confirm(`Enviar ${selected.length} arquivo(s) via FTP?`)) {
			return;
		}

		const btn = document.getElementById('upload-selected');
		btn.disabled = true;
		btn.innerHTML = '<i data-lucide="loader"></i> Enviando...';
		lucide.createIcons();

		try {
			const response = await fetch('<?= url('/admin/ftp-sync/upload') ?>', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					csrf_token: '<?= Security::generateCSRF() ?>',
					files: selected
				})
			});

			const result = await response.json();

			if (result.success) {
				alert(result.message);
				location.reload();
			} else {
				alert('Erro: ' + result.message);
				btn.disabled = false;
				btn.innerHTML = '<i data-lucide="upload"></i> Enviar Selecionados';
				lucide.createIcons();
			}
		} catch (error) {
			alert('Erro na requisição: ' + error.message);
			btn.disabled = false;
			btn.innerHTML = '<i data-lucide="upload"></i> Enviar Selecionados';
			lucide.createIcons();
		}
	});
	</script>

</body>
</html>
