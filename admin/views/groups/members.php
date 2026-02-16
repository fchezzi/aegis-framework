<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Membros do Grupo - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php
  $user = Auth::user();
  require_once __DIR__ . '/../../includes/header.php';
  ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Membros do Grupo</h1>
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
      <form method="POST" action="<?= url('/admin/groups/' . $group['id'] . '/members') ?>" class="m-pagebase__form">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <h3 class="m-pagebase__section-title">Membros do Grupo</h3>

        <div class="m-pagebase__form-group">
          <div class="m-pagebase__form-help">
            Marque os membros que farão parte do grupo "<?= htmlspecialchars($group['name']) ?>".
          </div>

          <?php if (empty($allMembers)): ?>
            <div class="m-pagebase__empty">
              <i data-lucide="user-x"></i>
              <p>Nenhum membro cadastrado ainda.</p>
              <p>
                <a href="<?= url('/admin/members/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                  <i data-lucide="plus"></i> Criar primeiro membro
                </a>
              </p>
            </div>
          <?php else: ?>
            <?php foreach ($allMembers as $member): ?>
              <label class="m-pagebase__form-checkbox">
                <input
                  type="checkbox"
                  name="members[]"
                  value="<?= $member['id'] ?>"
                  <?= $member['in_group'] ? 'checked' : '' ?>
                >
                <?= htmlspecialchars($member['name']) ?>
                <span class="m-pagebase__meta">(<?= htmlspecialchars($member['email']) ?>)</span>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if (!empty($allMembers)): ?>
          <div class="m-pagebase__form-actions">
            <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
              <i data-lucide="save"></i> Salvar Membros
            </button>
            <a href="<?= url('/admin/groups') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
              <i data-lucide="x"></i> Cancelar
            </a>
          </div>
        <?php endif; ?>
      </form>
    </div>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>

</html>
