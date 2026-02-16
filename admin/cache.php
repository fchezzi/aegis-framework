<?php
require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

Auth::require();
$user = Auth::user();

$message = '';
$messageType = '';

// Processar ação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'clear_all') {
            $files = glob(CACHE_PATH . '*.cache');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            $message = "Cache limpo! {$count} arquivo(s) removido(s).";
            $messageType = 'success';
        }

        if ($action === 'clear_specific') {
            $key = $_POST['key'] ?? '';
            if ($key) {
                $result = Cache::delete($key);
                $message = $result ? "Cache '{$key}' removido!" : "Cache '{$key}' não encontrado.";
                $messageType = $result ? 'success' : 'error';
            }
        }

        if ($action === 'clear_expired') {
            $files = glob(CACHE_PATH . '*.cache');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    $content = unserialize(file_get_contents($file));
                    if ($content['expires'] < time()) {
                        unlink($file);
                        $count++;
                    }
                }
            }
            $message = "{$count} arquivo(s) expirado(s) removido(s).";
            $messageType = 'success';
        }

    } catch (Exception $e) {
        $message = "ERRO: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Listar arquivos de cache
$cacheFiles = [];
$totalSize = 0;

if (is_dir(CACHE_PATH)) {
    $files = glob(CACHE_PATH . '*.cache');

    foreach ($files as $file) {
        $size = filesize($file);
        $totalSize += $size;

        $content = @unserialize(file_get_contents($file));
        $expires = $content['expires'] ?? 0;
        $expired = $expires < time();

        $cacheFiles[] = [
            'name' => basename($file),
            'size' => $size,
            'created' => date('d/m/Y H:i:s', filectime($file)),
            'modified' => date('d/m/Y H:i:s', filemtime($file)),
            'expires' => date('d/m/Y H:i:s', $expires),
            'expired' => $expired,
            'ttl_remaining' => max(0, $expires - time())
        ];
    }

    // Ordenar por modificação (mais recente primeiro)
    usort($cacheFiles, function($a, $b) {
        return strcmp($b['modified'], $a['modified']);
    });
}

// Estatísticas
$stats = [
    'total_files' => count($cacheFiles),
    'total_size' => $totalSize,
    'expired_count' => count(array_filter($cacheFiles, fn($f) => $f['expired'])),
    'active_count' => count(array_filter($cacheFiles, fn($f) => !$f['expired']))
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-cachebody">

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Gerenciamento de Cache</h1>
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <p class="m-cache__intro">
            Visualize, limpe e gerencie o cache do sistema.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ESTATÍSTICAS -->
        <div class="m-cache__stats-grid">
            <div class="m-cache__stat-card">
                <div class="m-cache__stat-number"><?= $stats['total_files'] ?></div>
                <div class="m-cache__stat-label">Total de Arquivos</div>
            </div>

            <div class="m-cache__stat-card">
                <div class="m-cache__stat-number"><?= $stats['active_count'] ?></div>
                <div class="m-cache__stat-label">Ativos</div>
            </div>

            <div class="m-cache__stat-card">
                <div class="m-cache__stat-number"><?= $stats['expired_count'] ?></div>
                <div class="m-cache__stat-label">Expirados</div>
            </div>

            <div class="m-cache__stat-card">
                <div class="m-cache__stat-number"><?= round($stats['total_size'] / 1024, 2) ?> KB</div>
                <div class="m-cache__stat-label">Tamanho Total</div>
            </div>
        </div>

        <!-- AÇÕES -->
        <div class="m-cache__section">
            <h2 class="m-cache__section-title">
                <i data-lucide="zap"></i>
                Ações Rápidas
            </h2>

            <div class="m-cache__actions">
                <form method="post" class="m-cache__form-inline">
                    <button type="submit" name="action" value="clear_all" class="m-cache__btn m-cache__btn--danger"
                            onclick="return confirm('Tem certeza que deseja limpar TODO o cache?')">
                        Limpar Todo o Cache
                    </button>
                </form>

                <form method="post" class="m-cache__form-inline">
                    <button type="submit" name="action" value="clear_expired" class="m-cache__btn m-cache__btn--warning"
                            onclick="return confirm('Remover apenas caches expirados?')">
                        Limpar Expirados (<?= $stats['expired_count'] ?>)
                    </button>
                </form>
            </div>
        </div>

        <!-- LIMPAR CACHE ESPECÍFICO -->
        <div class="m-cache__section">
            <h2 class="m-cache__section-title">
                <i data-lucide="trash-2"></i>
                Limpar Cache Específico
            </h2>
            <p class="m-cache__section-desc">Digite a chave do cache que deseja remover.</p>

            <form method="post">
                <div class="m-cache__form-group">
                    <input type="text" name="key" placeholder="Ex: palpites_api_updates_123456" required class="m-cache__input">
                    <button type="submit" name="action" value="clear_specific" class="m-cache__btn m-cache__btn--primary">
                        Remover
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTA DE ARQUIVOS -->
        <?php if (!empty($cacheFiles)): ?>
        <div class="m-cache__section">
            <h2 class="m-cache__section-title">
                <i data-lucide="file-text"></i>
                Arquivos de Cache
            </h2>

            <table class="m-cache__table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tamanho</th>
                        <th>Criado</th>
                        <th>Expira em</th>
                        <th>TTL Restante</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cacheFiles as $file): ?>
                        <tr>
                            <td class="m-cache__filename">
                                <?= htmlspecialchars($file['name']) ?>
                            </td>
                            <td><?= round($file['size'] / 1024, 2) ?> KB</td>
                            <td><?= htmlspecialchars($file['created']) ?></td>
                            <td><?= htmlspecialchars($file['expires']) ?></td>
                            <td>
                                <?php if ($file['expired']): ?>
                                    <span class="m-cache__expired-text">Expirado</span>
                                <?php else: ?>
                                    <?= gmdate('H:i:s', $file['ttl_remaining']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="m-cache__badge <?= $file['expired'] ? 'm-cache__badge--danger' : 'm-cache__badge--success' ?>">
                                    <?= $file['expired'] ? 'Expirado' : 'Ativo' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="m-cache__section">
            <p class="m-cache__empty">Nenhum arquivo de cache encontrado.</p>
        </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
