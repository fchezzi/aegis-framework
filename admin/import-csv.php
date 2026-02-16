<?php
/**
 * AEGIS - Importação de CSV
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';
Autoloader::register();

// Verificar autenticação
Auth::require();

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar CSV - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-importcsvbody">

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Importar CSV</h1>
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <p class="m-import-csv__intro">
            Upload e importação de dados via arquivo CSV
        </p>

        <div id="alert-container"></div>

        <!-- STEP 1: Upload -->
        <div id="upload-section" class="m-import-csv__card">
            <h2 class="m-import-csv__card-title">1. Selecione o arquivo CSV</h2>

            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">

                <div class="m-import-csv__form-group">
                    <label for="table" class="m-import-csv__label">Tabela de Destino *</label>
                    <select id="table" name="table" required class="m-import-csv__select">
                        <option value="">Selecione a tabela...</option>
                        <option value="youtube_extra">youtube_extra (Métricas de Canais)</option>
                        <option value="tbl_youtube">tbl_youtube (Vídeos YouTube)</option>
                        <option value="tbl_website">tbl_website (Visitantes do Website)</option>
                        <option value="tbl_facebook">tbl_facebook (Métricas Facebook)</option>
                        <option value="tbl_instagram">tbl_instagram (Métricas Instagram)</option>
                        <option value="tbl_tiktok">tbl_tiktok (Métricas TikTok)</option>
                        <option value="tbl_x">tbl_x (Métricas X/Twitter)</option>
                        <option value="tbl_x_inscritos">tbl_x_inscritos (Inscritos X/Twitter)</option>
                        <option value="tbl_app">tbl_app (Métricas App)</option>
                        <option value="tbl_twitch">tbl_twitch (Métricas Twitch)</option>
                    </select>
                </div>

                <div class="m-import-csv__form-group">
                    <label for="csv_file" class="m-import-csv__label">Arquivo CSV *</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="m-import-csv__file-input">
                    <div class="m-import-csv__hint">
                        Máximo: 5MB | Formato: CSV com cabeçalhos na primeira linha
                    </div>
                </div>

                <button type="submit" class="m-import-csv__btn" id="upload-btn">
                    <i data-lucide="upload"></i>
                    Fazer Upload e Visualizar
                </button>
            </form>
        </div>

        <!-- STEP 2: Preview -->
        <div id="preview-section" class="m-import-csv__card m-import-csv__hidden">
            <h2 class="m-import-csv__card-title">2. Visualizar e Confirmar Importação</h2>

            <div class="m-import-csv__stats" id="stats-container"></div>

            <div id="preview-content"></div>

            <div class="m-import-csv__actions">
                <button class="m-import-csv__btn m-import-csv__btn--success" id="import-btn">
                    <i data-lucide="check"></i>
                    Confirmar e Importar
                </button>
                <button class="m-import-csv__btn m-import-csv__btn--secondary" id="cancel-btn">
                    <i data-lucide="x"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        let uploadedData = null;
        const csrfToken = '<?= Security::generateCSRF() ?>';

        // Upload e Preview
        document.getElementById('upload-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('upload-btn');
            const formData = new FormData(this);

            // Validar arquivo
            const file = document.getElementById('csv_file').files[0];
            if (!file) {
                showAlert('danger', 'Selecione um arquivo CSV');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showAlert('danger', 'Arquivo muito grande. Máximo: 5MB');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Processando...';
            lucide.createIcons();

            try {
                const response = await fetch('<?= url('/admin/api/process-csv.php') ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    uploadedData = data;
                    showPreview(data);
                    showAlert('success', `✅ ${data.total_rows} linhas encontradas no CSV`);
                } else {
                    showAlert('danger', 'Erro: ' + data.error);
                }
            } catch (error) {
                showAlert('danger', 'Erro ao processar arquivo: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="upload"></i> Fazer Upload e Visualizar';
                lucide.createIcons();
            }
        });

        // Importar dados
        document.getElementById('import-btn').addEventListener('click', async function() {
            if (!uploadedData) return;

            if (!confirm('Confirma a importação de ' + uploadedData.total_rows + ' linhas?')) {
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader"></i> Importando...';
            lucide.createIcons();

            try {
                const response = await fetch('<?= url('/admin/api/import-csv.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        table: uploadedData.table,
                        rows: uploadedData.rows
                    })
                });

                const data = await response.json();

                if (data.success) {
                    let message = `✅ Importação concluída! ${data.imported} linhas importadas, ${data.errors} erros.`;

                    // Mostrar erros detalhados
                    if (data.error_details && data.error_details.length > 0) {
                        console.log('Erros detalhados:', data.error_details);
                        message += '\n\nPrimeiros erros:';
                        data.error_details.slice(0, 5).forEach(err => {
                            message += `\nLinha ${err.line}: ${err.error}`;
                        });
                    }

                    showAlert(data.errors > 0 ? 'danger' : 'success', message);

                    // Resetar apenas se não houver erros
                    if (data.errors === 0) {
                        document.getElementById('upload-form').reset();
                        document.getElementById('preview-section').style.display = 'none';
                        document.getElementById('upload-section').style.display = 'block';
                        uploadedData = null;
                    }
                } else {
                    showAlert('danger', 'Erro na importação: ' + data.error);
                }
            } catch (error) {
                showAlert('danger', 'Erro: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check"></i> Confirmar e Importar';
                lucide.createIcons();
            }
        });

        // Cancelar
        document.getElementById('cancel-btn').addEventListener('click', function() {
            document.getElementById('preview-section').style.display = 'none';
            document.getElementById('upload-section').style.display = 'block';
            uploadedData = null;
        });

        // Mostrar preview
        function showPreview(data) {
            document.getElementById('upload-section').style.display = 'none';
            document.getElementById('preview-section').style.display = 'block';

            // Stats
            const statsHtml = `
                <div class="m-import-csv__stat-box">
                    <div class="m-import-csv__stat-label">Total de Linhas</div>
                    <div class="m-import-csv__stat-value">${data.total_rows}</div>
                </div>
                <div class="m-import-csv__stat-box">
                    <div class="m-import-csv__stat-label">Tabela Destino</div>
                    <div class="m-import-csv__stat-value m-import-csv__stat-value--small">${data.table}</div>
                </div>
            `;
            document.getElementById('stats-container').innerHTML = statsHtml;

            // Preview table
            let tableHtml = '<table class="m-import-csv__preview-table"><thead><tr>';

            // Cabeçalhos
            const headers = Object.keys(data.rows[0] || {});
            headers.forEach(header => {
                tableHtml += `<th>${header}</th>`;
            });
            tableHtml += '</tr></thead><tbody>';

            // Primeiras 10 linhas
            const preview = data.rows.slice(0, 10);
            preview.forEach(row => {
                tableHtml += '<tr>';
                headers.forEach(header => {
                    tableHtml += `<td>${row[header] || '-'}</td>`;
                });
                tableHtml += '</tr>';
            });

            tableHtml += '</tbody></table>';

            if (data.rows.length > 10) {
                tableHtml += `<p class="m-import-csv__preview-footer">Mostrando 10 de ${data.rows.length} linhas</p>`;
            }

            document.getElementById('preview-content').innerHTML = tableHtml;
        }

        // Alertas
        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => { container.innerHTML = ''; }, 5000);
        }

        lucide.createIcons();
    </script>
</body>
</html>
