<?php
/**
 * Palpites - Listagem
 * Gerencia palpites cadastrados (ANTES do jogo acontecer)
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

// Filtros
$jogo_id = $_GET['jogo_id'] ?? null;
$palpiteiro_id = $_GET['palpiteiro_id'] ?? null;

// Cache key
$cache_key = 'palpites_list_' . md5($jogo_id . '_' . $palpiteiro_id);
$palpites = SimpleCache::get($cache_key);

if ($palpites === null) {
    // Query base
    $where_conditions = [];
    $where_sql = '';

    if ($jogo_id) {
        $jogo_id_safe = Security::sanitize($jogo_id);
        $where_conditions[] = "p.jogo_id = '$jogo_id_safe'";
    }

    if ($palpiteiro_id) {
        $palpiteiro_id_safe = Security::sanitize($palpiteiro_id);
        $where_conditions[] = "p.palpiteiro_id = '$palpiteiro_id_safe'";
    }

    if (!empty($where_conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    $palpites = $db->query("
        SELECT
            p.*,
            j.campeonato,
            j.rodada,
            j.data_jogo,
            j.ativo as jogo_ativo,
            tm.nome as time_mandante_nome,
            tm.sigla as time_mandante_sigla,
            tv.nome as time_visitante_nome,
            tv.sigla as time_visitante_sigla,
            pa.nome as palpiteiro_nome
        FROM tbl_palpites p
        LEFT JOIN tbl_jogos_palpites j ON p.jogo_id = j.id
        LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
        LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
        LEFT JOIN tbl_palpiteiros pa ON p.palpiteiro_id = pa.id
        $where_sql
        ORDER BY j.data_jogo DESC, pa.nome ASC
    ");

    SimpleCache::set($cache_key, $palpites, 300); // 5 min
}

// Buscar jogos ATIVOS para filtro (só esses aceitam palpites)
$jogos_ativos = $db->query("
    SELECT id, campeonato, rodada, data_jogo,
           tm.sigla as mandante_sigla,
           tv.sigla as visitante_sigla
    FROM tbl_jogos_palpites j
    LEFT JOIN tbl_times tm ON j.time_mandante_id = tm.id
    LEFT JOIN tbl_times tv ON j.time_visitante_id = tv.id
    WHERE j.ativo = true
    ORDER BY j.data_jogo ASC
");

// Buscar palpiteiros ativos para filtro
$palpiteiros = $db->query("SELECT id, nome FROM tbl_palpiteiros WHERE ativo = true ORDER BY nome ASC");

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palpites - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Palpites</h1>
            <div>
                <a href="<?= url('/admin/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
                <a href="<?= url('/admin/palpites/palpites/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="plus"></i> Novo Palpite
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

        <!-- Filtros -->
        <form method="GET" action="<?= url('/admin/palpites/palpites') ?>" class="m-pagebase__filters">
            <div class="m-pagebase__filters-group">
                <div>
                    <select name="jogo_id" class="m-pagebase__filters-select">
                        <option value="">Todos os jogos</option>
                        <?php foreach ($jogos_ativos as $jogo): ?>
                            <option value="<?= $jogo['id'] ?>" <?= $jogo_id == $jogo['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($jogo['mandante_sigla']) ?> x <?= htmlspecialchars($jogo['visitante_sigla']) ?>
                                • <?= date('d/m/Y', strtotime($jogo['data_jogo'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="palpiteiro_id" class="m-pagebase__filters-select">
                        <option value="">Todos os palpiteiros</option>
                        <?php foreach ($palpiteiros as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $palpiteiro_id == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="m-pagebase__filters-btn">
                    <i data-lucide="search"></i> Filtrar
                </button>
                <?php if (!empty($jogo_id) || !empty($palpiteiro_id)): ?>
                    <a href="<?= url('/admin/palpites/palpites') ?>" class="m-pagebase__filters-clear">
                        <i data-lucide="x"></i> Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tabela de Palpites -->
        <?php if (empty($palpites)): ?>
            <div class="m-pagebase__empty">
                <i data-lucide="file-text"></i>
                <?php if (!empty($jogo_id) || !empty($palpiteiro_id)): ?>
                    <p>Nenhum palpite encontrado com os filtros aplicados.</p>
                    <p>
                        <a href="<?= url('/admin/palpites/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                            <i data-lucide="x"></i> Limpar filtros
                        </a>
                    </p>
                <?php else: ?>
                    <p>Nenhum palpite cadastrado ainda.</p>
                    <p>
                        <a href="<?= url('/admin/palpites/palpites/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                            <i data-lucide="plus"></i> Criar primeiro palpite
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="m-pagebase__table">
                <thead>
                    <tr>
                        <th>Jogo</th>
                        <th>Palpiteiro</th>
                        <th style="text-align: center;">Palpite</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($palpites as $palpite): ?>
                        <tr>
                            <td>
                                <div style="font-size: 14px;">
                                    <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                        <?= htmlspecialchars($palpite['time_mandante_sigla']) ?>
                                        <span style="color: #95a5a6;">vs</span>
                                        <?= htmlspecialchars($palpite['time_visitante_sigla']) ?>
                                    </div>
                                    <div class="m-pagebase__meta">
                                        <?= date('d/m/Y', strtotime($palpite['data_jogo'])) ?>
                                        • <?= htmlspecialchars($palpite['campeonato']) ?>
                                        <?php if ($palpite['rodada']): ?>
                                            • <?= htmlspecialchars($palpite['rodada']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($palpite['palpiteiro_nome']) ?></strong>
                            </td>
                            <td>
                                <div style="font-size: 20px; font-weight: 700; color: #3498db; text-align: center; background: #f8f9fa; padding: 8px; border-radius: 6px; white-space: nowrap;">
                                    <?= $palpite['gols_mandante'] ?> x <?= $palpite['gols_visitante'] ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($palpite['jogo_ativo']): ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--success">Jogo Ativo</span>
                                <?php else: ?>
                                    <span class="m-pagebase__badge m-pagebase__badge--inactive">Encerrado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="m-pagebase__actions">
                                    <?php if ($palpite['jogo_ativo']): ?>
                                        <a href="<?= url('/admin/palpites/palpites/' . $palpite['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                                            <i data-lucide="pencil"></i> Editar
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= url('/admin/palpites/palpites/' . $palpite['id'] . '/delete') ?>" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este palpite?')">
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
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
