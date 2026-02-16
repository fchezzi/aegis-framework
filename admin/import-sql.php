<?php
/**
 * Importador de SQL via Admin
 * Upload de arquivo .sql e importação automática no banco
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';

Auth::require();
$user = Auth::user();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    try {
        // Validar upload
        if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Nenhum arquivo foi enviado ou ocorreu erro no upload');
        }

        $file = $_FILES['sql_file'];
        $fileName = $file['name'];
        $tmpPath = $file['tmp_name'];

        // Validar extensão
        if (!preg_match('/\.sql$/i', $fileName)) {
            throw new Exception('Apenas arquivos .sql são permitidos');
        }

        // Validar tamanho (max 50MB)
        $maxSize = 50 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('Arquivo muito grande. Máximo: 50MB');
        }

        // Ler conteúdo
        $sqlContent = file_get_contents($tmpPath);
        if ($sqlContent === false || empty(trim($sqlContent))) {
            throw new Exception('Arquivo SQL está vazio ou não pôde ser lido');
        }

        // MÉTODO 1: Tentar mysql CLI (mais rápido)
        $usedMethod = '';
        $mysqlCli = false;
        $mysql = '/Applications/MAMP/Library/bin/mysql';

        if (!file_exists($mysql)) {
            exec('which mysql 2>/dev/null', $mysqlCheck, $mysqlReturn);
            if ($mysqlReturn === 0 && !empty($mysqlCheck)) {
                $mysql = trim($mysqlCheck[0]);
            }
        }

        if (file_exists($mysql) && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $mysqlCli = true;
        }

        if ($mysqlCli) {
            // Importar via CLI
            $cmd = sprintf(
                "%s -h %s -u %s -p%s %s < %s 2>&1",
                escapeshellcmd($mysql),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                DB_PASS,
                escapeshellarg(DB_NAME),
                escapeshellarg($tmpPath)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Erro ao importar SQL via CLI: ' . implode("\n", $output));
            }
            $usedMethod = 'MySQL CLI';
        } else {
            // MÉTODO 2: PDO (fallback)
            $usedMethod = 'PDO (multi-query)';

            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');
                $pdo->exec('SET time_zone = "+00:00"');

                // Processar statements
                $statements = array_filter(
                    array_map('trim', explode(";\n", $sqlContent)),
                    function($stmt) {
                        return !empty($stmt) &&
                               strpos($stmt, '--') !== 0 &&
                               strpos($stmt, '/*') !== 0;
                    }
                );

                $totalStatements = count($statements);
                $executed = 0;

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                            $executed++;
                        } catch (PDOException $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }
                }

                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                $usedMethod .= " ({$executed}/{$totalStatements} statements)";

            } catch (PDOException $e) {
                throw new Exception('Erro ao importar via PDO: ' . $e->getMessage());
            }
        }

        // Limpar cache
        if (file_exists(ROOT_PATH . 'storage/cache')) {
            exec('rm -rf ' . escapeshellarg(ROOT_PATH . 'storage/cache') . '/* 2>/dev/null');
        }

        $message = "<strong>Banco de dados importado com sucesso!</strong><br><br>";
        $message .= "→ Todas as tabelas foram atualizadas<br>";
        $message .= "→ Cache foi limpo automaticamente<br>";
        $message .= "→ Arquivo: " . htmlspecialchars($fileName) . "<br>";
        $message .= "→ Tamanho: " . round($file['size'] / 1024 / 1024, 2) . " MB<br>";
        $message .= "→ Método: " . htmlspecialchars($usedMethod);
        $messageType = 'success';

    } catch (Exception $e) {
        $message = "ERRO: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar SQL - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
</head>
<body class="m-importsqlbody">

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Importar Banco de Dados</h1>
            <a href="<?= url('/admin/dashboard') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                <i data-lucide="arrow-left"></i> Voltar
            </a>
        </div>

        <p class="m-import-sql__intro">
            Faça upload do arquivo SQL gerado pelo Deploy V2 e importe automaticamente.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- AVISOS -->
        <div class="m-import-sql__warning">
            <strong>
                <i data-lucide="alert-triangle"></i>
                ATENÇÃO:
            </strong>
            <ul>
                <li>Isso vai <strong>SOBRESCREVER</strong> todas as tabelas do banco atual</li>
                <li>Use apenas em <strong>servidor novo</strong> ou para substituir completamente</li>
                <li>Sempre faça <strong>backup</strong> antes de importar</li>
            </ul>
        </div>

        <!-- UPLOAD -->
        <div class="m-import-sql__section">
            <h2 class="m-import-sql__section-title">
                <i data-lucide="upload"></i>
                Upload do Arquivo SQL
            </h2>

            <form method="post" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="import">

                <div class="m-import-sql__upload-area" id="uploadArea">
                    <input type="file" name="sql_file" id="sqlFile" accept=".sql" required class="m-import-sql__upload-input">
                    <label for="sqlFile" class="m-import-sql__upload-label">
                        <i data-lucide="folder"></i>
                        Escolher Arquivo SQL
                    </label>
                    <div class="m-import-sql__file-info" id="fileInfo">
                        Nenhum arquivo selecionado
                    </div>
                </div>

                <button type="submit" class="m-import-sql__btn" id="submitBtn" disabled>
                    Importar Banco de Dados
                </button>
            </form>
        </div>

        <!-- INSTRUÇÕES -->
        <div class="m-import-sql__section">
            <h2 class="m-import-sql__section-title">
                <i data-lucide="list"></i>
                Como Usar
            </h2>

            <div class="m-import-sql__instructions">
                <ol>
                    <li>Extraia o pacote deploy-completo-*.zip do Deploy V2</li>
                    <li>Localize o arquivo <code>database/database-*.sql</code></li>
                    <li>Faça upload desse arquivo usando o formulário acima</li>
                    <li>Clique em "Importar Banco de Dados"</li>
                    <li>Aguarde a conclusão (pode levar alguns minutos)</li>
                    <li>Pronto! Todas as páginas e configurações estarão importadas</li>
                </ol>
            </div>
        </div>

        <!-- INFO TÉCNICA -->
        <div class="m-import-sql__info">
            <strong>
                <i data-lucide="info"></i>
                Informações Técnicas:
            </strong>
            <ul>
                <li>Tamanho máximo: 50 MB</li>
                <li>Formato: .sql (dump MySQL)</li>
                <li>Método: mysql CLI ou PDO (automático)</li>
                <li>Cache: Limpo automaticamente após importação</li>
            </ul>
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        const sqlFile = document.getElementById('sqlFile');
        const fileInfo = document.getElementById('fileInfo');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');

        sqlFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                fileInfo.innerHTML = `
                    <strong>${file.name}</strong><br>
                    Tamanho: ${sizeMB} MB
                `;
                submitBtn.disabled = false;
            } else {
                fileInfo.textContent = 'Nenhum arquivo selecionado';
                submitBtn.disabled = true;
            }
        });

        uploadForm.addEventListener('submit', function(e) {
            if (!confirm('⚠️ ATENÇÃO!\n\nIsso vai SOBRESCREVER o banco de dados atual.\n\nTem certeza que deseja continuar?')) {
                e.preventDefault();
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Importando... Aguarde...';
        });

        lucide.createIcons();
    </script>
</body>
</html>
