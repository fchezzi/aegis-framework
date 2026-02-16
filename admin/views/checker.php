<?php
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once __DIR__ . '/../includes/_admin-head.php'; ?>
    <title>Checker SEO/Qualidade - <?= ADMIN_NAME ?></title>
    <style>
        .checker-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .checker-header {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .checker-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .checker-header__subtitle {
            color: #6b7280;
            margin: 0 0 1.5rem 0;
        }

        .checker-progress {
            background: #f3f4f6;
            height: 32px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            margin-bottom: 1rem;
        }

        .checker-progress__bar {
            background: linear-gradient(90deg, #3b82f6, #10b981);
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .checker-progress__text {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .checker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .checker-card {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #e5e7eb;
            transition: transform 0.2s;
        }

        .checker-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .checker-card--success {
            border-left-color: #10b981;
        }

        .checker-card--warning {
            border-left-color: #f59e0b;
        }

        .checker-card--error {
            border-left-color: #ef4444;
        }

        .checker-card--pending {
            border-left-color: #6b7280;
        }

        .checker-card__header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .checker-card__icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .checker-card__icon--success {
            background: #d1fae5;
            color: #065f46;
        }

        .checker-card__icon--warning {
            background: #fef3c7;
            color: #92400e;
        }

        .checker-card__icon--error {
            background: #fee2e2;
            color: #991b1b;
        }

        .checker-card__icon--pending {
            background: #f3f4f6;
            color: #6b7280;
        }

        .checker-card__title-wrapper {
            flex: 1;
        }

        .checker-card__title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
        }

        .checker-card__time {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .checker-card__message {
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .checker-card__detail {
            font-size: 0.875rem;
            color: #6b7280;
            font-family: monospace;
            background: #f9fafb;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.75rem;
        }

        .checker-card__action {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }

        .checker-card__action code {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .checker-card__link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.2s;
        }

        .checker-card__link:hover {
            background: #2563eb;
        }

        .checker-card__link--secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .checker-card__link--secondary:hover {
            background: #d1d5db;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .btn-back:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body class="page-checker">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="checker-container">

        <a href="<?= url('/admin/dashboard') ?>" class="btn-back">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
            Voltar ao Dashboard
        </a>

        <div class="checker-header">
            <h2>🎯 Checker SEO & Qualidade - TIER 1</h2>
            <p class="checker-header__subtitle">Validação automática de ferramentas essenciais (Princípio Pareto 80/20)</p>

            <div class="checker-progress">
                <div class="checker-progress__bar" style="width: <?= $progress ?>%">
                    <?= $progress ?>%
                </div>
            </div>
            <div class="checker-progress__text">
                <?= $completed ?> de <?= $total ?> itens concluídos
            </div>
        </div>

        <div class="checker-grid">

            <?php foreach ($checks as $key => $check): ?>
            <div class="checker-card checker-card--<?= $check['status'] ?>">
                <div class="checker-card__header">
                    <div class="checker-card__icon checker-card__icon--<?= $check['status'] ?>">
                        <?php
                        $icons = [
                            'success' => 'check-circle',
                            'warning' => 'alert-triangle',
                            'error' => 'x-circle',
                            'pending' => 'clock'
                        ];
                        ?>
                        <i data-lucide="<?= $icons[$check['status']] ?>" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="checker-card__title-wrapper">
                        <h3 class="checker-card__title"><?= htmlspecialchars($check['title']) ?></h3>
                        <span class="checker-card__time">⏱️ <?= htmlspecialchars($check['time']) ?></span>
                    </div>
                </div>

                <p class="checker-card__message"><?= htmlspecialchars($check['message']) ?></p>

                <?php if (!empty($check['detail'])): ?>
                    <div class="checker-card__detail"><?= htmlspecialchars($check['detail']) ?></div>
                <?php endif; ?>

                <?php if (!empty($check['action'])): ?>
                    <div class="checker-card__action">
                        <strong>Ação:</strong> <?php
                        if (strpos($check['action'], 'composer') !== false) {
                            echo '<code>' . htmlspecialchars($check['action']) . '</code>';
                        } else {
                            echo htmlspecialchars($check['action']);
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($check['link'])): ?>
                    <a href="<?= htmlspecialchars($check['link']) ?>"
                       class="checker-card__link <?= $check['status'] === 'success' ? 'checker-card__link--secondary' : '' ?>"
                       <?= strpos($check['link'], 'http') === 0 ? 'target="_blank"' : '' ?>>
                        <i data-lucide="<?= strpos($check['link'], 'http') === 0 ? 'external-link' : 'edit' ?>" style="width: 16px; height: 16px;"></i>
                        <?= htmlspecialchars($check['link_text'] ?? 'Abrir') ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>
</html>
