<?php
Auth::require();
$user = Auth::user();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Artigo - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Artigo</h1>
            <a href="<?= url('/admin/artigos') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= nl2br(htmlspecialchars($error)) ?>
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/artigos/atualizar/' . $artigo['id']) ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="titulo" class="m-pagebase__form-label">Título *</label>
                    <input type="text" id="titulo" name="titulo" required maxlength="255" value="<?= htmlspecialchars($artigo['titulo']) ?>" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="slug" class="m-pagebase__form-label">Slug (URL) *</label>
                    <input type="text" id="slug" name="slug" required maxlength="255" value="<?= htmlspecialchars($artigo['slug']) ?>" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Gerado automaticamente a partir do título. Edite se necessário.</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="autor" class="m-pagebase__form-label">Autor *</label>
                    <input type="text" id="autor" name="autor" required maxlength="255" value="<?= htmlspecialchars($artigo['autor']) ?>" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="data_artigo" class="m-pagebase__form-label">Data do Artigo *</label>
                    <input type="date" id="data_artigo" name="data_artigo" required value="<?= htmlspecialchars($artigo['data_artigo']) ?>" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">Data de publicação do artigo original</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="introducao" class="m-pagebase__form-label">Introdução *</label>
                    <textarea id="introducao" name="introducao" required rows="5" class="m-pagebase__form-textarea"><?= htmlspecialchars($artigo['introducao']) ?></textarea>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="link_externo" class="m-pagebase__form-label">Link Externo (Artigo Completo)</label>
                    <input type="url" id="link_externo" name="link_externo" maxlength="500" value="<?= htmlspecialchars($artigo['link_externo'] ?? '') ?>" class="m-pagebase__form-input">
                    <div class="m-pagebase__form-help">URL completa para o artigo no site externo (opcional)</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="imagem" class="m-pagebase__form-label">Imagem de Destaque (JPG, PNG, WEBP - máx 5MB)</label>
                    <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/jpg,image/png,image/webp" class="m-pagebase__form-file">
                    <div class="m-pagebase__form-help">Deixe em branco para manter a imagem atual</div>
                    <?php if ($artigo['imagem']): ?>
                        <div class="m-pagebase__form-preview">
                            <strong>Imagem atual:</strong>
                            <img src="<?= url('/storage/uploads/' . $artigo['imagem']) ?>" alt="<?= htmlspecialchars($artigo['titulo']) ?>" class="m-pagebase__form-preview-img">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="arquivo_pdf" class="m-pagebase__form-label">Arquivo PDF do Artigo (máx 10MB)</label>
                    <input type="file" id="arquivo_pdf" name="arquivo_pdf" accept="application/pdf" class="m-pagebase__form-file">
                    <div class="m-pagebase__form-help">Deixe em branco para manter o PDF atual (se houver)</div>
                    <?php if (!empty($artigo['arquivo_pdf'])): ?>
                        <div class="m-pagebase__form-file-current">
                            <strong>PDF atual:</strong>
                            <a href="<?= url('/storage/uploads/' . $artigo['arquivo_pdf']) ?>" target="_blank">
                                <i data-lucide="file-text"></i> <?= htmlspecialchars($artigo['arquivo_pdf'] ?? '') ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Atualizar Artigo
                    </button>
                    <a href="<?= url('/admin/artigos') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
                        <i data-lucide="x"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Auto-slug a partir do título
        document.getElementById('titulo').addEventListener('input', function(e) {
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
