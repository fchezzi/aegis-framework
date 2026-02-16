<?php
$user = Auth::user();
?>

<!DOCTYPE html>
<html lang="pt-BR">

	<head>
		<?php
		$loadAdminJs = true;
		require_once __DIR__ . '/../includes/_admin-head.php';
		?>
		<title>Google Search Console - <?= ADMIN_NAME ?></title>
	</head>

	<body class="m-pagebasebody">

		<?php require_once __DIR__ . '/../includes/header.php'; ?>

		<main class="gsc-container">

			<?php if (isset($_SESSION['success'])): ?>
				<div class="alert alert--success"><?= $_SESSION['success'] ?></div>
				<?php unset($_SESSION['success']); ?>
			<?php endif; ?>

			<?php if (isset($_SESSION['error'])): ?>
				<div class="alert alert--error"><?= htmlspecialchars($_SESSION['error']) ?></div>
				<?php unset($_SESSION['error']); ?>
			<?php endif; ?>

			<!-- Header -->
			<div class="gsc-header">
				<div>
					<h2 class="gsc-header__title"><i data-lucide="bar-chart-3"></i> Google Search Console</h2>
					<p class="gsc-header__subtitle">Dados de busca orgânica e performance no Google</p>
				</div>
				<div class="gsc-header__actions">
					<?php if ($has_credentials): ?>
					<form method="POST" action="<?= url('/admin/search-console/sync') ?>">
						<input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
						<button type="submit" class="btn btn--primary">
							<i data-lucide="refresh-cw" class="btn__lucide"></i>
							Sincronizar
						</button>
					</form>
					<?php endif; ?>
					<a href="<?= url('/admin/dashboard') ?>" class="btn btn--secondary">
						<i data-lucide="arrow-left" class="btn__lucide"></i>
						Voltar
					</a>
				</div>
			</div>

			<?php if (!$has_credentials): ?>
				<!-- Empty State: Sem credenciais -->
				<div class="empty-state">
					<i data-lucide="key" class="empty-state__lucide"></i>
					<h3 class="empty-state__title">Credenciais não configuradas</h3>
					<p class="empty-state__text">
						Para usar o Google Search Console API, você precisa:<br><br>
						1. Criar Service Account no Google Cloud Console<br>
						2. Baixar arquivo JSON de credenciais<br>
						3. Fazer upload para: <code>/config/google-service-account.json</code><br>
						4. Adicionar Service Account como Owner no Search Console
					</p>
				</div>

			<?php elseif ($has_data): ?>
				<!-- Summary Cards -->
				<div class="summary-grid">
					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="search" class="summary-card__lucide"></i>
							Keywords (30d)
						</div>
						<div class="summary-card__value"><?= number_format($summary['total_keywords'], 0, ',', '.') ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="mouse-pointer-click" class="summary-card__lucide"></i>
							Clicks (30d)
						</div>
						<div class="summary-card__value summary-card__value--success"><?= number_format($summary['total_clicks'], 0, ',', '.') ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="eye" class="summary-card__lucide"></i>
							Impressões (30d)
						</div>
						<div class="summary-card__value summary-card__value--primary"><?= number_format($summary['total_impressions'], 0, ',', '.') ?></div>
					</div>

					<div class="summary-card">
						<div class="summary-card__label">
							<i data-lucide="trending-up" class="summary-card__lucide"></i>
							Posição Média
						</div>
						<div class="summary-card__value"><?= $summary['avg_position'] ?></div>
					</div>
				</div>

				<!-- Gráfico de Evolução -->
				<?php if (!empty($stats['evolution'])): ?>
				<div class="gsc-chart">
					<div class="gsc-chart__header">
						<h3 class="gsc-chart__title">Evolução (últimos 7 dias)</h3>
					</div>
					<canvas id="evolutionChart" class="gsc-chart__canvas"></canvas>
				</div>
				<?php endif; ?>

				<!-- Top Queries e Top Pages -->
				<div class="gsc-grid">
					<!-- Top Queries -->
					<div class="gsc-card">
						<div class="gsc-card__header">
							<h3 class="gsc-card__title"><i data-lucide="search"></i> Top 10 Keywords</h3>
						</div>
						<div class="gsc-card__content">
							<?php if (!empty($stats['top_queries'])): ?>
								<table class="gsc-table">
									<thead>
										<tr>
											<th>Query</th>
											<th>Clicks</th>
											<th>Impressões</th>
											<th>Posição</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($stats['top_queries'] as $query): ?>
										<tr>
											<td class="gsc-table__query"><?= htmlspecialchars($query['query']) ?></td>
											<td><?= number_format($query['total_clicks'], 0, ',', '.') ?></td>
											<td><?= number_format($query['total_impressions'], 0, ',', '.') ?></td>
											<td><?= round($query['avg_position'], 1) ?></td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else: ?>
								<p class="gsc-card__empty">Nenhuma query encontrada</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Top Pages -->
					<div class="gsc-card">
						<div class="gsc-card__header">
							<h3 class="gsc-card__title"><i data-lucide="file-text"></i> Top 10 Páginas</h3>
						</div>
						<div class="gsc-card__content">
							<?php if (!empty($stats['top_pages'])): ?>
								<table class="gsc-table">
									<thead>
										<tr>
											<th>URL</th>
											<th>Clicks</th>
											<th>Impressões</th>
											<th>Posição</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($stats['top_pages'] as $page): ?>
										<tr>
											<td class="gsc-table__url">
												<a href="<?= htmlspecialchars($page['page_url']) ?>" target="_blank" rel="noopener">
													<?= htmlspecialchars(parse_url($page['page_url'], PHP_URL_PATH) ?: $page['page_url']) ?>
												</a>
											</td>
											<td><?= number_format($page['total_clicks'], 0, ',', '.') ?></td>
											<td><?= number_format($page['total_impressions'], 0, ',', '.') ?></td>
											<td><?= round($page['avg_position'], 1) ?></td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else: ?>
								<p class="gsc-card__empty">Nenhuma página encontrada</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

			<?php else: ?>
				<!-- Empty State: Sem dados -->
				<div class="empty-state">
					<i data-lucide="database" class="empty-state__lucide"></i>
					<h3 class="empty-state__title">Nenhum dado sincronizado</h3>
					<p class="empty-state__text">
						Clique em "Sincronizar" para buscar os dados do Google Search Console
					</p>
					<form method="POST" action="<?= url('/admin/search-console/sync') ?>">
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
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', init);
			} else {
				init();
			}

			function init() {
				lucide.createIcons();

				// Gráfico de evolução
				const chartCanvas = document.getElementById('evolutionChart');
				if (chartCanvas) {
					const evolutionData = <?= json_encode($stats['evolution'] ?? []) ?>;

					const labels = evolutionData.map(d => {
						const date = new Date(d.date);
						return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
					});

					const clicks = evolutionData.map(d => d.clicks);
					const impressions = evolutionData.map(d => d.impressions);

					new Chart(chartCanvas, {
						type: 'line',
						data: {
							labels: labels,
							datasets: [
								{
									label: 'Clicks',
									data: clicks,
									borderColor: '#10b981',
									backgroundColor: 'rgba(16, 185, 129, 0.1)',
									borderWidth: 2,
									fill: true,
									tension: 0.4
								},
								{
									label: 'Impressões',
									data: impressions,
									borderColor: '#3b82f6',
									backgroundColor: 'rgba(59, 130, 246, 0.1)',
									borderWidth: 2,
									fill: true,
									tension: 0.4
								}
							]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							plugins: {
								legend: {
									position: 'top'
								}
							},
							scales: {
								y: {
									beginAtZero: true
								}
							}
						}
					});
				}
			}
		</script>

	</body>

</html>
