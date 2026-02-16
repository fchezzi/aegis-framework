<?php
Auth::require();
$user = Auth::user();
$error = $_SESSION['error'] ?? '';
if ($error) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Categoria - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-blogbody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Categoria</h1>
            <a href="<?= url('/admin/blog/categorias') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPosts > 0): ?>
            <div class="m-pagebase__info">
                Esta categoria possui <?= $totalPosts ?> post(s)
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/blog/categorias/' . $categoria['id'] . '/update') ?>" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="nome" class="m-pagebase__form-label">Nome *</label>
                    <input type="text" id="nome" name="nome" required maxlength="100" value="<?= htmlspecialchars($categoria['nome']) ?>" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="slug" class="m-pagebase__form-label">Slug (URL) *</label>
                    <input type="text" id="slug" name="slug" required maxlength="100" value="<?= htmlspecialchars($categoria['slug']) ?>" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Usado na URL da categoria</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="descricao" class="m-pagebase__form-label">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" class="m-pagebase__form-textarea"><?= htmlspecialchars($categoria['descricao'] ?? '') ?></textarea>
                    <div class="m-pagebase__form-help">Breve descrição da categoria</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="ordem" class="m-pagebase__form-label">Ordem</label>
                    <input type="number" id="ordem" name="ordem" value="<?= $categoria['ordem'] ?>" min="0" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Ordem de exibição (menor número aparece primeiro)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" <?= $categoria['ativo'] ? 'checked' : '' ?>>
                        Categoria ativa
                    </label>
                    <div class="m-pagebase__form-help">Desmarque para ocultar a categoria</div>
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

        // Auto-gerar slug a partir do nome
        document.getElementById('nome').addEventListener('input', function(e) {
            const slug = e.target.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
</body>
</html>
