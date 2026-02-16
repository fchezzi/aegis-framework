<?php
/**
 * Times - Editar
 */

Auth::require();
$user = Auth::user();

$id = $_GET['id'] ?? null;

if (!$id) {
    Core::redirect('/admin/palpites/times?error=' . urlencode('ID não informado'));
}

$db = DB::connect();
$time = $db->select('tbl_times', ['id' => $id]);

if (empty($time)) {
    Core::redirect('/admin/palpites/times?error=' . urlencode('Time não encontrado'));
}

$time = $time[0];
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Time - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Time</h1>
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
            <form method="POST" action="<?= url('/admin/palpites/times/' . $id . '/update') ?>" enctype="multipart/form-data" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="nome" class="m-pagebase__form-label">Nome do Time *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($time['nome']) ?>" required class="m-pagebase__form-input" placeholder="Ex: Flamengo">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="sigla" class="m-pagebase__form-label">Sigla *</label>
                    <input type="text" id="sigla" name="sigla" value="<?= htmlspecialchars($time['sigla']) ?>" required maxlength="10" class="m-pagebase__form-input" placeholder="Ex: FLA">
                    <div class="m-pagebase__form-help">A sigla será usada como nome do arquivo do escudo</div>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="escudo" class="m-pagebase__form-label">Escudo do Time</label>

                    <?php if (!empty($time['escudo_url'])): ?>
                        <div class="m-pagebase__form-preview">
                            <strong>Escudo atual:</strong>
                            <img src="<?= Upload::url($time['escudo_url']) ?>" alt="Escudo atual" class="m-pagebase__form-preview-img">
                        </div>
                    <?php endif; ?>

                    <input type="file" id="escudo" name="escudo" accept="image/*" class="m-pagebase__form-file">
                    <div class="m-pagebase__form-help">
                        <?php if (!empty($time['escudo_url'])): ?>
                            Deixe em branco para manter o escudo atual.
                        <?php endif; ?>
                        Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 5MB
                    </div>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Salvar Alterações
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
    </script>
</body>
</html>
