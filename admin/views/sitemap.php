<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Sitemap.xml - <?= ADMIN_NAME ?></title>
    <style>
        .sitemap-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .sitemap-status {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .sitemap-status__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .sitemap-status__icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .sitemap-status__icon--success {
            background: #d1fae5;
            color: #065f46;
        }

        .sitemap-status__icon--error {
            background: #fee2e2;
            color: #991b1b;
        }

        .sitemap-status__title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .sitemap-status__info {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .sitemap-status__item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sitemap-status__label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .sitemap-status__value {
            font-size: 0.875rem;
            font-family: monospace;
            color: #111827;
        }

        .sitemap-preview {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .sitemap-preview__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .sitemap-preview__title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .sitemap-preview__actions {
            display: flex;
            gap: 0.5rem;
        }

        .sitemap-preview__content {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .sitemap-preview__empty {
            text-align: center;
            color: #6b7280;
            padding: 2rem;
        }

        .btn {
            padding: 0.5rem 1rem;
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

        .btn--success {
            background: #10b981;
            color: white;
        }

        .btn--success:hover {
            background: #059669;
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

        .alert--success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .sitemap-help {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .sitemap-help__title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }

        .sitemap-help__list {
            margin: 0;
            padding-left: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.8;
            color: #374151;
        }
    </style>
</head>
<body class="page-sitemap">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="sitemap-container">

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert--success">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Status -->
        <div class="sitemap-status">
            <div class="sitemap-status__header">
                <div class="sitemap-status__icon sitemap-status__icon--<?= $exists ? 'success' : 'error' ?>">
                    <i data-lucide="<?= $exists ? 'check-circle' : 'alert-circle' ?>"></i>
                </div>
                <h2 class="sitemap-status__title">
                    <?= $exists ? '✅ sitemap.xml encontrado' : '❌ sitemap.xml não encontrado' ?>
                </h2>
            </div>

            <div class="sitemap-status__info">
                <div class="sitemap-status__item">
                    <span class="sitemap-status__label">Localização:</span>
                    <span class="sitemap-status__value"><?= htmlspecialchars($path) ?></span>
                </div>
                <div class="sitemap-status__item">
                    <span class="sitemap-status__label">URL Pública:</span>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="sitemap-status__value"><?= htmlspecialchars($url) ?></a>
                </div>
                <?php if ($exists): ?>
                <div class="sitemap-status__item">
                    <span class="sitemap-status__label">Tamanho:</span>
                    <span class="sitemap-status__value"><?= strlen($content) ?> bytes</span>
                </div>
                <div class="sitemap-status__item">
                    <span class="sitemap-status__label">Última Modificação:</span>
                    <span class="sitemap-status__value"><?= htmlspecialchars($last_modified) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Preview -->
        <div class="sitemap-preview">
            <div class="sitemap-preview__header">
                <h3 class="sitemap-preview__title">Conteúdo do sitemap.xml</h3>
                <div class="sitemap-preview__actions">
                    <form method="POST" action="<?= url('/admin/sitemap/generate') ?>" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                        <button type="submit" class="btn btn--success">
                            <i data-lucide="refresh-cw" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                            <?= $exists ? 'Regenerar Sitemap' : 'Gerar Sitemap' ?>
                        </button>
                    </form>
                    <a href="<?= url('/admin/dashboard') ?>" class="btn btn--secondary">← Voltar</a>
                </div>
            </div>

            <?php if ($exists && !empty($content)): ?>
                <div class="sitemap-preview__content"><?= htmlspecialchars($content) ?></div>
            <?php else: ?>
                <div class="sitemap-preview__empty">
                    <i data-lucide="file-x" style="width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem;"></i>
                    <p>Nenhum sitemap encontrado. Clique em "Gerar Sitemap" para criar automaticamente.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ajuda -->
        <div class="sitemap-help">
            <h4 class="sitemap-help__title">💡 O que é incluído no sitemap.xml:</h4>
            <ul class="sitemap-help__list">
                <li><strong>Homepage</strong> - Página inicial (prioridade 1.0)</li>
                <li><strong>Páginas</strong> - Todas as páginas publicadas do sistema (prioridade 0.8)</li>
                <li><strong>Notícias</strong> - Todas as notícias publicadas, se módulo instalado (prioridade 0.7)</li>
            </ul>
            <br>
            <h4 class="sitemap-help__title">🔄 Geração Automática:</h4>
            <ul class="sitemap-help__list">
                <li>O sitemap é gerado automaticamente com base no conteúdo do banco de dados</li>
                <li>Inclui data de última modificação para cada URL</li>
                <li>Define prioridade e frequência de atualização para cada tipo de conteúdo</li>
                <li>Após gerar, envie o sitemap para o Google Search Console</li>
            </ul>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
