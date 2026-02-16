<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Permissões do Grupo - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php
  $user = Auth::user();
  require_once __DIR__ . '/../../includes/header.php';
  ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Permissões do Grupo</h1>
      <a href="<?= url('/admin/groups') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
        <i data-lucide="arrow-left"></i> Voltar
      </a>
    </div>

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

    <div class="m-pagebase__form-container">
      <form method="POST" action="<?= url('/admin/groups/' . $group['id'] . '/permissions') ?>" class="m-pagebase__form">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <!-- PÁGINAS -->
        <h3 class="m-pagebase__section-title">Páginas</h3>

        <div class="m-pagebase__form-group">
          <div class="m-pagebase__form-help">
            Selecione quais páginas os membros deste grupo poderão acessar.
          </div>

          <?php if (empty($pages)): ?>
            <div class="m-pagebase__empty">
              <i data-lucide="file-x"></i>
              <p>Nenhuma página disponível.</p>
            </div>
          <?php else: ?>
            <?php foreach ($pages as $page): ?>
              <label class="m-pagebase__form-checkbox">
                <input
                  type="checkbox"
                  name="pages[]"
                  value="<?= $page['id'] ?>"
                  <?= isset($page['has_access']) && $page['has_access'] ? 'checked' : '' ?>
                >
                <?= htmlspecialchars($page['title']) ?>
                <code class="m-pagebase__code m-pagebase__code--sm">/<?= htmlspecialchars($page['slug']) ?></code>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- MÓDULOS -->
        <?php if (!empty($modules)): ?>
          <h3 class="m-pagebase__section-title m-pagebase__section-title--spaced">Módulos</h3>

          <div class="m-pagebase__form-group">
            <div class="m-pagebase__form-help">
              Selecione quais módulos os membros deste grupo poderão acessar.
            </div>

            <?php
            $hasPublicModules = false;
            foreach ($modules as $module) {
              if (!empty($module['public_url'])) {
                $hasPublicModules = true;
                break;
              }
            }
            ?>

            <?php if (!$hasPublicModules): ?>
              <div class="m-pagebase__empty">
                <i data-lucide="package-x"></i>
                <p>Nenhum módulo com URL pública disponível.</p>
              </div>
            <?php else: ?>
              <?php foreach ($modules as $module): ?>
                <?php if (!empty($module['public_url'])): ?>
                  <label class="m-pagebase__form-checkbox">
                    <input
                      type="checkbox"
                      name="modules[]"
                      value="<?= htmlspecialchars($module['name']) ?>"
                      <?= isset($module['has_access']) && $module['has_access'] ? 'checked' : '' ?>
                    >
                    <?= htmlspecialchars($module['label']) ?>
                    <code class="m-pagebase__code m-pagebase__code--sm"><?= htmlspecialchars($module['public_url']) ?></code>
                  </label>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="m-pagebase__form-actions">
          <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
            <i data-lucide="save"></i> Salvar Permissões
          </button>
          <a href="<?= url('/admin/groups') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
            <i data-lucide="x"></i> Cancelar
          </a>
        </div>
      </form>
    </div>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>

</html>
