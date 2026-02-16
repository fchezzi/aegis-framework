<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Módulos - <?= ADMIN_NAME ?></title>
</head>

<body class="m-modulesbody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="m-modules">

        <div class="m-pagebase__header">
            <div>
                <h1>Gerenciar Módulos</h1>
            </div>
            <a href="<?= url('/admin') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>

        <?php if ($success): ?>
            <div class="m-components__alert m-components__alert--success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="m-components__alert m-components__alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="m-components__alert m-components__alert--warning">
                <?= $_SESSION['warning'] ?>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <!-- CONFIGURAÇÕES DE MÓDULOS (PÚBLICO/PRIVADO) -->
        <?php if (!empty($modules)): ?>
        <h2 class="m-modules__section-title">
            <i data-lucide="settings"></i> Configurações de Acesso
        </h2>

        <div class="m-modules__config">
            <p class="m-modules__config-intro">
                Configure quais módulos são públicos (acessíveis sem login) ou privados (exigem autenticação e permissões de grupo).
            </p>

            <form method="POST" action="<?= url('/admin/modules/update') ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-modules__config-list">
                    <?php foreach ($modules as $module): ?>
                        <?php if (!empty($module['public_url'])): ?>
                        <label class="m-modules__config-item">
                            <input
                                type="checkbox"
                                name="public_modules[]"
                                value="<?= htmlspecialchars($module['name']) ?>"
                                <?= $module['is_public'] ? 'checked' : '' ?>
                            >
                            <div class="m-modules__config-item-content">
                                <strong><?= htmlspecialchars($module['title']) ?></strong>
                                <span><?= htmlspecialchars($module['public_url']) ?></span>
                            </div>
                            <span class="m-modules__config-item-badge <?= $module['is_public'] ? 'm-modules__config-item-badge--public' : 'm-modules__config-item-badge--private' ?>">
                                <i data-lucide="<?= $module['is_public'] ? 'globe' : 'lock' ?>"></i>
                                <?= $module['is_public'] ? 'Público' : 'Privado' ?>
                            </span>
                        </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="m-pagebase__btn m-pagebase__btn--widthauto" style="margin-top: 20px;">
                    <i data-lucide="save"></i> Salvar Configurações
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- MÓDULOS INSTALADOS -->
        <h2 class="m-modules__section-title">
            <i data-lucide="package-check"></i> Módulos Instalados
        </h2>

        <?php
        $installedModules = array_filter($available, function($module) {
            return $module['installed'] === true;
        });
        ?>

        <?php if (empty($installedModules)): ?>
            <div class="m-modules__empty">
                <div class="m-modules__empty-icon">
                    <i data-lucide="package"></i>
                </div>
                <p><strong>Nenhum módulo instalado ainda</strong></p>
                <p>Instale módulos da seção abaixo para adicionar funcionalidades ao AEGIS</p>
            </div>
        <?php else: ?>
            <?php foreach ($installedModules as $moduleName => $module): ?>
                <div class="m-modules__card m-modules__card--installed">
                    <div class="m-modules__card-header">
                        <div style="flex: 1;">
                            <h3 class="m-modules__title">
                                <?php if (isset($module['admin_menu']['icon'])): ?>
                                    <span class="m-modules__icon">
                                        <i data-lucide="<?= htmlspecialchars($module['admin_menu']['icon']) ?>"></i>
                                    </span>
                                <?php endif; ?>
                                <?= htmlspecialchars($module['title']) ?>
                            </h3>
                            <div style="margin-bottom: 15px;">
                                <span class="m-modules__version">v<?= htmlspecialchars($module['version']) ?></span>
                                <span class="m-modules__badge m-modules__badge--installed">
                                    <i data-lucide="check-circle"></i> Instalado
                                </span>
                            </div>
                            <p class="m-modules__description"><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($module['tables']) || !empty($module['routes']['admin']) || !empty($module['routes']['public'])): ?>
                    <div class="m-modules__meta">
                        <?php if (!empty($module['tables'])): ?>
                            <div class="m-modules__meta-item">
                                <i data-lucide="database"></i>
                                <strong>Tabelas:</strong> <?= count($module['tables']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($module['routes']['admin'])): ?>
                            <div class="m-modules__meta-item">
                                <i data-lucide="link"></i>
                                <strong>Rotas Admin:</strong> <?= count($module['routes']['admin']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($module['routes']['public'])): ?>
                            <div class="m-modules__meta-item">
                                <i data-lucide="globe"></i>
                                <strong>Rotas Públicas:</strong> <?= count($module['routes']['public']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="m-modules__actions">
                        <button
                            class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto"
                            onclick="confirmUninstall('<?= htmlspecialchars($moduleName) ?>', '<?= htmlspecialchars($module['title']) ?>')"
                        >
                            <i data-lucide="trash-2"></i> Desinstalar
                        </button>
                        <?php if (isset($module['routes']['admin'][0])): ?>
                            <a href="<?= url($module['routes']['admin'][0]) ?>" class="m-pagebase__btn m-pagebase__btn--sm">
                                <i data-lucide="settings"></i> Acessar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- MÓDULOS DISPONÍVEIS -->
        <h2 class="m-modules__section-title">
            <i data-lucide="package-plus"></i> Módulos Disponíveis
        </h2>

        <?php
        $availableModules = array_filter($available, function($module) {
            return $module['installed'] === false;
        });
        ?>

        <?php if (empty($availableModules)): ?>
            <div class="m-modules__empty">
                <div class="m-modules__empty-icon">
                    <i data-lucide="check-circle-2"></i>
                </div>
                <p><strong>Todos os módulos disponíveis já estão instalados!</strong></p>
                <p>Não há módulos novos para instalar no momento</p>
            </div>
        <?php else: ?>
            <?php foreach ($availableModules as $moduleName => $module): ?>
                <div class="m-modules__card m-modules__card--available">
                    <div class="m-modules__card-header">
                        <div style="flex: 1;">
                            <h3 class="m-modules__title">
                                <?php if (isset($module['admin_menu']['icon'])): ?>
                                    <span class="m-modules__icon">
                                        <i data-lucide="<?= htmlspecialchars($module['admin_menu']['icon']) ?>"></i>
                                    </span>
                                <?php endif; ?>
                                <?= htmlspecialchars($module['title']) ?>
                            </h3>
                            <div style="margin-bottom: 15px;">
                                <span class="m-modules__version">v<?= htmlspecialchars($module['version']) ?></span>
                                <span class="m-modules__badge m-modules__badge--available">
                                    <i data-lucide="circle"></i> Disponível
                                </span>
                            </div>
                            <p class="m-modules__description"><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    </div>

                    <?php if (isset($module['requires'])): ?>
                        <div class="m-modules__requirements">
                            <strong><i data-lucide="clipboard-list"></i> Requisitos:</strong>
                            <ul class="m-modules__requirements-list">
                                <?php if (isset($module['requires']['database'])): ?>
                                    <li>Banco de dados: <?= implode(' ou ', $module['requires']['database']) ?></li>
                                <?php endif; ?>
                                <?php if (isset($module['requires']['members']) && $module['requires']['members']): ?>
                                    <li>Sistema de membros habilitado</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($module['tables'])): ?>
                    <div class="m-modules__meta">
                        <div class="m-modules__meta-item">
                            <i data-lucide="database"></i>
                            <strong>Criará:</strong> <?= count($module['tables']) ?> tabela(s)
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="m-modules__actions">
                        <form method="POST" action="<?= url('/admin/modules/install') ?>" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                            <input type="hidden" name="module_name" value="<?= htmlspecialchars($moduleName) ?>">
                            <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto">
                                <i data-lucide="download"></i> Instalar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="m-modules__back-container">
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn-secondary">
                <i data-lucide="arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function confirmUninstall(moduleName, moduleTitle) {
            if (!confirm(`⚠️ ATENÇÃO!\n\nDesinstalar o módulo "${moduleTitle}"?\n\nIsso vai:\n- Remover TODAS as tabelas do módulo\n- Deletar TODOS os dados cadastrados\n- Remover as rotas\n\nEsta ação é IRREVERSÍVEL!\n\nDigite "CONFIRMAR" para prosseguir:`)) {
                return;
            }

            const confirmation = prompt('Digite "CONFIRMAR" (tudo em maiúsculas) para desinstalar:');

            if (confirmation !== 'CONFIRMAR') {
                alert('❌ Desinstalação cancelada.');
                return;
            }

            // Criar form e submeter
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('/admin/modules/uninstall') ?>';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= Security::generateCSRF() ?>';
            form.appendChild(csrfInput);

            const moduleInput = document.createElement('input');
            moduleInput.type = 'hidden';
            moduleInput.name = 'module_name';
            moduleInput.value = moduleName;
            form.appendChild(moduleInput);

            const confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirmed';
            confirmInput.value = '1';
            form.appendChild(confirmInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>

</body>
</html>
