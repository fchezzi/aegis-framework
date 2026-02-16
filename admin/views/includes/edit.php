<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Editar Include - <?= ADMIN_NAME ?></title>
</head>

<body class="m-includesbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-includes">

        <div class="m-pagebase__header">
            <div>
                <h1 style="display: flex; align-items: center; gap: 10px;">
                    Editar Include: <code><?= htmlspecialchars($name) ?></code>
                    <?php if ($isProtected): ?>
                        <span class="m-includes__badge-inline">CRÍTICO</span>
                    <?php endif; ?>
                </h1>
            </div>
            <a href="<?= url('/admin/includes') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="m-components__alert m-components__alert--success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="m-components__alert m-components__alert--error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if ($isProtected): ?>
            <div class="m-includes__warning">
                <strong>⚠️ Include Crítico</strong><br>
                Este arquivo é essencial para o funcionamento do sistema. Tenha cuidado ao editar.<br>
                Um backup será criado automaticamente ao salvar.
            </div>
        <?php endif; ?>

        <div class="m-includes__form-container">
            <form method="POST" action="<?= url('/admin/includes/' . $name) ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-includes__form-group">
                    <label for="code" class="m-includes__form-label">Código HTML/PHP *</label>
                    <textarea
                        id="code"
                        name="code"
                        required
                        class="m-includes__form-textarea m-includes__form-textarea--large"
                    ><?= htmlspecialchars($code) ?></textarea>
                    <div class="m-includes__form-help">
                        Edite o código do include.
                        <?php if ($hasBackup): ?>
                            Backup disponível - use o botão "Restaurar Backup" para reverter.
                        <?php else: ?>
                            Um backup será criado ao salvar.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="m-includes__form-group">
                    <label class="m-includes__form-checkbox">
                        <input type="checkbox" name="is_critical" value="1" <?= $isProtected ? 'checked' : '' ?>>
                        <span>Marcar como crítico (não poderá ser deletado)</span>
                    </label>
                    <div class="m-includes__form-help">Includes críticos aparecem primeiro na listagem e têm proteção extra contra exclusão.</div>
                </div>

                <div class="m-includes__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Salvar Alterações
                    </button>
                    <a href="<?= url('/admin/includes') ?>" class="m-pagebase__btn-secondary">
                        <i data-lucide="x"></i> Cancelar
                    </a>

                    <?php if ($hasBackup): ?>
                    <form method="POST" action="<?= url('/admin/includes/' . $name . '/restore') ?>" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        <button type="submit" class="m-includes__btn-restore" onclick="return confirm('Restaurar backup? Isso irá sobrescrever o código atual.')">
                            <i data-lucide="refresh-cw"></i> Restaurar Backup
                        </button>
                    </form>
                    <?php endif; ?>
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
