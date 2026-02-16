<?php
/**
 * Resultados - Lista de Jogos
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();
$jogos = $db->query("
    SELECT
        j.*,
        tm.nome as time_mandante_nome,
        tm.sigla as time_mandante_sigla,
        tv.nome as time_visitante_nome,
        tv.sigla as time_visitante_sigla,
        COUNT(p.id) as total_palpites
    FROM tbl_jogos_palpites j
    LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
    LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
    LEFT JOIN tbl_palpites p ON j.id = p.jogo_id
    GROUP BY j.id, tm.nome, tm.sigla, tv.nome, tv.sigla
    ORDER BY j.data_jogo DESC
");

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>

        .games-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .game-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: grid;
            grid-template-columns: 120px 1fr 150px 200px 150px;
            gap: 25px;
            align-items: center;
        }

        .game-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .game-date {
            text-align: center;
            background: #3b0764;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
        }

        .game-date .day {
            font-size: 32px;
            display: block;
            line-height: 1;
        }

        .game-date .month {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .game-info h3 {
            font-size: 22px;
            color: #2d3748;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .game-info .vs {
            color: #a0aec0;
            margin: 0 8px;
            font-weight: 500;
        }

        .game-info .campeonato {
            color: #718096;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .palpites-count {
            text-align: center;
        }

        .palpites-count .number {
            font-size: 36px;
            font-weight: 900;
            color: #3b0764;
            display: block;
            line-height: 1;
        }

        .palpites-count .label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resultado-input {
            width: 60px;
            height: 45px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .resultado-input:focus {
            outline: none;
            border-color: #3b0764;
            box-shadow: 0 0 0 3px rgba(59, 7, 100, 0.1);
        }

        .empty-card {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .empty-card h2 {
            color: #666;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .empty-card p {
            color: #999;
            font-size: 16px;
        }

        @media (max-width: 1200px) {
            .game-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Gerenciar Resultados</h1>
            <a href="<?= url('/admin/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
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

        <?php if (empty($jogos)): ?>
            <div class="empty-card">
                <h2>
                    <i data-lucide="clipboard" style="width: 24px; height: 24px;"></i>
                    Nenhum jogo cadastrado
                </h2>
                <p>Cadastre jogos primeiro para poder gerenciar resultados.</p>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($jogos as $j): ?>
                    <div class="game-card">
                        <!-- Data -->
                        <div class="game-date">
                            <span class="day"><?= date('d', strtotime($j['data_jogo'])) ?></span>
                            <span class="month"><?= date('M', strtotime($j['data_jogo'])) ?></span>
                        </div>

                        <!-- Info do Jogo -->
                        <div class="game-info">
                            <h3>
                                <?= htmlspecialchars($j['time_mandante_sigla']) ?>
                                <span class="vs">vs</span>
                                <?= htmlspecialchars($j['time_visitante_sigla']) ?>
                            </h3>
                            <div class="campeonato">
                                <i data-lucide="trophy" style="width: 14px; height: 14px;"></i>
                                <?= htmlspecialchars($j['campeonato']) ?>
                                <?php if ($j['rodada']): ?>
                                    • <?= htmlspecialchars($j['rodada']) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Total de Palpites -->
                        <div class="palpites-count">
                            <span class="number"><?= $j['total_palpites'] ?></span>
                            <span class="label">Palpites</span>
                        </div>

                        <!-- Formulário de Resultado -->
                        <form id="resultado-form-<?= $j['id'] ?>" method="POST" action="<?= url('/admin/palpites/resultados/' . $j['id'] . '/cadastrar') ?>" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                            <input type="number"
                                   name="gols_mandante"
                                   min="0"
                                   max="50"
                                   placeholder="0"
                                   value="<?= $j['gols_mandante_real'] ?? '' ?>"
                                   required
                                   class="resultado-input">

                            <span style="font-size: 18px; font-weight: bold; color: #999;">×</span>

                            <input type="number"
                                   name="gols_visitante"
                                   min="0"
                                   max="50"
                                   placeholder="0"
                                   value="<?= $j['gols_visitante_real'] ?? '' ?>"
                                   required
                                   class="resultado-input">
                        </form>

                        <!-- Ação -->
                        <div>
                            <button type="submit" form="resultado-form-<?= $j['id'] ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                                <i data-lucide="save"></i>
                                Salvar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
