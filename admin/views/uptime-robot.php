<?php
$user = Auth::user();
?>

<!DOCTYPE html>
<html lang="pt-BR">

	<head>
		<?php
		$loadAdminJs = true;
		require_once __DIR__ . '../../includes/_admin-head.php';
		?>
		<title>Páginas - <?= ADMIN_NAME ?></title>
	</head>

	<body class="m-pagebasebody">

  	<?php require_once __DIR__ . '../../includes/header.php'; ?>

		<main class="uptime-container">

			<?php if (isset($_SESSION['success'])): ?>
				<div class="alert alert--success"><?= $_SESSION['success'] ?></div>
				<?php unset($_SESSION['success']); ?>
			<?php endif; ?>

			<?php if (isset($_SESSION['error'])): ?>
				<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
				<?php unset($_SESSION['error']); ?>
			<?php endif; ?>

			<!-- breadcrumb e btns -->
			<div class="m-pagebase__header">
				<h1>UptimeRobot</h1>
				<form method="POST" action="<?= url('/admin/uptime-robot/sync') ?>">
					<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
					<button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
						<i data-lucide="refresh-cw" class="btn__lucide"></i>
						Sincronizar
					</button>
				</form>										
			</div>			

			<?php if ($has_data): ?>
				
				<!-- Summary Cards -->
				<div class="summary-grid">

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="activity" class="summary-card__lucide"></i>
							Total de Monitores
						</div>
						<div class="summary-card__value"><?= $summary['total_monitors'] ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="check-circle" class="summary-card__lucide"></i>
							Online Agora
						</div>
						<div class="summary-card__value summary-card__value--success"><?= $summary['online_now'] ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="alert-triangle" class="summary-card__lucide"></i>
							Incidentes (30d)
						</div>
						<div class="summary-card__value"><?= $summary['incidents_month'] ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="trending-up" class="summary-card__lucide"></i>
							Uptime Médio
						</div>
						<div class="summary-card__value summary-card__value--success"><?= $summary['avg_uptime'] ?>%</div>
					</div>

				</div>

				<!-- Monitor Cards -->
				<div class="uptime-grid">
					<?php foreach ($stats as $monitor): ?>
					<div class="uptime-card uptime-card--<?= $monitor['status'] === 2 ? 'online' : ($monitor['status'] === 0 ? 'paused' : 'offline') ?>">
						<div class="uptime-card__header">
							<div class="uptime-card__icon uptime-card__icon--<?= $monitor['status'] === 2 ? 'online' : ($monitor['status'] === 0 ? 'paused' : 'offline') ?>">
								<i data-lucide="<?= $monitor['status'] === 2 ? 'check-circle' : ($monitor['status'] === 0 ? 'pause-circle' : 'x-circle') ?>" class="uptime-card__lucide"></i>
							</div>
							<div class="uptime-card__title">
								<h3 class="uptime-card__name"><?= htmlspecialchars($monitor['name']) ?></h3>
								<p class="uptime-card__url"><?= htmlspecialchars($monitor['url']) ?></p>
								<?php if ($monitor['last_check']): ?>
									<p class="uptime-card__meta">
										Último check: <?= date('d/m H:i', $monitor['last_check']['datetime']) ?> - <?= $monitor['last_check']['value'] ?>ms
									</p>
								<?php endif; ?>
							</div>
						</div>

						<div class="uptime-card__badge-wrapper">
							<span class="uptime-card__badge uptime-card__badge--<?= $monitor['status'] === 2 ? 'online' : ($monitor['status'] === 0 ? 'paused' : 'offline') ?>">
								<?= htmlspecialchars($monitor['status_text']) ?>
							</span>
						</div>

						<?php if (!empty($monitor['sparkline_data'])): ?>
							<canvas class="uptime-card__sparkline" data-sparkline='<?= json_encode($monitor['sparkline_data']) ?>'></canvas>
						<?php endif; ?>

						<div class="uptime-card__stats">
							<div class="uptime-card__stat">
								<div class="uptime-card__stat-value uptime-card__stat-value--good">
									<?= $monitor['uptime_percent'] ?>%
								</div>
								<div class="uptime-card__stat-label">Uptime (30d)</div>
							</div>
							<div class="uptime-card__stat">
								<div class="uptime-card__stat-value uptime-card__stat-value--ok">
									<?= $monitor['avg_response_time'] ?>ms
								</div>
								<div class="uptime-card__stat-label">Tempo Médio</div>
							</div>
						</div>

						<?php if ($monitor['min_response'] > 0 && $monitor['max_response'] > 0): ?>
							<div class="uptime-card__minmax">
								<span><i data-lucide="arrow-down"></i> Mín: <?= $monitor['min_response'] ?>ms</span>
								<span><i data-lucide="arrow-up"></i> Máx: <?= $monitor['max_response'] ?>ms</span>
							</div>
						<?php endif; ?>

						<?php if ($monitor['last_incident']): ?>
							<div class="uptime-card__incident">
								<strong><i data-lucide="alert-triangle"></i> Último incidente:</strong><br>
								<?= date('d/m/Y H:i', $monitor['last_incident']['datetime']) ?> -
								Duração: <?= gmdate('H:i:s', $monitor['last_incident']['duration']) ?>
								<?php if (!empty($monitor['last_incident']['reason_detail'])): ?>
									<br><?= htmlspecialchars($monitor['last_incident']['reason_detail']) ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="empty-state">
					<i data-lucide="database" class="empty-state__lucide"></i>
					<h3 class="empty-state__title">Nenhum dado sincronizado</h3>
					<p class="empty-state__text">
						Clique em "Sincronizar" para buscar os dados dos seus monitores UptimeRobot
					</p>
					<form method="POST" action="<?= url('/admin/uptime-robot/sync') ?>">
						<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
						<button type="submit" class="btn btn--primary">
							<i data-lucide="refresh-cw" class="btn__lucide"></i>
							Sincronizar Agora
						</button>
					</form>
				</div>
			<?php endif; ?>

		</main>

		<script src="https://unpkg.com/lucide@latest"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
		<script>
			// Executar apenas quando DOM estiver totalmente carregado
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initCharts);
			} else {
				initCharts();
			}

			function initCharts() {
				// Prevenir múltiplas inicializações (CodeKit auto-refresh)
				if (window.chartsInitialized) return;
				window.chartsInitialized = true;

				lucide.createIcons();

				// Sparklines
				document.querySelectorAll('[data-sparkline]').forEach(canvas => {
					// Pular se já foi inicializado
					if (canvas.dataset.initialized) return;
					canvas.dataset.initialized = 'true';

					const data = JSON.parse(canvas.dataset.sparkline);

					if (!data || data.length === 0) {
						canvas.style.display = 'none';
						return;
					}

					new Chart(canvas, {
						type: 'line',
						data: {
							labels: data.map((_, i) => ''),
							datasets: [{
								data: data,
								borderColor: '#3b82f6',
								backgroundColor: 'rgba(59, 130, 246, 0.1)',
								borderWidth: 2,
								fill: true,
								tension: 0.4,
								pointRadius: 0
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: {
								legend: { display: false },
								tooltip: { enabled: false }
							},
							scales: {
								x: { display: false },
								y: { display: false }
							}
						}
					});
				});
			}
		</script>

	</body>

</html>
