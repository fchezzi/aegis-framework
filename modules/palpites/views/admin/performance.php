<?php
/**
 * Monitor de Performance - Módulo Palpites
 */

Auth::require();

$db = DB::connect();

// Estatísticas de cache
$cache_stats = [
    'hits' => $_SESSION['cache_hits'] ?? 0,
    'misses' => $_SESSION['cache_misses'] ?? 0,
    'size' => count($_SESSION['simple_cache'] ?? []),
    'keys' => array_keys($_SESSION['simple_cache'] ?? [])
];

$hit_rate = $cache_stats['hits'] + $cache_stats['misses'] > 0
    ? round(($cache_stats['hits'] / ($cache_stats['hits'] + $cache_stats['misses'])) * 100, 2)
    : 0;

// Contar registros
$counts = $db->query("
    SELECT
        (SELECT COUNT(*) FROM tbl_palpiteiros) as palpiteiros,
        (SELECT COUNT(*) FROM tbl_times) as times,
        (SELECT COUNT(*) FROM tbl_jogos_palpites) as jogos,
        (SELECT COUNT(*) FROM tbl_palpites) as palpites
")[0];

// Verificar índices (PostgreSQL)
$indices = [];
if (DB_TYPE === 'supabase') {
    $indices = $db->query("
        SELECT
            schemaname,
            tablename,
            indexname
        FROM pg_indexes
        WHERE tablename LIKE 'tbl_%palpite%' OR tablename LIKE 'tbl_jogos%' OR tablename LIKE 'tbl_times'
        ORDER BY tablename, indexname
    ");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Monitor</title>
    <link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
    <meta http-equiv="refresh" content="5">
    <style>
        body { background: #f5f7fa; font-family: system-ui; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 48px; font-weight: 700; color: #667eea; margin: 10px 0; }
        .stat-label { color: #718096; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .good { color: #27ae60; }
        .warning { color: #f39c12; }
        .bad { color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .cache-key { font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Monitor de Performance</h1>
        <p style="color: #718096; margin-bottom: 30px;">Atualização automática a cada 5 segundos</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Cache Hit Rate</div>
                <div class="stat-value <?= $hit_rate > 80 ? 'good' : ($hit_rate > 50 ? 'warning' : 'bad') ?>">
                    <?= $hit_rate ?>%
                </div>
                <small><?= $cache_stats['hits'] ?> hits / <?= $cache_stats['misses'] ?> misses</small>
            </div>

            <div class="stat-card">
                <div class="stat-label">Itens em Cache</div>
                <div class="stat-value"><?= $cache_stats['size'] ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Palpiteiros</div>
                <div class="stat-value"><?= $counts['palpiteiros'] ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Jogos</div>
                <div class="stat-value"><?= $counts['jogos'] ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Palpites</div>
                <div class="stat-value"><?= $counts['palpites'] ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Índices Criados</div>
                <div class="stat-value <?= count($indices) >= 10 ? 'good' : 'warning' ?>">
                    <?= count($indices) ?>
                </div>
                <small>Recomendado: 10+</small>
            </div>
        </div>

        <h2 style="margin-top: 40px; margin-bottom: 20px;">📦 Chaves de Cache Ativas</h2>
        <div style="background: white; padding: 20px; border-radius: 12px;">
            <?php if (empty($cache_stats['keys'])): ?>
                <p style="color: #999;">Nenhuma chave em cache no momento</p>
            <?php else: ?>
                <?php foreach ($cache_stats['keys'] as $key): ?>
                    <span class="cache-key"><?= htmlspecialchars($key) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($indices)): ?>
        <h2 style="margin-top: 40px; margin-bottom: 20px;">🔍 Índices do Banco de Dados</h2>
        <table>
            <thead>
                <tr>
                    <th>Tabela</th>
                    <th>Nome do Índice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($indices as $idx): ?>
                <tr>
                    <td><?= htmlspecialchars($idx['tablename']) ?></td>
                    <td><code><?= htmlspecialchars($idx['indexname']) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="<?= url('/admin/palpites') ?>" class="btn">← Voltar ao Dashboard</a>
            <button onclick="location.reload()" class="btn btn-primary" style="margin-left: 10px;">🔄 Atualizar Agora</button>
        </div>
    </div>
</body>
</html>
