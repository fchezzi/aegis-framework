<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Grupos - <?= ADMIN_NAME ?></title>
</head>

<body class="m-pagebasebody">

  <?php
  $user = Auth::user();
  require_once __DIR__ . '/../../includes/header.php';
  ?>

  <main class="m-pagebase">

    <div class="m-pagebase__header">
      <h1>Grupos (<?= count($groups) ?>)</h1>
      <a href="<?= url('/admin/groups/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
        <i data-lucide="plus"></i> Novo Grupo
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

    <?php if (empty($groups)): ?>
      <div class="m-pagebase__empty">
        <i data-lucide="users"></i>
        <p>Nenhum grupo criado ainda.</p>
        <p>
          <a href="<?= url('/admin/groups/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
            <i data-lucide="plus"></i> Criar primeiro grupo
          </a>
        </p>
      </div>
    <?php else: ?>
      <table class="m-pagebase__table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Membros</th>
            <th>Permissões</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groups as $group): ?>
          <tr>
            <td>
              <?= htmlspecialchars($group['name']) ?>
            </td>
            <td>
              <?= htmlspecialchars($group['description'] ?? '') ?>
            </td>
            <td>
              <span class="m-pagebase__badge m-pagebase__badge--core">
                <?= $group['member_count'] ?> membro<?= $group['member_count'] != 1 ? 's' : '' ?>
              </span>
            </td>
            <td>
              <span class="m-pagebase__badge m-pagebase__badge--success">
                <?= $group['permission_count'] ?> permiss<?= $group['permission_count'] != 1 ? 'ões' : 'ão' ?>
              </span>
            </td>
            <td class="m-pagebase__actions">
              <a href="<?= url('/admin/groups/' . $group['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                <i data-lucide="pencil"></i> Editar
              </a>
              <a href="<?= url('/admin/groups/' . $group['id'] . '/members') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--view m-pagebase__btn--widthauto">
                <i data-lucide="users"></i> Membros
              </a>
              <a href="<?= url('/admin/groups/' . $group['id'] . '/permissions') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--builder m-pagebase__btn--widthauto">
                <i data-lucide="shield"></i> Permissões
              </a>
              <form method="POST" action="<?= url('/admin/groups/' . $group['id'] . '/delete') ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o grupo:&#10;<?= htmlspecialchars($group['name']) ?>&#10;&#10;Membros: <?= $group['member_count'] ?>&#10;Permissões: <?= $group['permission_count'] ?>&#10;&#10;Esta ação NÃO pode ser desfeita!">
                  <i data-lucide="trash-2"></i> Deletar
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>

</body>

</html>
