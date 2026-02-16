<?php
// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;

// Definir título e slug da página
// Quando criado via admin, charts e charts são substituídos
// Quando carregado via rota, $pageTitle e $pageSlug são definidos
$title = isset($pageTitle) ? $pageTitle : 'charts';
$slug = isset($pageSlug) ? $pageSlug : 'charts';
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

    <meta name="keywords" content="inserir,as,palavras,chave">
    <meta name="description" content="inserir o meta keywords">

    <title><?= htmlspecialchars($title) ?> - Energia 97</title>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

		<!-- carrega light mode -->
		<script>
			// Carregar tema ANTES de renderizar (padrão: dark)
			const savedTheme = localStorage.getItem('theme');
			if (savedTheme !== 'light') {
			document.body.classList.add('dark');
			}
		</script>

    <!-- include - header -->
    <?php Core::requireInclude('frontend/includes/_dash-header.php', true); ?>

		<!-- breadcrumb -->
		<?= Core::renderBreadcrumb([
			['Home', '/'],
			['Dashboard', '/dashboard'],
			[htmlspecialchars($title)]
		]) ?>


		<!-- MAIN CONTAINER -->
		<main class="l-main">

			<!-- include - aside -->
			<?php Core::requireInclude('frontend/includes/_aside.php', true); ?>

			<!-- CONTENT -->
			<div class="l-content">

				<?php
				// Renderizar blocos do Page Builder
				echo PageBuilder::render($slug);
				?>

			</div>

		</main>

	</body>

	<!-- Theme Toggle JS -->
	<script src="<?= url('/assets/js/theme-toggle-min.js') ?>"></script>

	<!-- Dashboard JS -->
	<script src="<?= url('/assets/js/dashboard-min.js') ?>"></script>

	<?php // Core::requireInclude('frontend/includes/_footer.php', true); ?>

	<script>lucide.createIcons();</script>

</html>
