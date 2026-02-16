<?php $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Membro - <?= ADMIN_NAME ?></title>
</head>
<body class="m-membersbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Membro</h1>
            <a href="<?= url('/admin/members') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/members/' .  $member['id'] ) ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Nome:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($member['name']) ?>" required class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($member['email']) ?>" required class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Foto de Perfil:</label>
                    <?php if (!empty($member['avatar'])): ?>
                        <img src="<?= url($member['avatar']) ?>" alt="Avatar" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 10px; display: block;">
                    <?php endif; ?>
                    <input type="file" name="avatar" accept="image/*" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">JPG, PNG ou GIF. Máximo 2MB.</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Nova Senha (deixe vazio para manter):</label>
                    <input type="password" name="password" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 especial</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Status:</label>
                    <select name="ativo" class="m-pagebase__form-select">
                        <option value="1" <?= $member['ativo'] == 1 ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= $member['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-label">Grupos:</label>
                    <?php foreach ($allGroups as $group): ?>
                        <label class="m-pagebase__form-checkbox">
                            <input type="checkbox" name="groups[]" value="<?= $group['id'] ?>"
                                <?= in_array($group['id'], $memberGroupIds) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($group['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="save"></i> Salvar Alterações
                </button>
            </form>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
