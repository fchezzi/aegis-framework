<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Robots.txt - <?= ADMIN_NAME ?></title>
    <style>
        .robots-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .robots-status {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .robots-status__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .robots-status__icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .robots-status__icon--success {
            background: #d1fae5;
            color: #065f46;
        }

        .robots-status__icon--error {
            background: #fee2e2;
            color: #991b1b;
        }

        .robots-status__title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .robots-status__info {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .robots-status__item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .robots-status__label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .robots-status__value {
            font-size: 0.875rem;
            font-family: monospace;
            color: #111827;
        }

        .robots-editor {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .robots-editor__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .robots-editor__title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .robots-editor__actions {
            display: flex;
            gap: 0.5rem;
        }

        .robots-editor__textarea {
            width: 100%;
            min-height: 400px;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.6;
            resize: vertical;
        }

        .robots-editor__textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        .robots-help {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .robots-help__title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }

        .robots-help__list {
            margin: 0;
            padding-left: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.8;
            color: #374151;
        }
    </style>
</head>
<body class="page-robots">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="robots-container">

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
        <div class="robots-status">
            <div class="robots-status__header">
                <div class="robots-status__icon robots-status__icon--<?= $exists ? 'success' : 'error' ?>">
                    <i data-lucide="<?= $exists ? 'check-circle' : 'alert-circle' ?>"></i>
                </div>
                <h2 class="robots-status__title">
                    <?= $exists ? '✅ robots.txt encontrado' : '❌ robots.txt não encontrado' ?>
                </h2>
            </div>

            <div class="robots-status__info">
                <div class="robots-status__item">
                    <span class="robots-status__label">Localização:</span>
                    <span class="robots-status__value"><?= htmlspecialchars($path) ?></span>
                </div>
                <div class="robots-status__item">
                    <span class="robots-status__label">URL Pública:</span>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="robots-status__value"><?= htmlspecialchars($url) ?></a>
                </div>
                <?php if ($exists): ?>
                <div class="robots-status__item">
                    <span class="robots-status__label">Tamanho:</span>
                    <span class="robots-status__value"><?= strlen($content) ?> bytes</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Editor -->
        <div class="robots-editor">
            <div class="robots-editor__header">
                <h3 class="robots-editor__title">Conteúdo do robots.txt</h3>
                <div class="robots-editor__actions">
                    <a href="<?= url('/admin/dashboard') ?>" class="btn btn--secondary">← Voltar</a>
                </div>
            </div>

            <form method="POST" action="<?= url('/admin/robots/save') ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <textarea name="content" class="robots-editor__textarea" placeholder="User-agent: *&#10;Disallow: /admin/&#10;&#10;Sitemap: <?= htmlspecialchars(url('/sitemap.xml')) ?>"><?= htmlspecialchars($content) ?></textarea>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn--primary">Salvar robots.txt</button>
                </div>
            </form>
        </div>

        <!-- Ajuda -->
        <div class="robots-help">
            <h4 class="robots-help__title">💡 Exemplo de robots.txt Recomendado:</h4>
            <ul class="robots-help__list">
                <li><strong>Disallow: /admin/</strong> - Bloqueia indexação do painel admin</li>
                <li><strong>Disallow: /api/</strong> - Bloqueia indexação de APIs</li>
                <li><strong>Disallow: /storage/logs/</strong> - Bloqueia logs</li>
                <li><strong>Disallow: /storage/cache/</strong> - Bloqueia cache</li>
                <li><strong>Allow: /storage/uploads/</strong> - Permite imagens públicas</li>
                <li><strong>Allow: /frontend/</strong> - Permite páginas públicas</li>
                <li><strong>Allow: /modules/</strong> - Permite módulos públicos (blog, artigos)</li>
                <li><strong>Sitemap: <?= htmlspecialchars(url('/sitemap.xml')) ?></strong> - Informa localização do sitemap</li>
            </ul>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
