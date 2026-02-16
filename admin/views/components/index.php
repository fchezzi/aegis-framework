<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Componentes - <?= ADMIN_NAME ?></title>
</head>

<body class="m-componentsbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-components">

        <div class="m-pagebase__header">
            <h1>Componentes do Page Builder</h1>
            <a href="<?= url('/admin/pages') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="m-components__alert m-components__alert--success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="m-components__alert m-components__alert--error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($components)): ?>
            <div class="m-components__empty">
                <div class="m-components__empty-icon">📦</div>
                <p class="m-components__empty-text">Nenhum componente encontrado.</p>
                <p style="margin-top: 10px; color: #999; font-size: 14px;">
                    Crie componentes em <code>components/</code>
                </p>
            </div>
        <?php else: ?>
            <div class="m-components__grid">
                <?php foreach ($components as $component): ?>
                    <?php
                        $icon = 'box';
                        switch ($component['type']) {
                            case 'hero':
                                $icon = 'target';
                                break;
                            case 'tabelas':
                                $icon = 'table';
                                break;
                            case 'card':
                                $icon = 'square';
                                break;
                            case 'button':
                                $icon = 'circle';
                                break;
                        }
                    ?>
                    <div class="m-components__card">
                        <div class="m-components__icon">
                            <i data-lucide="<?= $icon ?>" style="width: 48px; height: 48px; stroke-width: 1.5;"></i>
                        </div>
                        <div class="m-components__type"><?= htmlspecialchars($component['type']) ?></div>
                        <h3 class="m-components__title"><?= htmlspecialchars($component['title']) ?></h3>
                        <p class="m-components__description"><?= htmlspecialchars($component['description']) ?></p>

                        <div class="m-components__meta">
                            <div class="m-components__meta-item">
                                <i data-lucide="ruler" style="width: 14px; height: 14px;"></i>
                                <span>Tamanho: <?= $component['min_size'] ?>-<?= $component['max_size'] ?>/6</span>
                            </div>
                        </div>

                        <div class="m-components__actions">
                            <button class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #9b59b6;" onclick="previewComponent('<?= htmlspecialchars($component['type']) ?>')">
                                <i data-lucide="eye"></i> Preview
                            </button>
                            <button class="m-pagebase__btn-secondary m-pagebase__btn--widthauto" onclick="viewMetadata('<?= htmlspecialchars($component['type']) ?>')">
                                <i data-lucide="info"></i> Metadata
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Modal Preview -->
    <div id="preview-modal" class="m-components__modal">
        <div class="m-components__modal-content">
            <button class="m-components__modal-close" onclick="closeModal('preview-modal')">×</button>
            <h2>Preview do Componente</h2>
            <div id="preview-content" class="m-components__preview-content">
                <p style="text-align: center; color: #999;">Carregando preview...</p>
            </div>
        </div>
    </div>

    <!-- Modal Metadata -->
    <div id="metadata-modal" class="m-components__modal">
        <div class="m-components__modal-content m-components__modal-content--narrow">
            <button class="m-components__modal-close" onclick="closeModal('metadata-modal')">×</button>
            <h2>Metadata do Componente</h2>
            <pre id="metadata-content" class="m-components__metadata-content">Carregando...</pre>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

    <script>
        const csrfToken = '<?= Security::generateCSRF() ?>';

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        async function previewComponent(type) {
            const modal = document.getElementById('preview-modal');
            const content = document.getElementById('preview-content');

            modal.style.display = 'block';
            content.innerHTML = '<p style="text-align: center; color: #999;">Carregando preview...</p>';

            try {
                // Buscar metadata para obter defaults
                const metaResponse = await fetch(`<?= url('/admin/components/metadata') ?>?type=${type}`);
                const metaData = await metaResponse.json();

                if (!metaData.success) {
                    throw new Error(metaData.error);
                }

                // Criar dados padrão baseados no metadata
                const defaultData = {};
                if (metaData.metadata.fields) {
                    for (const [fieldName, fieldConfig] of Object.entries(metaData.metadata.fields)) {
                        defaultData[fieldName] = fieldConfig.default || '';
                    }
                }

                // Buscar preview
                const formData = new URLSearchParams();
                formData.append('csrf_token', csrfToken);
                formData.append('type', type);
                formData.append('data', JSON.stringify(defaultData));

                const response = await fetch('<?= url('/admin/components/preview') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `<p style="color: #e74c3c; text-align: center;">Erro: ${data.error}</p>`;
                }
            } catch (error) {
                content.innerHTML = `<p style="color: #e74c3c; text-align: center;">Erro ao carregar preview: ${error.message}</p>`;
            }
        }

        async function viewMetadata(type) {
            const modal = document.getElementById('metadata-modal');
            const content = document.getElementById('metadata-content');

            modal.style.display = 'block';
            content.textContent = 'Carregando...';

            try {
                const response = await fetch(`<?= url('/admin/components/metadata') ?>?type=${type}`);
                const data = await response.json();

                if (data.success) {
                    content.textContent = JSON.stringify(data.metadata, null, 2);
                } else {
                    content.textContent = `Erro: ${data.error}`;
                }
            } catch (error) {
                content.textContent = `Erro ao carregar metadata: ${error.message}`;
            }
        }

        // Fechar modal ao clicar fora
        document.getElementById('preview-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('preview-modal');
        });

        document.getElementById('metadata-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('metadata-modal');
        });
    </script>

</body>
</html>
