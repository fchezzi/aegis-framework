<?php
/**
 * Times - Criar
 */

Auth::require();
$user = Auth::user();

$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Time - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Novo Time</h1>
            <a href="<?= url('/admin/palpites/times') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/palpites/times/store') ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="nome" class="m-pagebase__form-label">Nome do Time *</label>
                    <input type="text" id="nome" name="nome" required class="m-pagebase__form-input" placeholder="Ex: Flamengo">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="sigla" class="m-pagebase__form-label">Sigla *</label>
                    <input type="text" id="sigla" name="sigla" required maxlength="10" class="m-pagebase__form-input" placeholder="Ex: FLA">
                    <div class="m-pagebase__form-help">A sigla será usada como nome do arquivo do escudo</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="escudo" class="m-pagebase__form-label">Escudo do Time</label>
                    <input type="file" id="escudo" name="escudo" accept="image/*" class="m-pagebase__form-file">
                    <div class="m-pagebase__form-help">Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 5MB</div>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto" id="submitBtn">
                        <i data-lucide="save"></i> Salvar Time
                    </button>
                    <a href="<?= url('/admin/palpites/times') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
                        <i data-lucide="x"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Proteção contra submit duplo
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        let isSubmitting = false;

        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader"></i> Salvando...';
            lucide.createIcons();
        });
    </script>
</body>
</html>
