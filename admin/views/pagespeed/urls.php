<?php
/**
 * PageSpeed URLs - Gerenciamento
 * Adicionar/remover URLs para análise
 */
Auth::require();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
if ($success) unset($_SESSION['success']);
if ($error) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once __DIR__ . '/../../includes/_admin-head.php'; ?>
    <title>URLs PageSpeed - <?= ADMIN_NAME ?></title>
</head>

<body class="page-pagespeed-urls">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-pagebase__container">

        <div class="m-pagebase__header">
            <div class="m-pagebase__title-wrapper">
                <h1 class="m-pagebase__title">
                    <i data-lucide="link"></i>
                    URLs para Análise PageSpeed
                </h1>
                <p class="m-pagebase__subtitle">
                    Gerencie quais URLs serão analisadas automaticamente
                </p>
            </div>
            <div class="m-pagebase__header-actions">
                <a href="<?= url('/admin/pagespeed') ?>" class="m-pagebase__btn">
                    <i data-lucide="arrow-left"></i> Voltar
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

        <!-- Form Adicionar URL -->
        <div class="m-pagebase__card">
            <h2 class="m-pagebase__card-title">
                <i data-lucide="plus"></i> Adicionar Nova URL
            </h2>
            <form method="POST" action="<?= url('/admin/pagespeed/urls/store') ?>" class="m-pagebase__form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-pagebase__form-row">
                    <div class="m-pagebase__form-group" style="flex: 1;">
                        <label for="url">URL Completa *</label>
                        <input type="url" id="url" name="url" required
                               placeholder="https://exemplo.com.br/pagina"
                               style="width: 100%;">
                    </div>

                    <div class="m-pagebase__form-group">
                        <label>
                            <input type="checkbox" name="ativo" value="1" checked>
                            Ativo
                        </label>
                    </div>

                    <div class="m-pagebase__form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="m-pagebase__btn m-pagebase__btn--primary">
                            <i data-lucide="plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de URLs -->
        <?php if (empty($urls)): ?>
            <div class="m-pagebase__empty">
                <i data-lucide="inbox"></i>
                <p>Nenhuma URL cadastrada ainda.</p>
                <p class="m-pagebase__empty-hint">Adicione URLs acima para começar a monitorar a performance.</p>
            </div>
        <?php else: ?>
            <div class="m-pagebase__card">
                <h2 class="m-pagebase__card-title">
                    <i data-lucide="list"></i> URLs Cadastradas (<?= count($urls) ?>)
                </h2>

                <table class="m-pagebase__table">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 150px;">Cadastrada em</th>
                            <th style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($urls as $urlData): ?>
                            <tr>
                                <td class="url-cell" title="<?= htmlspecialchars($urlData['url']) ?>">
                                    <?= htmlspecialchars($urlData['url']) ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?= url('/admin/pagespeed/urls/' . $urlData['id'] . '/toggle') ?>" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                        <button type="submit" class="badge badge--<?= $urlData['ativo'] ? 'success' : 'muted' ?>" style="border: none; cursor: pointer;">
                                            <?= $urlData['ativo'] ? '<i data-lucide="check-circle"></i> Ativo' : '<i data-lucide="x-circle"></i> Inativo' ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?= date('d/m/Y', strtotime($urlData['created_at'])) ?></td>
                                <td>
                                    <form method="POST" action="<?= url('/admin/pagespeed/urls/' . $urlData['id'] . '/delete') ?>"
                                          style="display: inline;"
                                          onsubmit="return confirm('Remover esta URL?')">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                        <button type="submit" class="btn-icon btn-icon--danger" title="Remover">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

</body>

</html>
