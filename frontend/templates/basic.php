<?php
// Pegar usuário logado (admin ou member)
$user = Auth::user() ?? MemberAuth::member() ?? null;
?>

<!DOCTYPE html>

<html lang="pt-br">

  <head>

    <!-- include - gtm-head -->
    <?php Core::requireInclude('frontend/includes/_gtm-head.php', true); ?>

    <!-- include - head (members ou frontend) -->
    <?php
    if (isset($pageContext) && $pageContext === 'members') {
        Core::requireInclude('frontend/includes/_members-head.php', true);
    } else {
        Core::requireInclude('frontend/includes/_head.php', true);
    }
    ?>

    <meta name="keywords" content="inserir,as,palavras,chave">
    <meta name="description" content="inserir o meta keywords">

    <title>Energia 97 - Dashboard</title>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>	


	</body>

	<?php // Core::requireInclude('frontend/includes/_footer.php', true); ?>
