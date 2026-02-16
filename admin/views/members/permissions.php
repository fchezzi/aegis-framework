<?php $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Permissões de <?= htmlspecialchars($member['name']) ?> - <?= ADMIN_NAME ?></title>
</head>
<body class="m-membersbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Permissões Individuais - <?= htmlspecialchars($member['name']) ?></h1>
            <a href="<?= url('/admin/members') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <form method="POST" action="<?= url('/admin/members/' .  $member['id']  . '/permissions') ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

            <table class="m-members__table">
                <thead>
                    <tr>
                        <th>Conteúdo</th>
                        <th>Tipo</th>
                        <th>Status Atual</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contents as $content): ?>
                        <tr>
                            <td><?= htmlspecialchars($content['title']) ?></td>
                            <td><?= htmlspecialchars($content['type']) ?></td>
                            <td>
                                <?php if ($content['is_public']): ?>
                                    <span class="m-pagebase__badge">Público</span>
                                <?php elseif ($content['individual_permission'] === 1): ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--success">Permitido</span>
                                <?php elseif ($content['individual_permission'] === 0): ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--danger">Bloqueado</span>
                                <?php elseif ($content['has_group_access']): ?>
                                    <span class="m-members__badge">Via Grupo</span>
                                <?php else: ?>
                                    Sem Acesso
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="permissions[<?= $content['id'] ?>]" class="m-pagebase__form-select">
                                    <option value="">-- Sem alteração --</option>
                                    <option value="allow">Permitir</option>
                                    <option value="deny">Bloquear</option>
                                    <option value="remove">Remover override</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>
            <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="save"></i> Salvar Permissões
            </button>
        </form>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
