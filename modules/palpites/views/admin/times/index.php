<?php
/**
 * Times - Listagem
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

// Cache: Listagem de times (5 minutos)
$times = SimpleCache::remember('palpites_times_list', function() use ($db) {
    return $db->query("SELECT * FROM tbl_times ORDER BY nome ASC");
}, 300);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Times - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>
        .escudo-thumb {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 5px;
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Times</h1>
            <div style="display: flex; gap: 10px;">
                <a href="<?= url('/admin/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
                <a href="<?= url('/admin/palpites/times/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="plus"></i> Novo Time
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <table class="m-pagebase__table">
            <thead>
                <tr>
                    <th style="width: 80px; text-align: center;">Escudo</th>
                    <th>Nome</th>
                    <th style="width: 100px;">Sigla</th>
                    <th style="text-align: center; width: 200px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($times as $t): ?>
                <tr>
                    <td style="text-align: center;">
                        <?php if (!empty($t['escudo_url'])): ?>
                            <img src="<?= Upload::url($t['escudo_url']) ?>" alt="Escudo" class="escudo-thumb">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; border-radius: 8px; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto;">
                                <i data-lucide="shield"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($t['nome']) ?></td>
                    <td>
                        <span class="m-pagebase__badge m-pagebase__badge--core"><?= htmlspecialchars($t['sigla']) ?></span>
                    </td>
                    <td style="text-align: center;">
                        <div class="m-pagebase__actions">
                            <a href="<?= url('/admin/palpites/times/' . $t['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                                <i data-lucide="pencil"></i> Editar
                            </a>
                            <form method="POST" action="<?= url('/admin/palpites/times/' . $t['id'] . '/delete') ?>" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este time?')">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto">
                                    <i data-lucide="trash-2"></i> Deletar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
