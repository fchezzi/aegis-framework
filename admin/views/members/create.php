<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Novo Membro - Admin</title>
</head>
<body>
    <div class="container">
        <a href="<?= url('/admin/members') ?>" class="back">← Voltar</a>

        <h1>Novo Membro</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="<?= url('/admin/members') ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="password" required>
                <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 especial</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="ativo" value="1" checked>
                    Ativo
                </label>
                <small>Membros inativos não conseguem fazer login</small>
            </div>

            <div class="form-group">
                <label>Grupos:</label>
                <div class="checkbox-group">
                    <?php foreach ($groups as $group): ?>
                        <label>
                            <input type="checkbox" name="groups[]" value="<?= $group['id'] ?>">
                            <?= htmlspecialchars($group['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn">Criar Membro</button>
        </form>
    </div>
</body>
</html>
