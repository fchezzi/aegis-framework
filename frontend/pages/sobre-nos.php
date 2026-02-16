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
        require_once ROOT_PATH . 'frontend/includes/_members-head.php';
    } else {
        require_once ROOT_PATH . 'frontend/includes/_head.php';
    }
    ?>

	</head>

	<body>

    <!-- include - gtm-body -->
    <?php Core::requireInclude('frontend/includes/_gtm-body.php', true); ?>	


	</body>

	<?php // Core::requireInclude('frontend/includes/_footer.php', true); ?>
