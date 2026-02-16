<?php
/**
 * Jogos - Listagem
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

// Cache: Listagem de jogos (3 minutos)
$jogos = SimpleCache::remember('palpites_jogos_list', function() use ($db) {
    return $db->query("
        SELECT
            j.*,
            tm.nome as time_mandante_nome,
            tm.sigla as time_mandante_sigla,
            tv.nome as time_visitante_nome,
            tv.sigla as time_visitante_sigla
        FROM tbl_jogos_palpites j
        LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
        LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
        ORDER BY j.data_jogo DESC
    ");
}, 180);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>
        .date-badge {
            background: #3b0764;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 12px;
            display: inline-block;
        }
        .score {
            font-weight: 700;
            color: #2d3748;
            font-size: 16px;
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 8px;
        }
        .no-score {
            color: #adb5bd;
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Jogos</h1>
            <div style="display: flex; gap: 10px;">
                <a href="<?= url('/admin/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
                <a href="<?= url('/admin/palpites/jogos/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="plus"></i> Novo Jogo
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
                    <th style="width: 120px;">Data</th>
                    <th>Jogo</th>
                    <th style="width: 200px;">Campeonato</th>
                    <th style="width: 120px;">Rodada</th>
                    <th style="width: 80px; text-align: center;">Status</th>
                    <th style="width: 100px; text-align: center;">Resultado</th>
                    <th style="text-align: center; width: 350px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jogos as $j): ?>
                <tr>
                    <td>
                        <span class="date-badge">
                            <?= date('d/m/Y', strtotime($j['data_jogo'])) ?>
                        </span>
                    </td>
                    <td style="font-weight: 600;">
                        <?= htmlspecialchars($j['time_mandante_nome']) ?>
                        <span style="color: #718096; font-weight: 400; margin: 0 8px;">vs</span>
                        <?= htmlspecialchars($j['time_visitante_nome']) ?>
                    </td>
                    <td><?= htmlspecialchars($j['campeonato']) ?></td>
                    <td><?= htmlspecialchars($j['rodada'] ?? '-') ?></td>
                    <td style="text-align: center;">
                        <?php if ($j['ativo']): ?>
                            <span class="m-pagebase__badge m-pagebase__badge--success">Ativo</span>
                        <?php else: ?>
                            <span class="m-pagebase__badge m-pagebase__badge--inactive">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($j['gols_mandante_real'] !== null): ?>
                            <span class="score"><?= $j['gols_mandante_real'] ?> x <?= $j['gols_visitante_real'] ?></span>
                        <?php else: ?>
                            <span class="no-score">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <div class="m-pagebase__actions">
                            <?php if ($j['ativo']): ?>
                                <a href="<?= url('/admin/palpites/palpites/ao-vivo?jogo_id=' . $j['id']) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--builder m-pagebase__btn--widthauto">
                                    <i data-lucide="file-text"></i> Palpites
                                </a>
                            <?php endif; ?>
                            <a href="<?= url('/admin/palpites/resultados/' . $j['id']) ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--view m-pagebase__btn--widthauto">
                                <i data-lucide="trophy"></i> Resultado
                            </a>
                            <a href="<?= url('/admin/palpites/jogos/' . $j['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                                <i data-lucide="pencil"></i> Editar
                            </a>
                            <form method="POST" action="<?= url('/admin/palpites/jogos/' . $j['id'] . '/delete') ?>" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este jogo?')">
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
