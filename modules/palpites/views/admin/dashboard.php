<?php
/**
 * Dashboard do Módulo Palpites
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

// Cache: Estatísticas (2 minutos)
$stats = SimpleCache::remember('palpites_dashboard_stats', function() use ($db) {
    return $db->query("
        SELECT
            (SELECT COUNT(*) FROM tbl_palpiteiros WHERE ativo = true) as total_palpiteiros,
            (SELECT COUNT(*) FROM tbl_times) as total_times,
            (SELECT COUNT(*) FROM tbl_jogos_palpites) as total_jogos,
            (SELECT COUNT(*) FROM tbl_jogos_palpites WHERE ativo = true) as jogos_ativos
    ")[0];
}, 120);

$total_palpiteiros = $stats['total_palpiteiros'] ?? 0;
$total_times = $stats['total_times'] ?? 0;
$total_jogos = $stats['total_jogos'] ?? 0;
$jogos_ativos = $stats['jogos_ativos'] ?? 0;

// Cache: Ranking TOP 10 (2 minutos) - ORDENADO: 1º pontos, 2º exatos
$ranking = SimpleCache::remember('palpites_dashboard_ranking', function() use ($db) {
    return $db->query("
        SELECT * FROM vw_ranking_palpiteiros
        ORDER BY total_pontos DESC, placares_exatos DESC
        LIMIT 10
    ");
}, 120);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palpites - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #3b0764;
        }
        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
            display: block;
        }
        .stat-value {
            font-size: 48px;
            font-weight: 700;
            color: #3b0764;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .quick-actions a {
            padding: 16px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #3b0764;
            color: white;
        }
        .quick-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 7, 100, 0.4);
        }
        .quick-actions a[target="_blank"] {
            background: white;
            color: #3b0764;
            border: 2px solid #3b0764;
        }
        .quick-actions a[target="_blank"]:hover {
            background: #3b0764;
            color: white;
        }
        .position-badge {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
        }
        .position-1 {
            background: #f7b733;
            color: white;
        }
        .position-2 {
            background: #c0c0c0;
            color: white;
        }
        .position-3 {
            background: #cd7f32;
            color: white;
        }
        .position-other {
            background: #e9ecef;
            color: #495057;
        }
        .points-highlight {
            font-weight: 700;
            font-size: 18px;
            color: #3b0764;
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Palpites - Dashboard</h1>
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon"><i data-lucide="users"></i></span>
                <div class="stat-value"><?= $total_palpiteiros ?></div>
                <div class="stat-label">Palpiteiros Ativos</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon"><i data-lucide="shield"></i></span>
                <div class="stat-value"><?= $total_times ?></div>
                <div class="stat-label">Times Cadastrados</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon"><i data-lucide="calendar"></i></span>
                <div class="stat-value"><?= $total_jogos ?></div>
                <div class="stat-label">Total de Jogos</div>
            </div>

            <div class="stat-card">
                <span class="stat-icon"><i data-lucide="circle-dot"></i></span>
                <div class="stat-value"><?= $jogos_ativos ?></div>
                <div class="stat-label">Jogos Ativos</div>
            </div>
        </div>

        <h3 class="m-pagebase__section-title">Ações Rápidas</h3>

        <div class="quick-actions">
            <a href="<?= url('/admin/palpites/palpiteiros') ?>">
                <i data-lucide="users"></i>
                <span>Gerenciar Palpiteiros</span>
            </a>
            <a href="<?= url('/admin/palpites/times') ?>">
                <i data-lucide="shield"></i>
                <span>Gerenciar Times</span>
            </a>
            <a href="<?= url('/admin/palpites/jogos') ?>">
                <i data-lucide="calendar"></i>
                <span>Gerenciar Jogos</span>
            </a>
            <a href="<?= url('/admin/palpites/palpites/ao-vivo') ?>">
                <i data-lucide="tv"></i>
                <span>Palpites Ao Vivo</span>
            </a>
            <a href="<?= url('/admin/palpites/resultados') ?>">
                <i data-lucide="trophy"></i>
                <span>Cadastrar Resultados</span>
            </a>
            <a href="<?= url('/palpites/exibicao') ?>" target="_blank">
                <i data-lucide="monitor"></i>
                <span>Tela de Exibição (OBS)</span>
            </a>
        </div>

        <?php if (!empty($ranking)): ?>
        <h3 class="m-pagebase__section-title m-pagebase__section-title--spaced">Ranking Atual - Top 10</h3>

        <table class="m-pagebase__table">
            <thead>
                <tr>
                    <th style="width: 80px; text-align: center;">Posição</th>
                    <th>Palpiteiro</th>
                    <th style="width: 120px; text-align: center;">Pontos</th>
                    <th style="width: 120px; text-align: center;">Placares Exatos</th>
                    <th style="width: 100px; text-align: center;">Acertos</th>
                    <th style="width: 100px; text-align: center;">Erros</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $posicao = 1;
                foreach ($ranking as $item):
                    if ($posicao == 1) {
                        $badgeClass = 'position-1';
                    } elseif ($posicao == 2) {
                        $badgeClass = 'position-2';
                    } elseif ($posicao == 3) {
                        $badgeClass = 'position-3';
                    } else {
                        $badgeClass = 'position-other';
                    }
                ?>
                <tr>
                    <td style="text-align: center;">
                        <span class="position-badge <?= $badgeClass ?>">
                            <?= $posicao++ ?>
                        </span>
                    </td>
                    <td style="font-weight: 600;">
                        <?= htmlspecialchars($item['palpiteiro_nome']) ?>
                    </td>
                    <td style="text-align: center;">
                        <span class="points-highlight"><?= $item['total_pontos'] ?? 0 ?></span>
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #28a745;">
                        <?= $item['placares_exatos'] ?? 0 ?>
                    </td>
                    <td style="text-align: center; color: #28a745;">
                        <?= $item['resultados_certos'] ?? 0 ?>
                    </td>
                    <td style="text-align: center; color: #dc3545;">
                        <?= $item['erros'] ?? 0 ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
