<?php
$csrf_token = Security::generateCSRF();
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="pt-BR">

  <head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Login - <?= ADMIN_NAME ?></title>
  </head>

  <body class="page-login">

    <div class="m-login">

      <div class="m-login__header">
        <h1><?= ADMIN_NAME ?></h1>
        <p><?= ADMIN_SUBTITLE ?></p>
      </div>

      <div class="m-login__body">
        <?php if ($error): ?>
          <div class="m-login__alert">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <div class="m-login__form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
          </div>
          <div class="m-login__form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required>
          </div>
          <button type="submit" class="m-login__btn">Entrar</button>
        </form>
      </div>

    </div>
    
  </body>

</html>
