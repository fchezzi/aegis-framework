<header class="m-dashboard__header">
  <h1><a href="<?= url('/admin/dashboard') ?>"><?= ADMIN_NAME ?></a></h1>
  <div class="m-dashboard__user-info">
    <span>Olá, <?= htmlspecialchars($user['name']) ?></span>
    <a href="<?= url('/admin/logout') ?>" class="m-dashboard__header-btn">Sair</a>
  </div>
</header>
