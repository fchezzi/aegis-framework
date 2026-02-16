<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Novo Grupo - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php
  $user = Auth::user();
  require_once __DIR__ . '/../../includes/header.php';
  ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Novo Grupo</h1>
      <a href="<?= url('/admin/groups') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
        <i data-lucide="arrow-left"></i> Voltar
      </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert--error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="m-pagebase__form-container">
      <form method="POST" action="<?= url('/admin/groups') ?>" class="m-pagebase__form">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-label">Nome do Grupo:</label>
          <input
            type="text"
            name="name"
            class="m-pagebase__form-input"
            placeholder="Ex: Editores, Moderadores..."
            required
          >
        </div>

        <div class="m-pagebase__form-group">
          <label class="m-pagebase__form-label">Descrição:</label>
          <textarea
            name="description"
            class="m-pagebase__form-textarea"
            placeholder="Descrição opcional do grupo..."
          ></textarea>
          <div class="m-pagebase__form-help">
            Descreva o propósito e responsabilidades deste grupo.
          </div>
        </div>

        <div class="m-pagebase__form-actions">
          <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
            <i data-lucide="plus"></i> Criar Grupo
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
