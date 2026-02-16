<?php
/**
 * Deploy V2 - Pacote Completo (Código + Banco de Dados)
 *
 * Gera:
 * - aegis-{ambiente}-{timestamp}.tar.gz (código)
 * - database-{timestamp}.sql (banco completo)
 * - deploy-completo-{timestamp}.zip (pacote final)
 */

require_once __DIR__ . '/../_config.php';
require_once __DIR__ . '/../core/Autoloader.php';

Auth::require();

$message = '';
$messageType = '';

// Verificar se exec() está disponível
$execDisabled = in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
if ($execDisabled) {
    $message = "❌ ERRO: A função exec() está desabilitada no servidor. O deploy não pode ser gerado.";
    $messageType = 'error';
}

// Processar ação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$execDisabled) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'generate_package_v2') {
            $ambiente = $_POST['ambiente'] ?? 'producao';
            $incluirUploads = isset($_POST['incluir_uploads']) && $_POST['incluir_uploads'] === '1';
            $incluirBanco = isset($_POST['incluir_banco']) && $_POST['incluir_banco'] === '1';
            $versao = date('Ymd-His');
            $deployDir = ROOT_PATH . 'deploys/';

            // Criar pasta deploys se não existir
            if (!is_dir($deployDir)) {
                if (!mkdir($deployDir, 0755, true)) {
                    throw new Exception('Não foi possível criar pasta deploys/');
                }
            }

            // Criar pasta temporária
            $tempDir = ROOT_PATH . 'deploys/temp-v2-' . $versao . '/';
            if (!mkdir($tempDir, 0755, true)) {
                throw new Exception('Não foi possível criar pasta temporária');
            }

            // ========================================
            // ETAPA 1: GERAR PACOTE DE CÓDIGO
            // ========================================

            $codigoFile = "aegis-{$ambiente}-{$versao}.tar.gz";
            $codigoPath = $tempDir . $codigoFile;

            // Diretórios principais
            $dirsToClean = [
                ROOT_PATH . 'admin',
                ROOT_PATH . 'core',
                ROOT_PATH . 'database',
                ROOT_PATH . 'frontend',
                ROOT_PATH . 'modules',
                ROOT_PATH . 'routes',
                ROOT_PATH . 'api',
                ROOT_PATH . 'public'
            ];

            $otherDirs = ['storage', 'assets', 'vendor', 'components'];
            $tempCodeDir = $tempDir . 'codigo/';
            mkdir($tempCodeDir, 0755, true);

            // Copiar diretórios principais
            foreach ($dirsToClean as $dir) {
                if (is_dir($dir)) {
                    $dirName = basename($dir);
                    $destDir = $tempCodeDir . $dirName;
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $output = [];
                    exec("cp -R " . escapeshellarg($dir) . "/. " . escapeshellarg($destDir) . "/ 2>&1", $output, $return);
                    if ($return !== 0) {
                        throw new Exception("Erro ao copiar {$dirName}: " . implode("\n", $output));
                    }
                }
            }

            // Copiar diretórios auxiliares (vendor, assets, etc)
            foreach ($otherDirs as $dirName) {
                $sourceDir = ROOT_PATH . $dirName;
                if (is_dir($sourceDir)) {
                    $destDir = $tempCodeDir . $dirName;
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    $output = [];
                    exec("cp -R " . escapeshellarg($sourceDir) . "/. " . escapeshellarg($destDir) . "/ 2>&1", $output, $return);
                    if ($return !== 0) {
                        throw new Exception("Erro ao copiar {$dirName}: " . implode("\n", $output));
                    }

                    // Validação crítica para vendor
                    if ($dirName === 'vendor' && !file_exists($destDir . '/autoload.php')) {
                        throw new Exception('vendor/autoload.php não foi copiado! Sistema não funcionará.');
                    }
                }
            }

            // Copiar arquivos individuais
            $individualFiles = ['index.php', 'routes.php', 'setup.php', 'config.php', '.htaccess', 'composer.json', 'composer.lock'];
            foreach ($individualFiles as $fileName) {
                $sourceFile = ROOT_PATH . $fileName;
                if (file_exists($sourceFile)) {
                    copy($sourceFile, $tempCodeDir . $fileName);
                }
            }

            // Garantir estrutura storage COMPLETA (0777 para PHP conseguir escrever em produção)
            $requiredDirs = [
                $tempCodeDir . 'storage/cache',
                $tempCodeDir . 'storage/logs',
                $tempCodeDir . 'storage/uploads',
                $tempCodeDir . 'storage/sessions'
            ];
            foreach ($requiredDirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                touch($dir . '/.gitkeep');
            }

            // Criar tar.gz do código
            $excludeUploads = '';
            if (!$incluirUploads) {
                $excludeUploads = "--exclude='storage/uploads/*' ";
            }

            $cmdTar = "cd " . escapeshellarg($tempCodeDir) . " && tar -czf " . escapeshellarg($codigoPath) . " " .
                   "--exclude='_config.php' " .
                   "--exclude='.env' " .
                   "--exclude='*.backup.*' " .
                   "--exclude='.DS_Store' " .
                   "--exclude='Thumbs.db' " .
                   "--exclude='storage/cache/*' " .
                   "--exclude='storage/logs/*.log' " .
                   $excludeUploads .
                   ". 2>&1";

            exec($cmdTar, $outputTar, $returnTar);

            if ($returnTar !== 0 || !file_exists($codigoPath)) {
                throw new Exception("Erro ao gerar pacote de código: " . implode("\n", $outputTar));
            }

            // Verificar conteúdo do tar.gz
            exec("tar -tzf " . escapeshellarg($codigoPath) . " | head -50", $tarContents);
            error_log("Conteúdo do tar.gz (primeiras 50 linhas): " . implode("\n", $tarContents));

            // Verificar arquivos CRÍTICOS no tar
            exec("tar -tzf " . escapeshellarg($codigoPath) . " | grep -E '^\\./.htaccess$'", $htaccessCheck);
            if (empty($htaccessCheck)) {
                throw new Exception('.htaccess é CRÍTICO e não foi incluído no pacote! Sistema não funcionará sem ele.');
            }

            exec("tar -tzf " . escapeshellarg($codigoPath) . " | grep -E '^\\./vendor/autoload\\.php$'", $vendorCheck);
            if (empty($vendorCheck)) {
                throw new Exception('vendor/autoload.php é CRÍTICO e não foi incluído! Sistema não funcionará sem dependências do Composer.');
            }

            exec("tar -tzf " . escapeshellarg($codigoPath) . " | grep -E '^\\./composer\\.json$'", $composerCheck);
            if (empty($composerCheck)) {
                throw new Exception('composer.json não foi incluído! Necessário para instalação de dependências.');
            }

            $codigoSize = filesize($codigoPath);
            $codigoSizeMB = round($codigoSize / 1024 / 1024, 2);

            // ========================================
            // ETAPA 2: EXPORTAR BANCO DE DADOS
            // ========================================

            $bancoFile = '';
            $bancoSizeMB = 0;

            if ($incluirBanco) {
                $bancoFile = "database-{$versao}.sql";
                $bancoPath = $tempDir . $bancoFile;

                // Caminho do mysqldump
                $mysqldump = '/Applications/MAMP/Library/bin/mysqldump';

                if (!file_exists($mysqldump)) {
                    exec('which mysqldump 2>/dev/null', $mysqldumpCheck, $mysqldumpReturn);
                    if ($mysqldumpReturn === 0 && !empty($mysqldumpCheck)) {
                        $mysqldump = trim($mysqldumpCheck[0]);
                    } else {
                        throw new Exception('mysqldump não encontrado. Instale MySQL ou ajuste o caminho.');
                    }
                }

                // Gerar dump (remover DEFINER e SQL SECURITY para evitar erro de privilégios)
                $cmdDump = sprintf(
                    "%s -h %s -u %s -p%s --skip-triggers --single-transaction %s 2>/dev/null | sed -e 's/DEFINER=[^ ]*//g' -e 's/SQL SECURITY DEFINER//g' > %s",
                    escapeshellcmd($mysqldump),
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_USER),
                    DB_PASS,
                    escapeshellarg(DB_NAME),
                    escapeshellarg($bancoPath)
                );

                exec($cmdDump, $outputDump, $returnDump);

                if ($returnDump !== 0 || !file_exists($bancoPath) || filesize($bancoPath) < 100) {
                    throw new Exception("Erro ao exportar banco: " . implode("\n", $outputDump));
                }

                // SQL será usado como está (SEM substituições)
                // O servidor deve ter APP_URL configurado no _config.php

                $bancoSize = filesize($bancoPath);
                $bancoSizeMB = round($bancoSize / 1024 / 1024, 2);
            }

            // ========================================
            // ETAPA 3: CRIAR INSTRUÇÕES
            // ========================================

            $instrucoesFile = $tempDir . 'DEPLOY-INSTRUCOES.txt';
            $instrucoesContent = "=================================================\n";
            $instrucoesContent .= "DEPLOY COMPLETO - AEGIS Framework\n";
            $instrucoesContent .= "=================================================\n\n";
            $instrucoesContent .= "Ambiente: " . strtoupper($ambiente) . "\n";
            $instrucoesContent .= "Data: " . date('d/m/Y H:i:s') . "\n";
            $instrucoesContent .= "Versão: {$versao}\n\n";
            $instrucoesContent .= "=================================================\n";
            $instrucoesContent .= "CONTEÚDO DO PACOTE\n";
            $instrucoesContent .= "=================================================\n\n";
            $instrucoesContent .= "1. {$codigoFile} ({$codigoSizeMB} MB)\n";
            $instrucoesContent .= "   - Código completo do framework\n";
            $instrucoesContent .= "   - Uploads: " . ($incluirUploads ? "✅ INCLUÍDOS" : "❌ NÃO INCLUÍDOS") . "\n\n";

            if ($incluirBanco) {
                $instrucoesContent .= "2. {$bancoFile} ({$bancoSizeMB} MB)\n";
                $instrucoesContent .= "   - Dump completo do banco de dados\n\n";
            }

            $instrucoesContent .= "=================================================\n";
            $instrucoesContent .= "INSTRUÇÕES DE DEPLOY\n";
            $instrucoesContent .= "=================================================\n\n";

            if ($incluirBanco) {
                $instrucoesContent .= "⚠️  ATENÇÃO: Deploy com banco (use em servidor NOVO)\n\n";
                $instrucoesContent .= "PASSO 1: UPLOAD E EXTRAÇÃO DO CÓDIGO\n";
                $instrucoesContent .= "-------------------------------------------------\n";
                $instrucoesContent .= "1. Upload do {$codigoFile} para o servidor\n";
                $instrucoesContent .= "2. Extrair: tar -xzf {$codigoFile}\n";
                $instrucoesContent .= "3. Ajustar permissões: chmod -R 755 *\n\n";
                $instrucoesContent .= "PASSO 2: IMPORTAR BANCO (VIA ADMIN)\n";
                $instrucoesContent .= "-------------------------------------------------\n";
                $instrucoesContent .= "1. Acesse: /admin/import-sql\n";
                $instrucoesContent .= "2. Faça upload do arquivo {$bancoFile}\n";
                $instrucoesContent .= "3. Clique em 'Importar Banco de Dados'\n";
                $instrucoesContent .= "4. Aguarde conclusão\n\n";
                $instrucoesContent .= "PASSO 3: CONFIGURAR\n";
                $instrucoesContent .= "-------------------------------------------------\n";
                $instrucoesContent .= "1. Copiar config.php para _config.php\n";
                $instrucoesContent .= "2. Editar _config.php com credenciais do servidor\n";
                $instrucoesContent .= "3. Testar acesso ao site\n\n";
            } else {
                $instrucoesContent .= "PASSO 1: UPLOAD E EXTRAÇÃO\n";
                $instrucoesContent .= "-------------------------------------------------\n";
                $instrucoesContent .= "1. Upload do {$codigoFile}\n";
                $instrucoesContent .= "2. Extrair: tar -xzf {$codigoFile}\n";
                $instrucoesContent .= "3. Ajustar permissões: chmod -R 755 *\n\n";
            }

            file_put_contents($instrucoesFile, $instrucoesContent);

            // ========================================
            // ETAPA 4: CRIAR ZIP FINAL
            // ========================================

            $zipFile = "deploy-completo-{$ambiente}-{$versao}.zip";
            $zipPath = $deployDir . $zipFile;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Não foi possível criar arquivo ZIP final');
            }

            $zip->addFile($codigoPath, 'codigo/' . $codigoFile);
            if ($incluirBanco && file_exists($bancoPath)) {
                $zip->addFile($bancoPath, 'database/' . $bancoFile);
            }
            $zip->addFile($instrucoesFile, 'DEPLOY-INSTRUCOES.txt');
            $zip->close();

            // Limpar pasta temporária
            exec("rm -rf " . escapeshellarg($tempDir));

            if (!file_exists($zipPath)) {
                throw new Exception('Arquivo ZIP não foi criado');
            }

            $zipSize = filesize($zipPath);
            $zipSizeMB = round($zipSize / 1024 / 1024, 2);

            // Mensagem de sucesso
            $message = "<strong>✅ Deploy V2 gerado com sucesso!</strong><br><br>";
            $message .= "<strong>📦 Pacote:</strong> {$zipFile}<br>";
            $message .= "<strong>📊 Tamanho:</strong> {$zipSizeMB} MB<br><br>";
            $message .= "<strong>📁 Conteúdo:</strong><br>";
            $message .= "→ Código: {$codigoSizeMB} MB" . ($incluirUploads ? ' (com uploads)' : ' (sem uploads)') . "<br>";
            if ($incluirBanco) {
                $message .= "→ Banco: {$bancoSizeMB} MB<br>";
            }
            $message .= "→ Instruções incluídas<br>";

            $messageType = 'success';
        }

    } catch (Exception $e) {
        if (isset($tempDir) && is_dir($tempDir)) {
            exec("rm -rf " . escapeshellarg($tempDir));
        }

        $message = "❌ ERRO: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Listar pacotes V2
$deployDir = ROOT_PATH . 'deploys/';
$packagesV2 = [];

if (is_dir($deployDir)) {
    $files = glob($deployDir . 'deploy-completo-*.zip');
    if ($files) {
        foreach ($files as $file) {
            $packagesV2[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'date' => date('d/m/Y H:i:s', filemtime($file))
            ];
        }
        usort($packagesV2, function($a, $b) {
            return strcmp($b['name'], $a['name']);
        });
    }
}

// Incluir view
require_once __DIR__ . '/views/deploy-v2.php';
