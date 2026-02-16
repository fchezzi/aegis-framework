<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php
	$loadAdminJs = true;
	require_once __DIR__ . '../../../includes/_admin-head.php';
	?>
	<title>Page Builder - <?= htmlspecialchars($slug) ?></title>
</head>
<body class="builder-page m-pagebasebody">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <div class="builder-header">
        <div>
            <h1>Page Builder</h1>
            <p class="subtitle">Editando layout da página: <strong><?= htmlspecialchars($slug) ?></strong></p>
        </div>
        <div class="actions">
            <button class="m-pagebase__btn m-pagebase__btn--widthauto" style="background: #3498db;" onclick="saveLayout()"><i data-lucide="save"></i> Salvar Layout</button>
            <a href="<?= url('/admin/pages') ?>" class="m-pagebase__btn-secondary"><i data-lucide="arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div id="alert-container" style="padding: 0 50px;"></div>

    <div class="builder-container">
        <div class="builder">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Blocos e Cards</h2>
                <button class="m-pagebase__btn m-pagebase__btn--widthauto" onclick="addBlock()"><i data-lucide="plus"></i> Adicionar Bloco</button>
            </div>

            <div id="blocks-container">
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <p>Nenhum bloco criado ainda.</p>
                    <p style="font-size: 14px; margin-top: 10px;">Clique em "Adicionar Bloco" para começar!</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>

    <script>
        // Constantes de segurança (sincronizadas com backend)
        const MAX_BLOCKS = 50;
        const MAX_CARDS = 300;
        const MAX_CONTENT_LENGTH = 1000000; // 1MB
        const MAX_PAYLOAD_SIZE = 10000000; // 10MB

        const pageSlug = '<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>';
        const csrfToken = '<?= Security::generateCSRF() ?>';
        let layout = <?= json_encode($blocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>; // Carregar layout existente

        /**
         * Escape HTML para prevenir XSS
         */
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Validar tamanho do payload antes de salvar
         */
        function validatePayloadSize() {
            const payload = JSON.stringify(layout);
            if (payload.length > MAX_PAYLOAD_SIZE) {
                showAlert('danger', `Erro: Dados muito grandes (${Math.round(payload.length / 1024 / 1024)}MB). Máximo: 10MB.`);
                return false;
            }
            return true;
        }

        /**
         * Validar limites de blocos e cards
         */
        function validateLimits() {
            if (layout.length > MAX_BLOCKS) {
                showAlert('danger', `Erro: Máximo de ${MAX_BLOCKS} blocos permitidos.`);
                return false;
            }

            let totalCards = 0;
            for (const block of layout) {
                totalCards += block.cards.length;
            }

            if (totalCards > MAX_CARDS) {
                showAlert('danger', `Erro: Máximo de ${MAX_CARDS} cards permitidos. Total atual: ${totalCards}`);
                return false;
            }

            return true;
        }

        // Renderizar layout ao carregar
        renderLayout();

        function renderLayout() {
            const container = document.getElementById('blocks-container');

            if (layout.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <p>Nenhum bloco criado ainda.</p>
                        <p style="font-size: 14px; margin-top: 10px;">Clique em "Adicionar Bloco" para começar!</p>
                    </div>
                `;
                return;
            }

            // 🚀 PERFORMANCE: Array buffer ao invés de string concatenation
            const htmlParts = [];

            layout.forEach((block, blockIndex) => {
                const totalSize = block.cards.reduce((sum, card) => sum + parseInt(card.size), 0);
                const statusClass = totalSize === 6 ? 'total-complete' : (totalSize > 6 ? 'total-over' : 'total-partial');
                const statusText = totalSize === 6 ? '✅ Completo' : (totalSize > 6 ? '❌ Ultrapassou!' : `⚠️ Disponível: ${6 - totalSize}/6`);

                htmlParts.push(`
                    <div class="block" data-block-index="${blockIndex}">
                        <div class="block-header">
                            <div class="block-title">🟦 Bloco #${blockIndex + 1}</div>
                            <div class="block-actions">
                                ${blockIndex > 0 ? `<button class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--secondary" data-action="move-block-up" data-index="${blockIndex}" title="Mover para cima">⬆️</button>` : ''}
                                ${blockIndex < layout.length - 1 ? `<button class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--secondary" data-action="move-block-down" data-index="${blockIndex}" title="Mover para baixo">⬇️</button>` : ''}
                                <button class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger" data-action="delete-block" data-index="${blockIndex}">🗑️ Deletar Bloco</button>
                            </div>
                        </div>

                        <div class="cards-container">
                            ${block.cards.map((card, cardIndex) => {
                                // DEBUG: Log do card para verificar estrutura
                                console.log(`Card [${blockIndex}][${cardIndex}]:`, {
                                    component_type: card.component_type,
                                    content: card.content,
                                    size: card.size
                                });

                                const isComponent = card.component_type && card.component_type.trim() !== '';
                                const componentBadge = isComponent ? `<span class="component-badge">🧩 ${card.component_type}</span>` : '';
                                const configButton = isComponent ?
                                    `<button class="m-pagebase__btn btn-sm" style="background: #9b59b6;" data-action="configure-component" data-block="${blockIndex}" data-card="${cardIndex}" title="Configurar Componente">⚙️ Config</button>` :
                                    `<button class="m-pagebase__btn btn-sm" style="background: #3498db;" data-action="set-component" data-block="${blockIndex}" data-card="${cardIndex}" title="Adicionar Componente">🧩 Componente</button>`;

                                return `
                                <div class="card-item size-${card.size}" data-card-index="${cardIndex}">
                                    <div class="card-header-mini">Card ${card.size}/6 ${componentBadge}</div>
                                    <div class="card-content">${escapeHtml(card.content) || (isComponent ? `Componente: ${card.component_type}` : '(vazio)')}</div>
                                    <div class="card-footer">
                                        <select data-action="change-size" data-block="${blockIndex}" data-card="${cardIndex}" style="flex: 1; padding: 4px; font-size: 11px;">
                                            ${[1,2,3,4,5,6].map(size => `<option value="${size}" ${card.size == size ? 'selected' : ''}>${size}/6</option>`).join('')}
                                        </select>
                                        ${configButton}
                                        <button class="m-pagebase__btn m-pagebase__btn--danger btn-sm" data-action="delete-card" data-block="${blockIndex}" data-card="${cardIndex}">🗑️</button>
                                    </div>
                                </div>
                            `}).join('')}
                        </div>

                        <div class="add-card-form">
                            <strong>Adicionar Card:</strong>
                            <select id="card-size-${blockIndex}">
                                <option value="1">1/6</option>
                                <option value="2">2/6</option>
                                <option value="3" selected>3/6</option>
                                <option value="4">4/6</option>
                                <option value="5">5/6</option>
                                <option value="6">6/6</option>
                            </select>
                            <button class="m-pagebase__btn m-pagebase__btn--sm" style="background: #3498db;" data-action="add-card" data-index="${blockIndex}">➕ Adicionar</button>
                        </div>

                        <div class="total-info ${statusClass}">
                            Total: ${totalSize}/6 ${statusText}
                        </div>
                    </div>
                `);
            });

            // 🚀 PERFORMANCE: Join uma única vez ao invés de N concatenações
            container.innerHTML = htmlParts.join('');

            // 🚀 PERFORMANCE: Event delegation (um listener ao invés de N listeners)
            attachEventDelegation();
        }

        /**
         * 🚀 Event Delegation - Performance
         * Um único listener no container ao invés de dezenas de inline handlers
         */
        function attachEventDelegation() {
            const container = document.getElementById('blocks-container');

            // Remover listeners antigos (se existirem)
            const oldContainer = container.cloneNode(false);
            container.parentNode.replaceChild(oldContainer, container);
            const newContainer = document.getElementById('blocks-container');
            newContainer.innerHTML = container.innerHTML;

            // Adicionar listener único (event delegation)
            newContainer.addEventListener('click', function(e) {
                const target = e.target;
                const action = target.dataset.action;

                if (action === 'delete-block') {
                    const index = parseInt(target.dataset.index);
                    deleteBlock(index);
                } else if (action === 'move-block-up') {
                    const index = parseInt(target.dataset.index);
                    moveBlockUp(index);
                } else if (action === 'move-block-down') {
                    const index = parseInt(target.dataset.index);
                    moveBlockDown(index);
                } else if (action === 'delete-card') {
                    const blockIndex = parseInt(target.dataset.block);
                    const cardIndex = parseInt(target.dataset.card);
                    deleteCard(blockIndex, cardIndex);
                } else if (action === 'add-card') {
                    const index = parseInt(target.dataset.index);
                    addCard(index);
                } else if (action === 'set-component') {
                    // NOVO: Adicionar componente a card vazio
                    const blockIndex = parseInt(target.dataset.block);
                    const cardIndex = parseInt(target.dataset.card);
                    openComponentSelector(blockIndex, cardIndex);
                } else if (action === 'configure-component') {
                    // NOVO: Configurar componente existente
                    const blockIndex = parseInt(target.dataset.block);
                    const cardIndex = parseInt(target.dataset.card);
                    const card = layout[blockIndex].cards[cardIndex];
                    currentComponentCard = { blockIndex, cardIndex };

                    // Se component_data é string JSON, parsear para objeto
                    let componentData = card.component_data;
                    if (typeof componentData === 'string' && componentData) {
                        try {
                            componentData = JSON.parse(componentData);
                        } catch (e) {
                            console.error('Erro ao parsear component_data:', e);
                            componentData = {};
                        }
                    }

                    openComponentConfig(card.component_type, componentData);
                }
            });

            // Listener para selects (change event)
            newContainer.addEventListener('change', function(e) {
                const target = e.target;
                const action = target.dataset.action;

                if (action === 'change-size') {
                    const blockIndex = parseInt(target.dataset.block);
                    const cardIndex = parseInt(target.dataset.card);
                    const newSize = target.value;
                    changeCardSize(blockIndex, cardIndex, newSize);
                }
            });
        }

        function addBlock() {
            // Validar limite de blocos
            if (layout.length >= MAX_BLOCKS) {
                showAlert('danger', `Erro: Máximo de ${MAX_BLOCKS} blocos permitidos.`);
                return;
            }

            layout.push({ cards: [] });
            renderLayout();
        }

        function deleteBlock(blockIndex) {
            if (confirm('Deletar este bloco e todos os seus cards?')) {
                layout.splice(blockIndex, 1);
                renderLayout();
            }
        }

        function moveBlockUp(blockIndex) {
            if (blockIndex > 0) {
                // Trocar com o bloco anterior
                const temp = layout[blockIndex];
                layout[blockIndex] = layout[blockIndex - 1];
                layout[blockIndex - 1] = temp;
                renderLayout();
            }
        }

        function moveBlockDown(blockIndex) {
            if (blockIndex < layout.length - 1) {
                // Trocar com o próximo bloco
                const temp = layout[blockIndex];
                layout[blockIndex] = layout[blockIndex + 1];
                layout[blockIndex + 1] = temp;
                renderLayout();
            }
        }

        function addCard(blockIndex) {
            // Validar limite total de cards
            let totalCards = 0;
            for (const block of layout) {
                totalCards += block.cards.length;
            }

            if (totalCards >= MAX_CARDS) {
                showAlert('danger', `Erro: Máximo de ${MAX_CARDS} cards permitidos no total.`);
                return;
            }

            const sizeSelect = document.getElementById(`card-size-${blockIndex}`);
            const size = parseInt(sizeSelect.value);

            // Validar total do bloco
            const currentTotal = layout[blockIndex].cards.reduce((sum, card) => sum + parseInt(card.size), 0);
            if (currentTotal + size > 6) {
                showAlert('danger', `Não é possível adicionar. Total atual: ${currentTotal}/6`);
                return;
            }

            layout[blockIndex].cards.push({ size: size, content: null });
            renderLayout();
        }

        function deleteCard(blockIndex, cardIndex) {
            layout[blockIndex].cards.splice(cardIndex, 1);
            renderLayout();
        }

        function changeCardSize(blockIndex, cardIndex, newSize) {
            layout[blockIndex].cards[cardIndex].size = parseInt(newSize);
            renderLayout();
        }

        function saveLayout() {
            // Validar limites antes de salvar
            if (!validateLimits()) {
                return;
            }

            // Validar tamanho do payload
            if (!validatePayloadSize()) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Salvando...';
            lucide.createIcons();

            fetch('<?= url('/admin/page-builder/save-layout') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    page_slug: pageSlug,
                    layout_data: JSON.stringify(layout)
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="save"></i> Salvar Layout';
                lucide.createIcons();

                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('danger', 'Erro: ' + data.error);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="save"></i> Salvar Layout';
                lucide.createIcons();
                showAlert('danger', 'Erro ao salvar: ' + err.message);
            });
        }

        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        // ================================================
        // COMPONENT SYSTEM
        // ================================================

        let currentComponentCard = null;
        let availableComponents = [];

        // Carregar componentes disponíveis
        async function loadAvailableComponents() {
            try {
                const response = await fetch('<?= url('/admin/components/metadata') ?>?type=_list_all&_t=' + Date.now());
                const data = await response.json();

                if (data.success && data.components) {
                    // Mapear componentes com ícones padrão
                    availableComponents = data.components.map(comp => ({
                        type: comp.type,
                        title: comp.title,
                        icon: getComponentIcon(comp.type),
                        min_size: comp.min_size,
                        max_size: comp.max_size
                    }));
                } else {
                    throw new Error(data.error || 'Erro ao carregar componentes');
                }
            } catch (error) {
                console.error('Erro ao carregar componentes:', error);
                // Fallback para lista hardcoded
                availableComponents = [
                    { type: 'hero', title: 'Hero Banner', icon: '🎯', min_size: 4, max_size: 6 },
                    { type: 'tabelas', title: 'Tabela de Dados', icon: '📊', min_size: 3, max_size: 6 }
                ];
            }
        }

        // Obter ícone do componente
        function getComponentIcon(type) {
            const icons = {
                'hero': '🎯',
                'tabelas': '📊',
                'filtros': '🔍',
                'widgets': '📦'
            };
            return icons[type] || '📄';
        }

        // Abrir modal de seleção de componente
        async function openComponentSelector(blockIndex, cardIndex) {
            currentComponentCard = { blockIndex, cardIndex };

            const modal = document.getElementById('component-modal');
            const grid = document.getElementById('component-grid');

            if (availableComponents.length === 0) {
                await loadAvailableComponents();
            }

            // Renderizar opções
            grid.innerHTML = availableComponents.map(comp => `
                <div class="component-option" data-type="${comp.type}">
                    <div class="component-icon">${comp.icon}</div>
                    <div class="component-name">${comp.title}</div>
                    <div style="font-size: 11px; color: #999; margin-top: 5px;">Tamanho: ${comp.min_size}-${comp.max_size}/6</div>
                </div>
            `).join('');

            // Event listeners para seleção
            grid.querySelectorAll('.component-option').forEach(option => {
                option.addEventListener('click', function() {
                    grid.querySelectorAll('.component-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });

            modal.style.display = 'block';
        }

        // Fechar modal de componente
        function closeComponentModal() {
            document.getElementById('component-modal').style.display = 'none';
            document.getElementById('component-config-modal').style.display = 'none';
            currentComponentCard = null;
        }

        // Próximo passo: configurar componente
        async function nextToConfig() {
            const selected = document.querySelector('.component-option.selected');
            if (!selected) {
                showAlert('danger', 'Selecione um componente primeiro');
                return;
            }

            const componentType = selected.dataset.type;

            // Fechar modal de seleção
            document.getElementById('component-modal').style.display = 'none';

            // Abrir modal de configuração
            await openComponentConfig(componentType);
        }

        // Abrir modal de configuração
        async function openComponentConfig(componentType, existingData = null) {
            const modal = document.getElementById('component-config-modal');
            const title = document.getElementById('config-title');
            const form = document.getElementById('config-form');

            title.textContent = `Configurar: ${componentType}`;
            form.innerHTML = '<p style="text-align: center; color: #999;">Carregando configurações...</p>';

            modal.style.display = 'block';

            try {
                // Buscar metadata do componente (com timestamp para evitar cache)
                const response = await fetch(`<?= url('/admin/components/metadata') ?>?type=${componentType}&_t=${Date.now()}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error);
                }

                const metadata = data.metadata;
                const fields = metadata.fields || {};

                // Gerar formulário dinamicamente
                let formHtml = '';
                for (const [fieldName, fieldConfig] of Object.entries(fields)) {
                    const value = existingData ? (existingData[fieldName] || fieldConfig.default || '') : (fieldConfig.default || '');
                    const required = fieldConfig.required ? 'required' : '';

                    // Suporte a show_if (campos condicionais)
                    let showIfAttr = '';
                    if (fieldConfig.show_if) {
                        const conditions = Object.entries(fieldConfig.show_if)
                            .map(([field, val]) => {
                                // Se val é array, converter para string separada por |
                                const valStr = Array.isArray(val) ? val.join('|') : val;
                                return `${field}:${valStr}`;
                            })
                            .join(',');
                        showIfAttr = ` data-show-if="${conditions}"`;
                    }

                    formHtml += `<div class="form-group"${showIfAttr}>`;
                    formHtml += `<label for="field_${fieldName}">${fieldConfig.label}${fieldConfig.required ? ' *' : ''}</label>`;

                    if (fieldConfig.type === 'textarea') {
                        formHtml += `<textarea id="field_${fieldName}" name="${fieldName}" ${required}>${escapeHtml(value)}</textarea>`;
                    } else if (fieldConfig.type === 'upload') {
                        // Campo de upload de imagem
                        formHtml += `<div style="display: flex; gap: 10px; align-items: stretch;">`;
                        formHtml += `<input type="text" id="field_${fieldName}" name="${fieldName}" value="${escapeHtml(value)}" ${required} placeholder="URL da imagem" readonly style="flex: 1;">`;
                        formHtml += `<input type="file" id="file_${fieldName}" accept="image/*" style="display: none;">`;
                        formHtml += `<button type="button" class="m-pagebase__btn btn-sm" onclick="document.getElementById('file_${fieldName}').click()" style="background: #3498db;">📁 Upload</button>`;
                        formHtml += `</div>`;
                        if (value) {
                            formHtml += `<div style="margin-top: 10px;"><img src="${escapeHtml(value)}" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"></div>`;
                        }
                    } else if (fieldConfig.type === 'select') {
                        const dependsOn = fieldConfig.depends_on || '';
                        formHtml += `<select id="field_${fieldName}" name="${fieldName}" ${required} data-field="${fieldName}" data-source="${fieldConfig.data_source || ''}" data-depends-on="${dependsOn}">`;

                        // Se tem data_source, será populado via AJAX
                        if (fieldConfig.data_source === 'tables') {
                            formHtml += `<option value="">Carregando...</option>`;
                        } else if (fieldConfig.data_source === 'columns') {
                            formHtml += `<option value="">Selecione uma tabela primeiro</option>`;
                        } else if (fieldConfig.data_source) {
                            formHtml += `<option value="">Carregando...</option>`;
                        } else {
                            // Opções estáticas
                            (fieldConfig.options || []).forEach(opt => {
                                const selected = opt === value ? 'selected' : '';
                                formHtml += `<option value="${opt}" ${selected}>${opt}</option>`;
                            });
                        }

                        formHtml += `</select>`;
                    } else {
                        const inputType = fieldConfig.type === 'number' ? 'number' : 'text';
                        formHtml += `<input type="${inputType}" id="field_${fieldName}" name="${fieldName}" value="${escapeHtml(value)}" ${required}>`;
                    }

                    if (fieldConfig.help) {
                        formHtml += `<div class="form-help">${fieldConfig.help}</div>`;
                    }

                    formHtml += `</div>`;
                }

                form.innerHTML = formHtml;
                form.dataset.componentType = componentType;

                // Popular selects com data_source
                await populateDynamicSelects(form, existingData);

                // Configurar visibilidade condicional (show_if)
                setupConditionalFields(form);

                // Configurar uploads de imagem
                setupImageUploads(form);

            } catch (error) {
                form.innerHTML = `<p style="color: #e74c3c;">Erro: ${error.message}</p>`;
            }
        }

        // Popular selects dinâmicos (ex: tabelas do banco)
        async function populateDynamicSelects(form, existingData) {
            const selects = form.querySelectorAll('select[data-source]');

            for (const select of selects) {
                const dataSource = select.dataset.source;
                const fieldName = select.dataset.field;
                const currentValue = existingData ? existingData[fieldName] : '';

                if (!dataSource || dataSource === '') continue;

                try {
                    let options = [];

                    // Buscar dados conforme data_source
                    if (dataSource === 'tables') {
                        console.log('🔍 Buscando tabelas...');
                        const response = await fetch('<?= url('/api/get-tables.php') ?>');
                        const data = await response.json();
                        console.log('✅ Tabelas recebidas:', data);
                        options = data.tables || [];
                    }

                    // Limpar e popular select
                    select.innerHTML = '<option value="">Selecione...</option>';
                    options.forEach(opt => {
                        const selected = opt.value === currentValue ? 'selected' : '';
                        select.innerHTML += `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                    });

                } catch (error) {
                    console.error(`Erro ao carregar ${dataSource}:`, error);
                    select.innerHTML = '<option value="">Erro ao carregar</option>';
                }
            }

            // Configurar listeners para campos dependentes
            setupDependentFields(form, existingData);
        }

        // Configurar campos que dependem de outros (ex: colunas dependem da tabela)
        function setupDependentFields(form, existingData) {
            const dependentSelects = Array.from(form.querySelectorAll('select[data-depends-on]'));

            dependentSelects.forEach(select => {
                const dependsOnFieldName = select.dataset.dependsOn;
                const parentField = form.querySelector(`select[data-field="${dependsOnFieldName}"]`);

                if (parentField) {
                    // Desabilitar até selecionar o campo pai
                    select.disabled = !parentField.value;

                    // Carregar colunas se tabela já está selecionada
                    if (parentField.value && select.dataset.source === 'columns') {
                        loadColumns(parentField.value, select, existingData);
                    }

                    // Listener para mudanças no campo pai
                    parentField.addEventListener('change', function() {
                        if (this.value && select.dataset.source === 'columns') {
                            loadColumns(this.value, select, null);
                            select.disabled = false;
                        } else {
                            select.innerHTML = '<option value="">Selecione uma tabela primeiro</option>';
                            select.disabled = true;
                        }
                    });
                }
            });
        }

        // Carregar colunas de uma tabela
        async function loadColumns(tableName, selectElement, existingData) {
            const fieldName = selectElement.dataset.field;
            const currentValue = existingData ? existingData[fieldName] : '';

            selectElement.innerHTML = '<option value="">Carregando...</option>';
            selectElement.disabled = true;

            try {
                const response = await fetch(`<?= url('/api/get-columns.php') ?>?table=${encodeURIComponent(tableName)}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error);
                }

                selectElement.innerHTML = '<option value="">Selecione...</option>';
                (data.columns || []).forEach(col => {
                    const selected = col.value === currentValue ? 'selected' : '';
                    selectElement.innerHTML += `<option value="${col.value}" ${selected}>${col.label}</option>`;
                });

                selectElement.disabled = false;

            } catch (error) {
                console.error('Erro ao carregar colunas:', error);
                selectElement.innerHTML = '<option value="">Erro ao carregar colunas</option>';
                selectElement.disabled = false;
            }
        }

        // Configurar campos com visibilidade condicional (show_if)
        function setupConditionalFields(form) {
            const conditionalGroups = form.querySelectorAll('.form-group[data-show-if]');

            // Função para atualizar visibilidade
            function updateVisibility() {
                conditionalGroups.forEach(group => {
                    const conditions = group.dataset.showIf.split(',');
                    let shouldShow = true;

                    conditions.forEach(condition => {
                        const [fieldName, expectedValue] = condition.split(':');
                        const field = form.querySelector(`[name="${fieldName}"]`);

                        if (field) {
                            // Se expectedValue contém | significa que aceita múltiplos valores
                            if (expectedValue.includes('|')) {
                                const validValues = expectedValue.split('|');
                                if (!validValues.includes(field.value)) {
                                    shouldShow = false;
                                }
                            } else {
                                // Comparação simples
                                if (field.value !== expectedValue) {
                                    shouldShow = false;
                                }
                            }
                        }
                    });

                    // Mostrar/ocultar
                    group.style.display = shouldShow ? 'block' : 'none';

                    // Desabilitar campos ocultos para não enviar valores
                    const inputs = group.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        input.disabled = !shouldShow;
                    });
                });
            }

            // Atualizar na carga
            updateVisibility();

            // Atualizar quando qualquer campo mudar
            const allInputs = form.querySelectorAll('input, select, textarea');
            allInputs.forEach(input => {
                input.addEventListener('change', updateVisibility);
            });
        }

        // Configurar uploads de imagem
        function setupImageUploads(form) {
            const fileInputs = form.querySelectorAll('input[type="file"]');

            fileInputs.forEach(fileInput => {
                const fieldName = fileInput.id.replace('file_', '');
                const textInput = form.querySelector(`#field_${fieldName}`);

                fileInput.addEventListener('change', async function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    // Validar tipo
                    if (!file.type.startsWith('image/')) {
                        alert('Por favor, selecione uma imagem válida');
                        return;
                    }

                    // Validar tamanho (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Imagem muito grande. Máximo: 5MB');
                        return;
                    }

                    // Mostrar loading
                    const btn = fileInput.parentElement.querySelector('button');
                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = '⏳ Enviando...';

                    try {
                        // Upload
                        const formData = new FormData();
                        formData.append('image', file);

                        const response = await fetch('<?= url('/api/upload-image.php') ?>', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Atualizar campo de texto com URL
                            textInput.value = data.url;

                            // Adicionar/atualizar preview
                            let preview = fileInput.parentElement.parentElement.querySelector('img');
                            if (!preview) {
                                const previewDiv = document.createElement('div');
                                previewDiv.style.marginTop = '10px';
                                previewDiv.innerHTML = `<img src="${data.url}" alt="Preview" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">`;
                                fileInput.parentElement.parentElement.appendChild(previewDiv);
                            } else {
                                preview.src = data.url;
                            }

                            showAlert('success', 'Imagem enviada com sucesso!');
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        alert('Erro ao fazer upload: ' + error.message);
                    } finally {
                        btn.disabled = false;
                        btn.textContent = originalText;
                        fileInput.value = ''; // Limpar input
                    }
                });
            });
        }

        // Salvar configuração do componente
        function saveComponentConfig() {
            const form = document.getElementById('config-form');
            const componentType = form.dataset.componentType;

            // Coletar dados do formulário (INCLUIR campos disabled também!)
            const formData = {};
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                // Pegar valor mesmo se disabled (campos ocultos por show_if podem ter dados importantes)
                if (input.name) {
                    formData[input.name] = input.value || '';
                }
            });

            // Atualizar card no layout
            const card = layout[currentComponentCard.blockIndex].cards[currentComponentCard.cardIndex];
            card.component_type = componentType;
            card.component_data = formData;
            card.content = null; // Limpar conteúdo HTML

            // Fechar modal e re-renderizar
            closeComponentModal();
            renderLayout();

            showAlert('success', `Componente ${componentType} configurado com sucesso!`);
        }

        // Inicializar sistema de componentes
        loadAvailableComponents();
    </script>

    <!-- Component Selector Modal -->
    <div id="component-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeComponentModal()">×</button>
            <div class="modal-header">
                <h3 class="modal-title">Selecionar Componente</h3>
                <p style="color: #666; font-size: 14px; margin-top: 5px;">Escolha um componente para adicionar ao card</p>
            </div>
            <div id="component-grid" class="component-grid"></div>
            <div class="modal-actions">
                <button class="m-pagebase__btn-secondary" onclick="closeComponentModal()">Cancelar</button>
                <button class="m-pagebase__btn" style="background: #3498db;" onclick="nextToConfig()">Próximo →</button>
            </div>
        </div>
    </div>

    <!-- Component Config Modal -->
    <div id="component-config-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeComponentModal()">×</button>
            <div class="modal-header">
                <h3 class="modal-title" id="config-title">Configurar Componente</h3>
                <p style="color: #666; font-size: 14px; margin-top: 5px;">Configure as opções do componente</p>
            </div>
            <div id="config-form"></div>
            <div class="modal-actions">
                <button class="m-pagebase__btn-secondary" onclick="closeComponentModal()">Cancelar</button>
                <button class="m-pagebase__btn" style="background: #3498db;" onclick="saveComponentConfig()">💾 Salvar</button>
            </div>
        </div>
    </div>

</body>
</html>
