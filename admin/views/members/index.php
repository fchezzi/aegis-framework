<?php $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Membros - <?= ADMIN_NAME ?></title>
</head>
<body class="m-membersbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Membros (<?= count($members) ?>)</h1>
            <a href="<?= url('/admin/members/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="user-plus"></i> Novo Membro
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <table class="m-members__table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Grupos</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['name']) ?></td>
                        <td><?= htmlspecialchars($member['email']) ?></td>
                        <td>
                            <?php foreach ($member['groups'] as $group): ?>
                                <span class="m-members__badge"><?= htmlspecialchars($group['name']) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($member['ativo']): ?>
                                <span class="m-pagebase__badge m-pagebase__badge--success">ATIVO</span>
                            <?php else: ?>
                                <span class="m-pagebase__badge m-pagebase__badge--inactive">INATIVO</span>
                            <?php endif; ?>
                        </td>
                        <td class="m-pagebase__actions">
                            <a href="<?= url('/admin/members/' .  $member['id']  . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit">
                                <i data-lucide="pencil"></i> Editar
                            </a>
                            <a href="<?= url('/admin/members/' .  $member['id']  . '/permissions') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--builder m-pagebase__btn--widthauto">
                                <i data-lucide="shield"></i> Permissões
                            </a>
                            <form method="POST" action="<?= url('/admin/members/' .  $member['id']  . '/delete') ?>">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto" data-confirm-delete="⚠️ ATENÇÃO!&#10;&#10;Deletar o membro:&#10;<?= htmlspecialchars($member['name']) ?>&#10;(<?= htmlspecialchars($member['email']) ?>)&#10;&#10;Esta ação NÃO pode ser desfeita!">
                                    <i data-lucide="trash-2"></i> Deletar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
