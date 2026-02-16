<?php
/**
 * Palpites Ao Vivo
 * TELA ÚNICA: Todos os palpiteiros e todos os jogos ao mesmo tempo
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Cache de jogos ativos (2 minutos)
$jogos = SimpleCache::remember('palpites_ao_vivo_jogos', function() use ($db) {
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
        WHERE j.ativo = true
        ORDER BY j.data_jogo ASC, j.id ASC
    ");
}, 120);

// Cache de palpiteiros (5 minutos)
$palpiteiros = SimpleCache::remember('palpites_ao_vivo_palpiteiros', function() use ($db) {
    return $db->query("
        SELECT * FROM tbl_palpiteiros
        WHERE ativo = true
        ORDER BY ordem ASC, nome ASC
    ");
}, 300);

// Buscar TODOS os palpites de TODOS os palpiteiros
$palpites_map = [];
if (!empty($palpiteiros)) {
    $palpites_existentes = $db->query("
        SELECT
            p.*,
            j.time_mandante_id,
            j.time_visitante_id
        FROM tbl_palpites p
        LEFT JOIN tbl_jogos_palpites j ON p.jogo_id = j.id
        WHERE j.ativo = true
    ");

    foreach ($palpites_existentes as $p) {
        $key = $p['palpiteiro_id'] . '_' . $p['jogo_id'];
        $palpites_map[$key] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palpites Ao Vivo - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>
        .palpiteiros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .palpiteiro-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .palpiteiro-header {
            background: #3b0764;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .palpiteiro-foto {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .palpiteiro-nome {
            font-size: 20px;
            font-weight: 700;
        }

        .jogos-list {
            padding: 15px;
            max-height: 600px;
            overflow-y: auto;
        }

        .jogo-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.2s;
        }

        .jogo-item:hover {
            background: #e9ecef;
        }

        .jogo-item.has-palpite {
            background: #d4edda;
            border-left-color: #28a745;
        }

        .jogo-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .jogo-times {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            flex: 1;
        }

        .jogo-meta {
            font-size: 11px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .placar-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .placar-input {
            width: 50px;
            height: 40px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .placar-input:focus {
            outline: none;
            border-color: #3b0764;
        }

        .vs {
            font-size: 14px;
            font-weight: bold;
            color: #999;
        }

        .btn-form {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-form.btn-form-primary {
            background: #3b0764;
            color: white;
        }

        .btn-form.btn-form-primary:hover {
            background: #2d0550;
        }

        .btn-form.btn-form-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-form.btn-form-danger:hover {
            background: #c0392b;
        }

        .stats-ao-vivo {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 30px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b0764;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            font-size: 14px;
            color: #718096;
        }

        .empty-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .empty-card h3 {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .empty-card p {
            color: #999;
        }

        .jogos-list::-webkit-scrollbar {
            width: 8px;
        }

        .jogos-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .jogos-list::-webkit-scrollbar-thumb {
            background: #3b0764;
            border-radius: 4px;
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Palpites Ao Vivo</h1>
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

        <!-- STATS -->
        <div class="stats-ao-vivo">
            <div class="stat-item">
                <div class="stat-icon">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="stat-number"><?= count($palpiteiros) ?></div>
                    <div class="stat-label">Palpiteiros Ativos</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i data-lucide="trophy" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="stat-number"><?= count($jogos) ?></div>
                    <div class="stat-label">Jogos Disponíveis</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i data-lucide="file-text" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="stat-number"><?= count($palpites_map) ?></div>
                    <div class="stat-label">Palpites Cadastrados</div>
                </div>
            </div>
        </div>

        <?php if (empty($palpiteiros)): ?>
            <div class="empty-card">
                <h3>
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    Nenhum palpiteiro cadastrado
                </h3>
                <p>Cadastre palpiteiros para começar.</p>
            </div>
        <?php elseif (empty($jogos)): ?>
            <div class="empty-card">
                <h3>
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                    Nenhum jogo ativo
                </h3>
                <p>Cadastre jogos e marque como "ativo" para aceitar palpites.</p>
                <a href="<?= url('/admin/palpites/jogos/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto" style="margin-top: 20px;">
                    <i data-lucide="plus"></i> Cadastrar Jogo
                </a>
            </div>
        <?php else: ?>
            <!-- GRID DE PALPITEIROS -->
            <div class="palpiteiros-grid">
                <?php foreach ($palpiteiros as $palpiteiro): ?>
                    <div class="palpiteiro-card">
                        <!-- Header do Palpiteiro -->
                        <div class="palpiteiro-header">
                            <?php if (!empty($palpiteiro['foto_url'])): ?>
                                <img src="<?= Upload::url($palpiteiro['foto_url']) ?>"
                                     class="palpiteiro-foto"
                                     alt="<?= htmlspecialchars($palpiteiro['nome']) ?>">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; border: 3px solid white;">
                                    <i data-lucide="user" style="width: 28px; height: 28px; color: white;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="palpiteiro-nome"><?= htmlspecialchars($palpiteiro['nome']) ?></div>
                        </div>

                        <!-- Lista de Jogos -->
                        <div class="jogos-list">
                            <?php foreach ($jogos as $jogo): ?>
                                <?php
                                $key = $palpiteiro['id'] . '_' . $jogo['id'];
                                $palpite_existente = $palpites_map[$key] ?? null;
                                $has_palpite = !empty($palpite_existente);
                                ?>
                                <div class="jogo-item <?= $has_palpite ? 'has-palpite' : '' ?>">
                                    <div class="jogo-info">
                                        <div>
                                            <div class="jogo-times">
                                                <?= htmlspecialchars($jogo['time_mandante_sigla']) ?>
                                                <span style="color: #999;">x</span>
                                                <?= htmlspecialchars($jogo['time_visitante_sigla']) ?>
                                            </div>
                                            <div class="jogo-meta">
                                                <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                                                <?= date('d/m', strtotime($jogo['data_jogo'])) ?> •
                                                <?= htmlspecialchars($jogo['campeonato']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST"
                                          action="<?= url('/admin/palpites/palpites/ao-vivo/salvar') ?>"
                                          class="placar-form">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                        <input type="hidden" name="jogo_id" value="<?= $jogo['id'] ?>">
                                        <input type="hidden" name="palpiteiro_id" value="<?= $palpiteiro['id'] ?>">
                                        <?php if ($has_palpite): ?>
                                            <input type="hidden" name="palpite_id" value="<?= $palpite_existente['id'] ?>">
                                        <?php endif; ?>

                                        <input type="number"
                                               name="gols_mandante"
                                               min="0"
                                               max="99"
                                               placeholder="0"
                                               class="placar-input"
                                               value="<?= $has_palpite ? $palpite_existente['gols_mandante'] : '' ?>"
                                               required>

                                        <span class="vs">X</span>

                                        <input type="number"
                                               name="gols_visitante"
                                               min="0"
                                               max="99"
                                               placeholder="0"
                                               class="placar-input"
                                               value="<?= $has_palpite ? $palpite_existente['gols_visitante'] : '' ?>"
                                               required>

                                        <button type="submit" class="btn-form btn-form-primary">
                                            <?php if ($has_palpite): ?>
                                                <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                                            <?php else: ?>
                                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                            <?php endif; ?>
                                        </button>

                                        <?php if ($has_palpite): ?>
                                            <button type="button"
                                                    class="btn-form btn-form-danger"
                                                    onclick="if(confirm('Deletar?')) {
                                                        var form = document.createElement('form');
                                                        form.method = 'POST';
                                                        form.action = '<?= url('/admin/palpites/palpites/ao-vivo/deletar') ?>';
                                                        form.innerHTML = '<input type=\'hidden\' name=\'csrf_token\' value=\'<?= Security::generateCSRF() ?>\'><input type=\'hidden\' name=\'palpite_id\' value=\'<?= $palpite_existente['id'] ?>\'>';
                                                        document.body.appendChild(form);
                                                        form.submit();
                                                    }">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endforeach; ?>
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
