<?php
/**
 * Jogos - Editar
 */

Auth::require();
$user = Auth::user();

$id = $_GET['id'] ?? null;

if (!$id) {
    Core::redirect('/admin/palpites/jogos?error=' . urlencode('ID não informado'));
}

$db = DB::connect();
$jogo = $db->select('tbl_jogos_palpites', ['id' => $id]);

if (empty($jogo)) {
    Core::redirect('/admin/palpites/jogos?error=' . urlencode('Jogo não encontrado'));
}

$jogo = $jogo[0];
$times = $db->query("SELECT * FROM tbl_times ORDER BY nome ASC");

$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jogo - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Editar Jogo</h1>
            <a href="<?= url('/admin/palpites/jogos') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="m-pagebase__form-container">
            <form method="POST" action="<?= url('/admin/palpites/jogos/' . $id . '/update') ?>" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                <input type="hidden" name="id" value="<?= $jogo['id'] ?>">

                <div class="m-pagebase__form-group">
                    <label for="time_mandante_id" class="m-pagebase__form-label">Time Mandante *</label>
                    <select id="time_mandante_id" name="time_mandante_id" required class="m-pagebase__form-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($times as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == $jogo['time_mandante_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nome']) ?> (<?= htmlspecialchars($t['sigla']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="time_visitante_id" class="m-pagebase__form-label">Time Visitante *</label>
                    <select id="time_visitante_id" name="time_visitante_id" required class="m-pagebase__form-select">
                        <option value="">Selecione...</option>
                        <?php foreach ($times as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $t['id'] == $jogo['time_visitante_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nome']) ?> (<?= htmlspecialchars($t['sigla']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="data_jogo" class="m-pagebase__form-label">Data do Jogo *</label>
                    <input type="date" id="data_jogo" name="data_jogo" value="<?= htmlspecialchars($jogo['data_jogo']) ?>" required class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="campeonato" class="m-pagebase__form-label">Campeonato *</label>
                    <input type="text" id="campeonato" name="campeonato" value="<?= htmlspecialchars($jogo['campeonato']) ?>" required placeholder="Ex: Brasileirão 2025" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="rodada" class="m-pagebase__form-label">Rodada</label>
                    <input type="text" id="rodada" name="rodada" value="<?= htmlspecialchars($jogo['rodada'] ?? '') ?>" placeholder="Ex: 15ª Rodada" class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" <?= $jogo['ativo'] ? 'checked' : '' ?>>
                        Ativo (aceita palpites)
                    </label>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto">
                        <i data-lucide="save"></i> Salvar Alterações
                    </button>
                    <a href="<?= url('/admin/palpites/jogos') ?>" class="m-pagebase__btn-secondary m-pagebase__btn--widthauto">
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
