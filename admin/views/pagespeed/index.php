<?php
/**
 * PageSpeed Insights - Dashboard
 * Listagem de relatórios de performance
 *
 * Variáveis disponíveis (passadas pelo PageSpeedController):
 * - $settings: Configurações do sistema
 * - $pagespeedEnabled: bool
 * - $reports: array de relatórios
 * - $stats: estatísticas gerais
 * - $totalPages: total de páginas
 * - $page: página atual
 * - $urlFilter, $strategyFilter, $scoreFilter: filtros ativos
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once __DIR__ . '/../../includes/_admin-head.php'; ?>
    <title>PageSpeed Insights - <?= ADMIN_NAME ?></title>
</head>

<body class="page-pagespeed">

	<?php require_once __DIR__ . '/../../includes/header.php'; ?>

  <main class="m-pagebase">

    <!-- breadcrumb e btns -->
    <div class="m-pagebase__header">
      <h1>PageSpeed Insights</h1>
			<div class="m-pagespeed__flexhead">
 				<?php if ($pagespeedEnabled): ?>
          <button class="m-pagebase__btn m-pagebase__btn--primary m-pagebase__btn--widthauto" id="trigger-analysis">
            <i data-lucide="play"></i> Analisar Agora
          </button>
        <?php endif; ?>
        <a href="<?= url('/admin/settings#pagespeed') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--mleft">
          <i data-lucide="settings"></i> Configurar
        </a>
			</div>
    </div>		

		<?php if (isset($_SESSION['success'])): ?>
			<div class="alert alert--success">
				<?= $_SESSION['success'] ?>
			</div>
			<?php unset($_SESSION['success']); ?>
		<?php endif; ?>
		<?php if (isset($_SESSION['error'])): ?>
			<div class="alert alert--error">
				<?= htmlspecialchars($_SESSION['error']) ?>
			</div>
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

        <!-- Stats Cards -->
        <div class="m-pagespeed__stats">
            <div class="m-pagespeed__stat-card">
                <div class="m-pagespeed__stat-icon">
                    <i data-lucide="bar-chart-3"></i>
                </div>
                <div class="m-pagespeed__stat-value"><?= number_format($stats['total_analyses']) ?></div>
                <div class="m-pagespeed__stat-label">Total de Análises</div>
            </div>

            <div class="m-pagespeed__stat-card">
                <div class="m-pagespeed__stat-icon">
                    <i data-lucide="gauge"></i>
                </div>
                <div class="m-pagespeed__stat-value"><?= round($stats['avg_score']) ?>/100</div>
                <div class="m-pagespeed__stat-label">Score Médio</div>
            </div>

            <div class="m-pagespeed__stat-card">
                <div class="m-pagespeed__stat-icon">
                    <i data-lucide="link"></i>
                </div>
                <div class="m-pagespeed__stat-value"><?= $stats['total_urls'] ?></div>
                <div class="m-pagespeed__stat-label">URLs Analisadas</div>
            </div>

            <div class="m-pagespeed__stat-card">
                <div class="m-pagespeed__stat-icon">
                    <i data-lucide="clock"></i>
                </div>
                <div class="m-pagespeed__stat-value">
                    <?= $stats['last_analysis'] ? date('d/m/Y', strtotime($stats['last_analysis'])) : 'Nunca' ?>
                </div>
                <div class="m-pagespeed__stat-label">Última Análise</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="m-pagespeed__filters" method="GET">
            <div class="m-pagespeed__filter-group">
                <label>URL:</label>
                <input type="text" name="url" value="<?= htmlspecialchars($urlFilter) ?>" placeholder="Filtrar por URL...">
            </div>

            <div class="m-pagespeed__filter-group">
                <label>Estratégia:</label>
                <select name="strategy">
                    <option value="">Todas</option>
                    <option value="mobile" <?= $strategyFilter === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                    <option value="desktop" <?= $strategyFilter === 'desktop' ? 'selected' : '' ?>>Desktop</option>
                </select>
            </div>

            <div class="m-pagespeed__filter-group">
                <label>Score:</label>
                <select name="score">
                    <option value="">Todos</option>
                    <option value="good" <?= $scoreFilter === 'good' ? 'selected' : '' ?>>Bom (90-100)</option>
                    <option value="average" <?= $scoreFilter === 'average' ? 'selected' : '' ?>>Médio (50-89)</option>
                    <option value="poor" <?= $scoreFilter === 'poor' ? 'selected' : '' ?>>Ruim (&lt;50)</option>
                </select>
            </div>

            <button type="submit" class="m-pagebase__btn m-pagebase__btn--primary">
                <i data-lucide="filter"></i> Filtrar
            </button>

            <?php if (!empty($urlFilter) || !empty($strategyFilter) || !empty($scoreFilter)): ?>
                <a href="<?= url('/admin/pagespeed') ?>" class="m-pagebase__btn">
                    <i data-lucide="x"></i> Limpar
                </a>
            <?php endif; ?>
        </form>

        <!-- Reports Table -->
        <?php if (empty($reports)): ?>
            <div class="m-pagebase__empty">
                <i data-lucide="inbox"></i>
                <p>Nenhum relatório encontrado.</p>
                <?php if ($pagespeedEnabled): ?>
                    <button class="m-pagebase__btn m-pagebase__btn--primary" id="trigger-analysis-empty">
                        <i data-lucide="play"></i> Fazer Primeira Análise
                    </button>
                <?php else: ?>
                    <a href="<?= url('/admin/settings#pagespeed') ?>" class="m-pagebase__btn m-pagebase__btn--primary">
                        <i data-lucide="settings"></i> Configurar PageSpeed
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="m-pagebase__table">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Estratégia</th>
                        <th>Score</th>
                        <th>LCP</th>
                        <th>CLS</th>
                        <th>INP</th>
                        <th>Analisado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <?php
                        $scoreClass = 'score-poor';
                        if ($report['performance_score'] >= 90) $scoreClass = 'score-good';
                        elseif ($report['performance_score'] >= 50) $scoreClass = 'score-average';
                        ?>
                        <tr class="report-row report-row--<?= $report['status'] ?? 'completed' ?>">
                            <td class="url-cell" title="<?= htmlspecialchars($report['url']) ?>">
                                <?= htmlspecialchars(substr($report['url'], 0, 50)) . (strlen($report['url']) > 50 ? '...' : '') ?>
                            </td>
                            <td>
                                <span class="badge badge--<?= $report['strategy'] ?>">
                                    <?php if ($report['strategy'] === 'mobile'): ?>
                                        <i data-lucide="smartphone"></i> Mobile
                                    <?php else: ?>
                                        <i data-lucide="monitor"></i> Desktop
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($report['status'] ?? 'completed') === 'pending'): ?>
                                    <span class="badge badge--warning"><i data-lucide="clock"></i> Aguardando...</span>
                                <?php elseif (($report['status'] ?? 'completed') === 'processing'): ?>
                                    <span class="badge badge--info"><i data-lucide="loader"></i> Processando...</span>
                                <?php elseif (($report['status'] ?? 'completed') === 'failed'): ?>
                                    <span class="badge badge--danger"><i data-lucide="x-circle"></i> Falhou</span>
                                <?php else: ?>
                                    <span class="score-badge <?= $scoreClass ?>">
                                        <?= $report['performance_score'] ?>/100
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= ($report['status'] ?? 'completed') === 'completed' ? ($report['lab_lcp'] ? number_format($report['lab_lcp'], 2) . 's' : '-') : '-' ?></td>
                            <td><?= ($report['status'] ?? 'completed') === 'completed' ? ($report['lab_cls'] ? number_format($report['lab_cls'], 3) : '-') : '-' ?></td>
                            <td><?= ($report['status'] ?? 'completed') === 'completed' ? ($report['lab_inp'] ? $report['lab_inp'] . 'ms' : '-') : '-' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($report['analyzed_at'])) ?></td>
                            <td>
                                <?php if (($report['status'] ?? 'completed') === 'completed'): ?>
                                    <a href="<?= url('/admin/pagespeed/report/' . $report['id']) ?>" class="btn-icon" title="Ver Detalhes">
                                        <i data-lucide="eye"></i>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="m-pagebase__pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&url=<?= urlencode($urlFilter) ?>&strategy=<?= $strategyFilter ?>&score=<?= $scoreFilter ?>"
                           class="m-pagebase__btn">
                            <i data-lucide="chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <span class="m-pagebase__pagination-info">
                        Página <?= $page ?> de <?= $totalPages ?>
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&url=<?= urlencode($urlFilter) ?>&strategy=<?= $strategyFilter ?>&score=<?= $scoreFilter ?>"
                           class="m-pagebase__btn">
                            Próxima <i data-lucide="chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Auto-refresh quando houver análises pendentes/processing
        const hasPendingAnalyses = document.querySelectorAll('.report-row--pending, .report-row--processing').length > 0;
        if (hasPendingAnalyses) {
            setTimeout(() => {
                location.reload();
            }, 5000); // Recarrega a cada 5 segundos
        }

        // Trigger manual analysis
        const triggerBtns = document.querySelectorAll('#trigger-analysis, #trigger-analysis-empty');
        triggerBtns.forEach(btn => {
            btn?.addEventListener('click', async () => {
                if (!confirm('Iniciar análise de todas as páginas? Isso pode levar vários minutos.')) return;

                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader"></i> Analisando...';
                lucide.createIcons();

                try {
                    // Trigger n8n webhook
                    const formData = new FormData();
                    formData.append('csrf_token', '<?= Security::generateCSRF() ?>');

                    const res = await fetch('<?= url('/admin/api/pagespeed-trigger.php') ?>', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();

                    if (data.success) {
                        alert(`Análise iniciada! ${data.total_analyses} análises serão processadas.`);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert('Erro: ' + data.error);
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="play"></i> Analisar Agora';
                        lucide.createIcons();
                    }
                } catch (error) {
                    alert('Erro ao iniciar análise: ' + error.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="play"></i> Analisar Agora';
                    lucide.createIcons();
                }
            });
        });
    </script>

</body>

</html>