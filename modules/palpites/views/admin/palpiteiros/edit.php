<?php
/**
 * Palpiteiros - Editar
 */

Auth::require();
$user = Auth::user();

$id = $_GET['id'] ?? null;

if (!$id) {
    Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('ID não informado'));
}

$db = DB::connect();
$palpiteiro = $db->select('tbl_palpiteiros', ['id' => $id]);

if (empty($palpiteiro)) {
    Core::redirect('/admin/palpites/palpiteiros?error=' . urlencode('Palpiteiro não encontrado'));
}

$palpiteiro = $palpiteiro[0];
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Palpiteiro - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Palpiteiro</h1>
            <a href="<?= url('/admin/palpites/palpiteiros') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/palpites/palpiteiros/' . $id . '/update') ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="nome" class="m-pagebase__form-label">Nome *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($palpiteiro['nome']) ?>" required class="m-pagebase__form-input" placeholder="Digite o nome do palpiteiro">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="foto" class="m-pagebase__form-label">Foto do Perfil</label>

                    <?php if (!empty($palpiteiro['foto_url'])): ?>
                        <div class="m-pagebase__form-preview">
                            <strong>Foto atual:</strong>
                            <img src="<?= Upload::url($palpiteiro['foto_url']) ?>" alt="Foto atual" class="m-pagebase__form-preview-img">
                        </div>
                    <?php endif; ?>

                    <input type="file" id="foto" name="foto" accept="image/*" class="m-pagebase__form-file">
                    <div class="m-pagebase__form-help">
                        <?php if (!empty($palpiteiro['foto_url'])): ?>
                            Deixe em branco para manter a foto atual.
                        <?php endif; ?>
                        Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 5MB
                    </div>
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" id="ativo" <?= $palpiteiro['ativo'] ? 'checked' : '' ?>>
                        Ativo
                    </label>
                    <div class="m-pagebase__form-help">Palpiteiros inativos não aparecem nas telas de exibição</div>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Salvar Alterações
                    </button>
                    <a href="<?= url('/admin/palpites/palpiteiros') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
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
