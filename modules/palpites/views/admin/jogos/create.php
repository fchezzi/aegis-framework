<?php
/**
 * Jogos - Criar
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();
$times = $db->query("SELECT * FROM tbl_times ORDER BY nome ASC");

$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Jogo - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Novo Jogo</h1>
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
            <form method="POST" action="<?= url('/admin/palpites/jogos/store') ?>" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-group">
                    <label for="time_mandante_id" class="m-pagebase__form-label">Time Mandante *</label>
                    <select id="time_mandante_id" name="time_mandante_id" required class="m-pagebase__form-select">
                        <option value="">Selecione o time mandante...</option>
                        <?php foreach ($times as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['nome']) ?> (<?= htmlspecialchars($t['sigla']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="time_visitante_id" class="m-pagebase__form-label">Time Visitante *</label>
                    <select id="time_visitante_id" name="time_visitante_id" required class="m-pagebase__form-select">
                        <option value="">Selecione o time visitante...</option>
                        <?php foreach ($times as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['nome']) ?> (<?= htmlspecialchars($t['sigla']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-pagebase__form-group">
                    <label for="data_jogo" class="m-pagebase__form-label">Data do Jogo *</label>
                    <input type="date" id="data_jogo" name="data_jogo" required class="m-pagebase__form-input">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="campeonato" class="m-pagebase__form-label">Campeonato *</label>
                    <input type="text" id="campeonato" name="campeonato" required class="m-pagebase__form-input" placeholder="Ex: Brasileirão Série A 2025">
                </div>

                <div class="m-pagebase__form-group">
                    <label for="rodada" class="m-pagebase__form-label">Rodada</label>
                    <input type="text" id="rodada" name="rodada" class="m-pagebase__form-input" placeholder="Ex: 15ª Rodada">
                </div>

                <div class="m-pagebase__form-group">
                    <label class="m-pagebase__form-checkbox">
                        <input type="checkbox" name="ativo" value="1" checked>
                        Ativo (aceita palpites)
                    </label>
                </div>

                <div class="m-pagebase__form-actions">
                    <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto" id="submitBtn">
                        <i data-lucide="save"></i> Salvar Jogo
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
