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
		<title>Melhorias Futuras - <?= ADMIN_NAME ?></title>
		<style>
			.m-pagebase__section {
				margin-bottom: 60px;
			}
			.m-pagebase__section-title {
				font-size: 18px;
				font-weight: 600;
				margin-bottom: 30px;
				display: flex;
				align-items: center;
				gap: 8px;
			}
			.m-pagebase__list {
				display: flex;
				flex-direction: column;
				gap: 40px;
			}
			.m-pagebase__list-item {
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				gap: 20px;
			}
			.m-pagebase__list-item-content {
				flex: 1;
			}
			.m-pagebase__list-item-title {
				font-size: 15px;
				font-weight: 400;
				margin: 0 0 8px 0;
			}
			.m-pagebase__list-item-description {
				font-size: 13px;
				font-weight: 300;
				color: #666;
				line-height: 1.5;
				margin: 0;
			}
			.m-pagebase__list-item-meta {
				display: flex;
				gap: 8px;
				flex-shrink: 0;
			}
		</style>
	</head>

	<body class="m-pagebasebody">

		<?php require_once __DIR__ . '/../includes/header.php'; ?>

		<main class="m-pagebase">

			<!-- breadcrumb e btns -->
			<div class="m-pagebase__header">
				<h1>Melhorias Futuras</h1>
				<a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
					<i data-lucide="arrow-left"></i> Voltar
				</a>
			</div>

			<!-- mensagens de status -->
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

			<!-- SEO -->
			<div class="m-pagebase__section">
				<h2 class="m-pagebase__section-title">
					<i data-lucide="search"></i> SEO
				</h2>

				<div class="m-pagebase__list">
					<!-- Item 8: PHPStan -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">PHPStan</h3>
							<p class="m-pagebase__list-item-description">Ferramenta de análise estática de código PHP para detectar bugs antes da execução</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">10min</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 9: Google Analytics API -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Google Analytics API</h3>
							<p class="m-pagebase__list-item-description">Integração completa com Google Analytics para dashboard de métricas no admin</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">4-5h</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 10: Rate Limiting -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Rate Limiting</h3>
							<p class="m-pagebase__list-item-description">Classe e implementação de rate limiting para proteção contra abuso de APIs e formulários</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">2h</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 11: Security Headers -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Security Headers</h3>
							<p class="m-pagebase__list-item-description">Implementação de headers de segurança (CSP, X-Frame-Options, HSTS, etc) com testes</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">40min</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 12: PHP_CodeSniffer -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">PHP_CodeSniffer</h3>
							<p class="m-pagebase__list-item-description">Ferramenta para garantir qualidade e padrões de código PHP (PSR-12)</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">15min</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 13: Logger Melhorado -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Logger Melhorado</h3>
							<p class="m-pagebase__list-item-description">Sistema de logs estruturados salvos em MySQL com níveis (info, warning, error, critical)</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">3h</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 14: Microsoft Clarity -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Microsoft Clarity</h3>
							<p class="m-pagebase__list-item-description">Adicionar código de tracking do Microsoft Clarity para heatmaps e session recordings</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">10min</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>

					<!-- Item 15: Pa11y -->
					<div class="m-pagebase__list-item">
						<div class="m-pagebase__list-item-content">
							<h3 class="m-pagebase__list-item-title">Pa11y</h3>
							<p class="m-pagebase__list-item-description">Ferramenta de teste de acessibilidade (WCAG) automatizada</p>
						</div>
						<div class="m-pagebase__list-item-meta">
							<span class="badge badge--info">30min</span>
							<span class="badge badge--warning">Pendente</span>
						</div>
					</div>
				</div>
			</div>

		</main>

		<script src="https://unpkg.com/lucide@latest"></script>
		<script>
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', init);
			} else {
				init();
			}

			function init() {
				lucide.createIcons();
			}
		</script>

	</body>

</html>
