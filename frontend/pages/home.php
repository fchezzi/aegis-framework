<?php
  // Definir título e slug da página
  // Quando criado via admin, {NAME} e {SLUG} são substituídos
  // Quando carregado via rota, $pageTitle e $pageSlug são definidos
  $title = isset($pageTitle) ? $pageTitle : '{NAME}';
  $slug = isset($pageSlug) ? $pageSlug : '{SLUG}';
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head -->
    <?php Core::requireInclude('frontend/includes/_head.php', true); ?>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>

    THIS IS HOME

	</body>

	<script>lucide.createIcons();</script>

</html>
