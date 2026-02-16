<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Novo Include - <?= ADMIN_NAME ?></title>
</head>

<body class="m-includesbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-includes">

        <div class="m-pagebase__header">
            <h1>Novo Include</h1>
            <a href="<?= url('/admin/includes') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="m-components__alert m-components__alert--error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="m-includes__form-container m-includes__form-container--narrow">
            <form method="POST" action="<?= url('/admin/includes') ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-includes__form-group">
                    <label for="name" class="m-includes__form-label">Nome do Include *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        placeholder="Ex: navigation"
                        pattern="[a-zA-Z0-9-]+"
                        title="Apenas letras, números e hífen"
                        class="m-includes__form-input"
                    >
                    <div class="m-includes__form-help">O arquivo será criado como <code>_{nome}.php</code>. Ex: "navigation" vira <code>_navigation.php</code></div>
                </div>

                <div class="m-includes__form-group">
                    <label for="code" class="m-includes__form-label">Código HTML/PHP *</label>
                    <textarea
                        id="code"
                        name="code"
                        placeholder="<nav>&#10;    <ul>&#10;        <li><a href=&quot;#&quot;>Home</a></li>&#10;        <li><a href=&quot;#&quot;>Sobre</a></li>&#10;    </ul>&#10;</nav>"
                        class="m-includes__form-textarea"
                    ></textarea>
                    <div class="m-includes__form-help">Cole o código HTML ou PHP do include. Um comentário com o nome será adicionado automaticamente no topo.</div>
                </div>

                <div class="m-includes__form-group">
                    <label class="m-includes__form-checkbox">
                        <input type="checkbox" name="is_critical" value="1">
                        <span>Marcar como crítico (não poderá ser deletado)</span>
                    </label>
                    <div class="m-includes__form-help">Includes críticos aparecem primeiro na listagem e têm proteção extra contra exclusão.</div>
                </div>

                <div class="m-includes__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Criar Include
                    </button>
                    <a href="<?= url('/admin/includes') ?>" class="m-pagebase__btn-secondary">
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
