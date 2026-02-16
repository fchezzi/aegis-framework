<?php
/**
 * PageSpeed Insights - Relatório Detalhado
 * Visualização completa de um relatório de performance
 *
 * Variáveis disponíveis (passadas pelo PageSpeedController):
 * - $report: dados do relatório
 * - $opportunities: array de oportunidades de melhoria
 * - $diagnostics: array de diagnósticos
 */

// Determinar classe do score
$scoreClass = 'score-poor';
if ($report['performance_score'] >= 90) $scoreClass = 'score-good';
elseif ($report['performance_score'] >= 50) $scoreClass = 'score-average';

// Helper para classificar métrica
function getMetricStatus($metric, $value, $isCLS = false) {
    if ($value === null) return 'unknown';

    $thresholds = [
        'lcp' => ['good' => 2500, 'average' => 4000],    // ms
        'fcp' => ['good' => 1800, 'average' => 3000],    // ms
        'cls' => ['good' => 0.1, 'average' => 0.25],     // score
        'inp' => ['good' => 200, 'average' => 500],      // ms
        'si' => ['good' => 3400, 'average' => 5800],     // ms
        'tti' => ['good' => 3800, 'average' => 7300],    // ms
        'tbt' => ['good' => 200, 'average' => 600],      // ms
        'ttfb' => ['good' => 800, 'average' => 1800]     // ms
    ];

    if (!isset($thresholds[$metric])) return 'unknown';

    $t = $thresholds[$metric];

    if ($value <= $t['good']) return 'good';
    if ($value <= $t['average']) return 'average';
    return 'poor';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

	<head>
		<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
		<title>PageSpeed Report - <?= ADMIN_NAME ?></title>
	</head>

	<body class="m-pagebasebody">

		<?php require_once __DIR__ . '/../../includes/header.php'; ?>

		<main class="m-pagebase">

			<!-- breadcrumb e btns -->
			<div class="m-pagebase__header">
				<h1>Relatório PageSpeed</h1>
				<p class="m-pagebase__subtitle">
					<?= htmlspecialchars($report['url']) ?>
				</p>
			</div>

			<!-- Seletor Mobile/Desktop -->
			<?php if (isset($otherReports) && count($otherReports) <= 2 && count($otherReports) > 1): ?>

				<div class="m-pagespeed__kind">

					<div class="m-pagespeed__scoredisplay">
						<strong>Visualizar: &nbsp;</strong>
						
						<?php foreach ($otherReports as $other): ?>
							<?php if ($other['id'] == $report['id']): ?>
								<button class="m-pagespeed__btn m-pagespeed__btn-desktop">
									<i data-lucide="<?= $other['strategy'] === 'mobile' ? 'smartphone' : 'monitor' ?>" class="m-pagespeed__btn-icon"></i> <?= ucfirst($other['strategy']) ?> (Score: <?= $other['performance_score'] ?>)
								</button>
							<?php else: ?>
								<a href="<?= url('/admin/pagespeed/report/' . $other['id']) ?>" class="m-pagespeed__btn m-pagespeed__btn-mobile">
									<i data-lucide="<?= $other['strategy'] === 'mobile' ? 'smartphone' : 'monitor' ?>" class="m-pagespeed__btn-icon"></i> <?= ucfirst($other['strategy']) ?> (Score: <?= $other['performance_score'] ?>)
								</a>
							<?php endif; ?>
						<?php endforeach; ?>

					</div>

					<div class="m-pagebase__header-actions">
						<a href="<?= url('/admin/pagespeed') ?>" class="m-pagebase__btn">
							<i data-lucide="arrow-left"></i> Voltar
						</a>
					</div>			

				</div>

			<?php endif; ?>

			<!-- Core Web Vitals Assessment -->
			<?php if ($report['field_category']): ?>
				<div class="m-pagespeed__coreweb">
					<strong>Avaliação das Core Web Vitals:</strong> 
					<?php if (strtolower($report['field_category']) === 'slow'): ?>
						<i data-lucide="x-circle" class="m-pagespeed__coreweb-icon"></i> REPROVADO
					<?php elseif (strtolower($report['field_category']) === 'fast'): ?>
						<i data-lucide="check-circle" class="m-pagespeed__coreweb-icon"></i> APROVADO
					<?php else: ?>
						<i data-lucide="alert-circle" class="m-pagespeed__coreweb-icon"></i> MÉDIO
					<?php endif; ?>
					
					(<?= $report['field_category'] ?>)
				</div>
			<?php endif; ?>			

			<!-- Overview Card -->
			<div class="m-pagespeed__report-card">
				<div class="m-pagespeed__report-header">
					<div>
						<h2>Visão Geral</h2>
						<div class="m-pagespeed__report-meta">
							<span>
								<i data-lucide="<?= $report['strategy'] === 'mobile' ? 'smartphone' : 'monitor' ?>"></i><?= ucfirst($report['strategy']) ?>
							</span>
							<span>
								<i data-lucide="calendar"></i><?= date('d/m/Y H:i', strtotime($report['analyzed_at'])) ?>
							</span>
							<span>
								<i data-lucide="zap"></i>Lighthouse <?= htmlspecialchars($report['lighthouse_version'] ?? 'N/A') ?>
							</span>
						</div>
					</div>
					<div class="m-pagespeed__prescore">
						<div class="m-pagespeed__score-badge-large <?= $scoreClass ?>">
							<?= $report['performance_score'] ?>				
						</div>
										<?php if (!empty($report['performance_min']) && !empty($report['performance_max'])): ?>
							<div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">
								<?= $report['performance_min'] ?>-<?= $report['performance_max'] ?>
								<?php if (!empty($report['num_tests']) && $report['num_tests'] > 1): ?>
									(<?= $report['num_tests'] ?> testes)
								<?php endif; ?>
							</div>
						<?php endif; ?></div>
				</div>
			</div>
			
			<div class="m-pagespeed__report-metrics">
				<!-- Core Web Vitals Cards (Ordem Google PageSpeed) -->
				<div class="m-pagespeed__report-section">
					<h3>
						<i data-lucide="activity"></i>Métricas Principais
					</h3>
				</div>
				<div class="m-pagespeed__metrics">
					<!-- 1. FCP (First Contentful Paint) -->
					<div class="m-pagespeed__metric <?= getMetricStatus('fcp', $report['lab_fcp']) ?>">
						<div class="m-pagespeed__metric-label">FCP</div>
							<div class="m-pagespeed__metric-value">
								<?= $report['lab_fcp'] ? number_format($report['lab_fcp'] / 1000, 2) . 's' : 'N/A' ?>
							</div>
						<div class="m-pagespeed__metric-name">First Contentful Paint</div>
					</div>
					<!-- 2. LCP (Largest Contentful Paint) -->
					<div class="m-pagespeed__metric <?= getMetricStatus('lcp', $report['lab_lcp']) ?>">
						<div class="m-pagespeed__metric-label">LCP</div>
						<div class="m-pagespeed__metric-value">
							<?= $report['lab_lcp'] ? number_format($report['lab_lcp'] / 1000, 2) . 's' : 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Largest Contentful Paint</div>
					</div>				
					<!-- 3. TBT (Total Blocking Time) -->
					<div class="m-pagespeed__metric <?= getMetricStatus('tbt', $report['lab_tbt']) ?>">
						<div class="m-pagespeed__metric-label">TBT</div>
						<div class="m-pagespeed__metric-value">
							<?= $report['lab_tbt'] !== null ? round($report['lab_tbt']) . 'ms' : 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Total Blocking Time</div>
					</div>
					<!-- 4. CLS (Cumulative Layout Shift) -->
					<div class="m-pagespeed__metric <?= getMetricStatus('cls', $report['lab_cls'], true) ?>">
						<div class="m-pagespeed__metric-label">CLS</div>
						<div class="m-pagespeed__metric-value">
							<?= $report['lab_cls'] !== null ? number_format($report['lab_cls'], 3) : 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Cumulative Layout Shift</div>
					</div>
					<!-- 5. SI (Speed Index) -->
					<div class="m-pagespeed__metric <?= getMetricStatus('si', $report['lab_si']) ?>">
						<div class="m-pagespeed__metric-label">SI</div>
						<div class="m-pagespeed__metric-value">
							<?= $report['lab_si'] ? number_format($report['lab_si'] / 1000, 2) . 's' : 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Speed Index</div>
					</div>
					<!-- 6. TTI (Time to Interactive) - Métrica extra -->
					<div class="m-pagespeed__metric <?= getMetricStatus('tti', $report['lab_tti']) ?>">
						<div class="m-pagespeed__metric-label">TTI</div>
						<div class="m-pagespeed__metric-value">
							<?= $report['lab_tti'] ? number_format($report['lab_tti'] / 1000, 2) . 's' : 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Time to Interactive</div>
					</div>							
				</div>
			</div>

			<!-- 4 Scores: Performance, Acessibilidade, Práticas, SEO -->
			<div class="m-pagespeed__reportsborder">
				<div class="m-pagespeed__report-section">
					<h3>
						<i data-lucide="bar-chart"></i>Diagnosticar problemas de desempenho
					</h3>
				</div>
				<div class="m-pagespeed__metrics">
					<!-- Performance -->
					<div class="m-pagespeed__metric">
						<div class="m-pagespeed__metric-label">Desempenho</div>
						<div class="m-pagespeed__metric-value" style="color: <?= $report['performance_score'] >= 90 ? 'blue' : ($report['performance_score'] >= 50 ? 'orange' : 'red') ?>">
							<?= $report['performance_score'] ?? 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Performance</div>
					</div>
					<!-- Acessibilidade -->
					<div class="m-pagespeed__metric">
						<div class="m-pagespeed__metric-label">Acessibilidade</div>
						<div class="m-pagespeed__metric-value" style="color: <?= ($report['accessibility_score'] ?? 0) >= 90 ? 'blue' : (($report['accessibility_score'] ?? 0) >= 50 ? 'orange' : 'red') ?>">
							<?= $report['accessibility_score'] ?? 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Accessibility</div>
					</div>
					<!-- Práticas Recomendadas -->
					<div class="m-pagespeed__metric">
						<div class="m-pagespeed__metric-label">Práticas</div>
						<div class="m-pagespeed__metric-value" style="color: <?= ($report['best_practices_score'] ?? 0) >= 90 ? 'blue' : (($report['best_practices_score'] ?? 0) >= 50 ? 'orange' : 'red') ?>">
							<?= $report['best_practices_score'] ?? 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Best Practices</div>
					</div>				
					<!-- SEO -->
					<div class="m-pagespeed__metric">
						<div class="m-pagespeed__metric-label">SEO</div>
						<div class="m-pagespeed__metric-value" style="color: <?= ($report['seo_score'] ?? 0) >= 90 ? 'blue' : (($report['seo_score'] ?? 0) >= 50 ? 'orange' : 'red') ?>">
							<?= $report['seo_score'] ?? 'N/A' ?>
						</div>
						<div class="m-pagespeed__metric-name">Search Engine Optimization</div>
					</div>
				</div>
			</div>	

			<!-- Field Data (Real User Metrics) -->
			<?php if ($report['field_lcp'] || $report['field_fcp'] || $report['field_cls'] || $report['field_inp']): ?>
				<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
					<div>
						<h3>
							<i data-lucide="users"></i>Dados de Usuários Reais (Field Data)
						</h3>
						<p class="m-pagespeed__section-help">Métricas coletadas de usuários reais via Chrome UX Report</p>
					</div>
					<div class="m-pagespeed__field-category">
						Categoria: 
						<span class="badge badge--<?= strtolower($report['field_category'] ?? 'unknown') ?>">
							<?= htmlspecialchars($report['field_category'] ?? 'N/A') ?>
						</span>
					</div>
					<div class="m-pagespeed__metrics m-pagespeed__field-category-pad">
						<!-- 1. FCP (First Contentful Paint) -->
						<div class="m-pagespeed__metric <?= $report['field_fcp'] ? getMetricStatus('fcp', $report['field_fcp']) : '' ?>">
							<div class="m-pagespeed__metric-label">FCP</div>
							<div class="m-pagespeed__metric-value">
								<?= $report['field_fcp'] ? number_format($report['field_fcp'] / 1000, 2) . 's' : 'N/A' ?>
							</div>
							<div class="m-pagespeed__metric-name">First Contentful Paint</div>
						</div>
						<!-- 2. LCP (Largest Contentful Paint) -->
						<div class="m-pagespeed__metric <?= $report['field_lcp'] ? getMetricStatus('lcp', $report['field_lcp']) : '' ?>">
							<div class="m-pagespeed__metric-label">LCP</div>
							<div class="m-pagespeed__metric-value">
								<?= $report['field_lcp'] ? number_format($report['field_lcp'] / 1000, 2) . 's' : 'N/A' ?>
							</div>
							<div class="m-pagespeed__metric-name">Largest Contentful Paint</div>
						</div>
						<!-- 3. TBT (Total Blocking Time) - Não disponível em Field Data -->
						<div class="m-pagespeed__metric">
							<div class="m-pagespeed__metric-label">TBT</div>
							<div class="m-pagespeed__metric-value">N/A</div>
							<div class="m-pagespeed__metric-name">Total Blocking Time</div>
						</div>
						<!-- 4. CLS (Cumulative Layout Shift) -->
						<div class="m-pagespeed__metric <?= $report['field_cls'] ? getMetricStatus('cls', $report['field_cls'], true) : '' ?>">
							<div class="m-pagespeed__metric-label">CLS</div>
							<div class="m-pagespeed__metric-value">
								<?= $report['field_cls'] !== null ? number_format($report['field_cls'], 3) : 'N/A' ?>
							</div>
							<div class="m-pagespeed__metric-name">Cumulative Layout Shift</div>
						</div>
						<!-- 5. SI (Speed Index) - Não disponível em Field Data -->
						<div class="m-pagespeed__metric">
							<div class="m-pagespeed__metric-label">SI</div>
							<div class="m-pagespeed__metric-value">N/A</div>
							<div class="m-pagespeed__metric-name">Speed Index</div>
						</div>
						<!-- 6. INP (Interaction to Next Paint) - Substituindo TTI no Field -->
						<div class="m-pagespeed__metric <?= $report['field_inp'] ? getMetricStatus('inp', $report['field_inp']) : '' ?>">
							<div class="m-pagespeed__metric-label">INP</div>
							<div class="m-pagespeed__metric-value">
								<?= $report['field_inp'] !== null ? round($report['field_inp']) . 'ms' : 'N/A' ?>
							</div>
							<div class="m-pagespeed__metric-name">Interaction to Next Paint</div>
						</div>
					</div>
				</div>
			<?php endif; ?>		

			<!-- Opportunities VERIFICAR-->
			<?php if (!empty($opportunities)): ?>
				<div class="m-pagespeed__report-section m-pagespeed__report-section--full">
					<h3><i data-lucide="lightbulb"></i>Oportunidades de Melhoria</h3>
					<p class="m-pagespeed__section-help">
						Principais otimizações que podem melhorar a performance
					</p>
					<div class="m-pagespeed__opportunities">
						<?php foreach ($opportunities as $opp): ?>
							<div class="m-pagespeed__opportunity">
								<div class="m-pagespeed__opportunity-header">
									<h4><?= htmlspecialchars($opp['title']) ?></h4>
									<div class="m-pagespeed__opportunity-savings">
										<?php if ($opp['savings_lcp'] > 0): ?>
											<span class="badge badge--warning">
												-<?= round($opp['savings_lcp'] / 1000, 2) ?>s LCP
											</span>
										<?php endif; ?>
										<?php if ($opp['savings_fcp'] > 0): ?>
											<span class="badge badge--warning">
												-<?= round($opp['savings_fcp'] / 1000, 2) ?>s FCP
											</span>
										<?php endif; ?>
									</div>
								</div>
								<p><?= nl2br(htmlspecialchars($opp['description'])) ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>	

			<!-- Diagnostics VERIFICAR -->
			<?php if (!empty($diagnostics)): ?>
				<div class="m-pagespeed__report-section">
					<h3><i data-lucide="info"></i>Diagnósticos</h3>
					<table class="m-pagespeed__diagnostics-table">
						<?php if (isset($diagnostics['dom_size'])): ?>
							<tr>
								<td>Tamanho do DOM</td>
								<td><strong><?= number_format($diagnostics['dom_size']) ?></strong> elementos</td>
							</tr>
						<?php endif; ?>
						<?php if (isset($diagnostics['requests_count'])): ?>
							<tr>
								<td>Total de Requests</td>
								<td><strong><?= number_format($diagnostics['requests_count']) ?></strong> requisições</td>
							</tr>
						<?php endif; ?>
						<?php if (isset($diagnostics['transfer_size'])): ?>
							<tr>
								<td>Tamanho Total</td>
								<td><strong><?= number_format($diagnostics['transfer_size'] / 1024 / 1024, 2) ?></strong> MB</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>Tempo de Análise</td>
							<td><strong><?= number_format($report['fetch_time_ms'] / 1000, 2) ?></strong> segundos</td>
						</tr>
					</table>
				</div>
			<?php endif; ?>

			<!-- ============================================  VERIFICAR-->
			<!-- NOVAS SEÇÕES - DADOS COMPLETOS v2.0 -->
			<!-- ============================================ -->
			<!-- TODAS as Oportunidades (não apenas TOP 5) -->
			<?php
				// DEBUG - VER O QUE ESTÁ CHEGANDO 
				echo "<!-- DEBUG opportunitiesFull: ";
				var_dump($report['opportunitiesFull'] ?? 'NAO EXISTE');
				echo " -->";
			?>

			<?php if (!empty($report['opportunitiesFull']) && is_array($report['opportunitiesFull']) && count($report['opportunitiesFull']) > 0): ?>
				<?php
					// Mapeamento de títulos para português
					$titleMap = [
					// Audits tradicionais
					'render-blocking-insight' => 'Recursos de bloqueio de renderização',
					'render-blocking-resources' => 'Recursos de bloqueio de renderização',
					'unused-css-rules' => 'CSS não utilizado',
					'unused-javascript' => 'JavaScript não utilizado',
					'modern-image-formats' => 'Usar formatos de imagem modernos',
					'uses-optimized-images' => 'Otimizar imagens',
					'offscreen-images' => 'Adiar imagens fora da tela',
					'uses-responsive-images' => 'Imagens responsivas',
					'efficient-animated-content' => 'Conteúdo animado eficiente',
					'duplicated-javascript' => 'JavaScript duplicado',
					'legacy-javascript' => 'JavaScript legado',
					'preload-lcp-image' => 'Pré-carregar imagem LCP',
					'uses-long-cache-ttl' => 'Cache de longo prazo',
					'uses-rel-preconnect' => 'Pré-conectar a origens',
					'server-response-time' => 'Tempo de resposta do servidor',
					'redirects' => 'Redirecionamentos múltiplos',
					'uses-text-compression' => 'Compressão de texto',
					'uses-rel-preload' => 'Pré-carregar recursos-chave',
					'unminified-css' => 'CSS não minificado',
					'unminified-javascript' => 'JavaScript não minificado',
					'font-display' => 'Fontes com display adequado',
					'third-party-summary' => 'Código de terceiros',
					// Novos audits
					'cls-culprits-insight' => 'Elementos causadores de mudança de layout',
					'image-delivery-insight' => 'Melhorar entrega de imagens',
					'font-display-insight' => 'Otimizar exibição de fontes',
					'cache-insight' => 'Usar cache eficiente',
					'lcp-discovery-insight' => 'Descoberta de requisição LCP',
					'network-dependency-tree-insight' => 'Árvore de dependências de rede',
					'largest-contentful-paint' => 'Largest Contentful Paint (LCP)',
					'layout-shifts' => 'Evitar grandes mudanças de layout',
					'interactive' => 'Tempo até interatividade',
					'unsized-images' => 'Imagens sem dimensões explícitas',
					'total-byte-weight' => 'Tamanho total da página',
					'speed-index' => 'Speed Index',
					'total-blocking-time' => 'Tempo total de bloqueio',
					'max-potential-fid' => 'Atraso máximo potencial',
					'cumulative-layout-shift' => 'Mudança cumulativa de layout',
					'first-contentful-paint' => 'First Contentful Paint'
					];
				?>
				<div class="m-pagespeed__report-section m-pagespeed__report-section--full m-pagespeed__reportsborder">
					<h3>
						<i data-lucide="zap"></i> Todas as Oportunidades de Melhoria (<?= count($report['opportunitiesFull']) ?>)
					</h3>
					<p class="m-pagespeed__section-help">Lista completa de otimizações ordenadas por impacto</p>
					<div class="m-pagespeed__opportunities-full">
						<?php foreach ($report['opportunitiesFull'] as $opp): ?>
							<?php
								$scoreClass = 'poor';
								if ($opp['score'] >= 0.9) $scoreClass = 'good';
								elseif ($opp['score'] >= 0.5) $scoreClass = 'average';
							?>
							<div class="m-pagespeed__opportunity-card">

								<div class="m-pagespeed__opportunity-header">
									<div>
										<?php
											// Usar título traduzido se disponível
											$displayTitle = $titleMap[$opp['audit_id']] ?? $opp['title'];
										?>
										<h4><?= htmlspecialchars($displayTitle) ?></h4>
										<p class="m-pagespeed__opportunity-desc"><?= htmlspecialchars($opp['description']) ?></p>
									</div>
									<div class="m-pagespeed__opportunity-score <?= $scoreClass ?>">
										<?= round($opp['score'] * 100) ?>
									</div>
								</div>

								<div class="m-pagespeed__opportunity-savings">
									<?php if ($opp['savings_ms'] > 0): ?>
										<span class="badge badge--warning">
											<i data-lucide="clock"></i> -<?= round($opp['savings_ms'] / 1000, 2) ?>s
										</span>
									<?php endif; ?>
									<?php if (!empty($opp['savings_bytes'])): ?>
										<span class="badge badge--info">
											<i data-lucide="hard-drive"></i> -<?= round($opp['savings_bytes'] / 1024, 1) ?>KB
										</span>
									<?php endif; ?>
									<?php if (!empty($opp['display_value'])): ?>
										<span class="badge"><?= htmlspecialchars($opp['display_value']) ?></span>
									<?php endif; ?>
								</div>
								<?php if (!empty($opp['items'])): ?>
									<details class="m-pagespeed__opportunity-details">
										<summary>Ver <?= count($opp['items']) ?> arquivo(s) específico(s)</summary>
										<table class="m-pagespeed__items-table">
											<thead>
												<tr>
													<th>Arquivo</th>
													<th>Tamanho</th>
													<th>Desperdício</th>
													<th>Economia</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($opp['items'] as $item): ?>
													<tr>
														<td class="url-cell" title="<?= htmlspecialchars($item['url'] ?? 'N/A') ?>">
														<?= htmlspecialchars(basename($item['url'] ?? 'N/A')) ?>
														</td>
														<td><?= isset($item['total_bytes']) ? round($item['total_bytes'] / 1024, 1) . 'KB' : 'N/A' ?></td>
														<td><?= isset($item['wasted_bytes']) ? round($item['wasted_bytes'] / 1024, 1) . 'KB' : 'N/A' ?></td>
														<td><?= isset($item['wasted_ms']) ? round($item['wasted_ms']) . 'ms' : 'N/A' ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</details>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

				<!-- Resource Breakdown -->
				<?php if (!empty($report['resourceSummary'])): ?>
					<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="package"></i> Recursos por Tipo
						</h3>
						<p class="m-pagespeed__section-help">Breakdown de recursos carregados</p>
						<div class="m-pagespeed__resources-grid">
							<?php if (isset($report['resourceSummary']['scripts'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="file-code"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['scripts']['count'] ?>
									</div>
									<div class="m-pagespeed__resource-label">Scripts</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['scripts']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
							<?php if (isset($report['resourceSummary']['stylesheets'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="palette"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['stylesheets']['count'] ?></div>
									<div class="m-pagespeed__resource-label">CSS</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['stylesheets']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
							<?php if (isset($report['resourceSummary']['images'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="image"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['images']['count'] ?></div>
									<div class="m-pagespeed__resource-label">Imagens</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['images']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
							<?php if (isset($report['resourceSummary']['fonts'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="type"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['fonts']['count'] ?></div>
									<div class="m-pagespeed__resource-label">Fontes</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['fonts']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
							<?php if (isset($report['resourceSummary']['documents'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="file-text"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['documents']['count'] ?></div>
									<div class="m-pagespeed__resource-label">HTML</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['documents']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
							<?php if (isset($report['resourceSummary']['other'])): ?>
								<div class="m-pagespeed__resource-card">
									<div class="m-pagespeed__resource-icon">
										<i data-lucide="package"></i>
									</div>
									<div class="m-pagespeed__resource-count"><?= $report['resourceSummary']['other']['count'] ?></div>
									<div class="m-pagespeed__resource-label">Outros</div>
									<div class="m-pagespeed__resource-size"><?= $report['resourceSummary']['other']['size_kb'] ?>KB</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Third-Party Analysis -->
				<?php if (!empty($report['thirdPartySummary']) && is_array($report['thirdPartySummary'])): ?>
					<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="globe"></i>
							Análise de Third-Party
						</h3>
						<p class="m-pagespeed__section-help">Código de terceiros que está impactando performance</p>
						<table class="m-pagespeed__diagnostics-table">
							<thead>
								<tr>
									<th>Entidade</th>
									<th>Tamanho</th>
									<th>Tempo CPU</th>
									<th>Bloqueio</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($report['thirdPartySummary'] as $tp): ?>
									<?php if (is_array($tp)): ?>
										<tr>
											<td><strong><?= htmlspecialchars($tp['entity'] ?? 'Desconhecido') ?></strong></td>
											<td><?= isset($tp['transfer_size_kb']) ? $tp['transfer_size_kb'] . 'KB' : 'N/A' ?></td>
											<td><?= isset($tp['mainthread_time_ms']) ? round($tp['mainthread_time_ms']) . 'ms' : 'N/A' ?></td>
											<td><?= isset($tp['blocking_time_ms']) ? round($tp['blocking_time_ms']) . 'ms' : 'N/A' ?></td>
										</tr>
									<?php endif; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>


				<!-- Mainthread Breakdown -->
				<?php if (!empty($report['diagnosticsFull']['mainthread_breakdown']) && is_array($report['diagnosticsFull']['mainthread_breakdown'])): ?>
					<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="cpu"></i>
							Mainthread Breakdown
						</h3>
						<p class="m-pagespeed__section-help">Como a thread principal está gastando tempo</p>
						<table class="m-pagespeed__diagnostics-table">
							<?php foreach ($report['diagnosticsFull']['mainthread_breakdown'] as $task): ?>
								<?php if (is_array($task)): ?>
									<tr>
										<td><?= htmlspecialchars($task['category'] ?? 'Outro') ?></td>
										<td><strong><?= isset($task['time_ms']) ? round($task['time_ms']) . 'ms' : 'N/A' ?></strong></td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</table>
					</div>
				<?php endif; ?>


				<!-- Bootup Time (JS Execution) -->
				<?php if (!empty($report['diagnosticsFull']['bootup_time'])): ?>
					<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="zap"></i>
							JavaScript Bootup Time
						</h3>
						<p class="m-pagespeed__section-help">Scripts mais lentos na inicialização</p>
						<table class="m-pagespeed__diagnostics-table">
							<thead>
								<tr>
									<th>Script</th>
									<th>Total</th>
									<th>Execução</th>
									<th>Parse</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($report['diagnosticsFull']['bootup_time'] as $script): ?>
									<tr>
										<td class="url-cell" title="<?= htmlspecialchars($script['url']) ?>">
										<?= htmlspecialchars(basename($script['url'])) ?>
										</td>
										<td><strong><?= round($script['total_ms']) ?>ms</strong></td>
										<td><?= round($script['scripting_ms']) ?>ms</td>
										<td><?= round($script['script_parse_compile_ms']) ?>ms</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- LCP & CLS Elements -->
				<?php if (!empty($report['lcp_element']) || !empty($clsElements)): ?>
					<div class="m-pagespeed__report-section m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="target"></i>
							Elementos Críticos
						</h3>
						<?php if (!empty($report['lcp_element'])): ?>
							<div class="m-pagespeed__element-box">
								<h4>Elemento LCP (priorize otimização):</h4>
								<code><?= htmlspecialchars($report['lcp_element']) ?></code>
							</div>
						<?php endif; ?>
						<?php if (!empty($clsElements)): ?>
							<div class="m-pagespeed__element-box">
								<h4>Elementos causando CLS:</h4>
								<ul>
									<?php foreach ($clsElements as $elem): ?>
										<li>
											<code><?= htmlspecialchars($elem['node']) ?></code>
											<span class="badge badge--warning">Score: <?= number_format($elem['score'], 3) ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Passed Audits (o que está bom) -->
				<?php if (!empty($report['passedAudits'])): ?>
					<div class="m-pagespeed__report-section  m-pagespeed__reportsborder">
						<h3>
							<i data-lucide="check-circle"></i>
							Auditorias Aprovadas (<?= count($report['passedAudits']) ?>)
						</h3>
						<p class="m-pagespeed__section-help">O que já está funcionando bem</p>
						<details>
							<summary>Ver lista completa</summary>
							<ul class="m-pagespeed__passed-list">
								<?php foreach ($report['passedAudits'] as $audit): ?>
									<li>
									<i data-lucide="check"></i>
									<?= htmlspecialchars($audit['title']) ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</details>
					</div>
				<?php endif; ?>				

			
		</main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>

</html>
