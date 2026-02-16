<?php
require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';

Auth::require();
$user = Auth::user();

$message = '';
$messageType = '';

// Processar ação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'bump') {
            $type = $_POST['type'] ?? '';
            $description = $_POST['description'] ?? '';
            $changes = $_POST['changes'] ?? '';

            // Processar changes (uma por linha)
            $changesArray = array_filter(
                array_map('trim', explode("\n", $changes)),
                fn($line) => !empty($line)
            );

            $result = Version::bump($type, $description, $changesArray);

            $message = "Versão atualizada com sucesso!<br><strong>{$result['old_version']}</strong> → <strong>{$result['new_version']}</strong>";
            $messageType = 'success';
        }

    } catch (Exception $e) {
        $message = "ERRO: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Informações da versão atual
$versionInfo = Version::info();
$history = Version::getHistory();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Versionamento - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-versionbody">

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Versionamento</h1>
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- VERSÃO ATUAL -->
        <div class="m-version__card">
            <div class="m-version__number">v<?= $versionInfo['version'] ?></div>
            <div class="m-version__label">Versão Atual do Framework</div>

            <div class="m-version__stats">
                <div class="m-version__stat m-version__stat--major">
                    <div class="m-version__stat-value"><?= $versionInfo['major'] ?></div>
                    <div class="m-version__stat-label">Major</div>
                </div>
                <div class="m-version__stat m-version__stat--minor">
                    <div class="m-version__stat-value"><?= $versionInfo['minor'] ?></div>
                    <div class="m-version__stat-label">Minor</div>
                </div>
                <div class="m-version__stat m-version__stat--patch">
                    <div class="m-version__stat-value"><?= $versionInfo['patch'] ?></div>
                    <div class="m-version__stat-label">Patch</div>
                </div>
            </div>
        </div>

        <!-- BUMP VERSION -->
        <div class="m-version__section">
            <h2 class="m-version__section-title">Atualizar Versão</h2>

            <form method="post" id="bumpForm">
                <input type="hidden" name="action" value="bump">
                <input type="hidden" name="type" id="bumpType" value="">

                <div class="m-version__bump-grid">
                    <div class="m-version__bump-card" data-type="patch">
                        <div class="m-version__bump-icon">
                            <i data-lucide="wrench"></i>
                        </div>
                        <div class="m-version__bump-title">Patch</div>
                        <div class="m-version__bump-desc">Correcoes de bugs</div>
                        <div class="m-version__bump-example"><?= $versionInfo['version'] ?> → <?= $versionInfo['major'] ?>.<?= $versionInfo['minor'] ?>.<?= $versionInfo['patch'] + 1 ?></div>
                    </div>

                    <div class="m-version__bump-card" data-type="minor">
                        <div class="m-version__bump-icon">
                            <i data-lucide="sparkles"></i>
                        </div>
                        <div class="m-version__bump-title">Minor</div>
                        <div class="m-version__bump-desc">Novas funcionalidades</div>
                        <div class="m-version__bump-example"><?= $versionInfo['version'] ?> → <?= $versionInfo['major'] ?>.<?= $versionInfo['minor'] + 1 ?>.0</div>
                    </div>

                    <div class="m-version__bump-card" data-type="major">
                        <div class="m-version__bump-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="m-version__bump-title">Major</div>
                        <div class="m-version__bump-desc">Breaking changes</div>
                        <div class="m-version__bump-example"><?= $versionInfo['version'] ?> → <?= $versionInfo['major'] + 1 ?>.0.0</div>
                    </div>
                </div>

      
            </form>
        </div>

        <!-- HISTÓRICO -->
        <div class="m-version__section">
            <h2 class="m-version__section-title">Histórico de Versões</h2>

            <?php if (empty($history)): ?>
                <p style="color: #666; text-align: center;">Nenhuma versão registrada ainda.</p>
            <?php else: ?>
                <?php foreach ($history as $entry): ?>
                    <div class="m-version__history-item">
                        <div class="m-version__history-header">
                            <div>
                                <span class="m-version__history-version">v<?= htmlspecialchars($entry['version']) ?></span>
                                <span class="m-version__history-type m-version__history-type--<?= $entry['type'] ?>"><?= strtoupper($entry['type']) ?></span>
                            </div>
                            <div class="m-version__history-date"><?= date('d/m/Y', strtotime($entry['date'])) ?></div>
                        </div>

                        <?php if (!empty($entry['description'])): ?>
                            <div class="m-version__history-description">
                                <?= htmlspecialchars($entry['description']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($entry['changes'])): ?>
                            <ul class="m-version__history-changes">
                                <?php foreach ($entry['changes'] as $change): ?>
                                    <li><?= htmlspecialchars($change) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        const cards = document.querySelectorAll('.m-version__bump-card');
        const typeInput = document.getElementById('bumpType');
        const submitBtn = document.getElementById('submitBtn');

        cards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected de todos
                cards.forEach(c => c.classList.remove('selected'));

                // Adiciona selected ao clicado
                this.classList.add('selected');

                // Define o tipo
                const type = this.dataset.type;
                typeInput.value = type;

                // Habilita botão
                submitBtn.disabled = false;
            });
        });

        // Confirmação antes de submit
        document.getElementById('bumpForm').addEventListener('submit', function(e) {
            const type = typeInput.value;
            if (!confirm(`Tem certeza que deseja fazer BUMP ${type.toUpperCase()}?`)) {
                e.preventDefault();
            }
        });

        lucide.createIcons();
    </script>
</body>
</html>
