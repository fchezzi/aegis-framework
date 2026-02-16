<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Teste UptimeRobot API - <?= ADMIN_NAME ?></title>
    <style>
        .test-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .test-header {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .test-result {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn--primary {
            background: #3b82f6;
            color: white;
        }

        .btn--primary:hover {
            background: #2563eb;
        }

        .btn--secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn--secondary:hover {
            background: #d1d5db;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert--warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-badge--ok {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge--missing {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="page-uptime-test">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="test-container">

        <a href="<?= url('/admin/dashboard') ?>" class="btn btn--secondary" style="margin-bottom: 1rem;">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px; vertical-align: middle;"></i>
            Voltar
        </a>

        <div class="test-header">
            <h2>🧪 Teste UptimeRobot API</h2>
            <p style="color: #6b7280; margin: 0.5rem 0 1.5rem 0;">
                Teste a conexão com a API do UptimeRobot e veja a estrutura de dados retornados
            </p>

            <?php if ($has_key): ?>
                <div class="status-badge status-badge--ok">
                    ✅ API Key configurada
                </div>
                <br>
                <a href="<?= url('/admin/uptime-test?test=1') ?>" class="btn btn--primary">
                    🔄 Testar Conexão
                </a>
            <?php else: ?>
                <div class="alert alert--warning">
                    <strong>⚠️ API Key não configurada</strong><br>
                    Configure em: <a href="<?= url('/admin/settings') ?>">Configurações → UptimeRobot</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <strong>❌ Erro ao conectar:</strong><br>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($response): ?>
            <div style="background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 1rem 0;">📊 Resposta da API:</h3>

                <?php if (isset($response['monitors'])): ?>
                    <p style="color: #6b7280; margin-bottom: 1rem;">
                        <strong>Total de monitores:</strong> <?= count($response['monitors']) ?>
                    </p>
                <?php endif; ?>

                <div class="test-result"><?= json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
