<?php
/**
 * FtpSyncController
 * Sincronização seletiva de arquivos via FTP
 * Versão 1: Lista arquivos modificados + upload seletivo
 */

class FtpSyncController {

    /**
     * Página principal - lista arquivos modificados
     */
    public function index() {
        Auth::require();

        $user = Auth::user();

        // Buscar configurações FTP
        $ftpConfigured = !empty(Settings::get('ftp_host')) &&
                         !empty(Settings::get('ftp_username')) &&
                         !empty(Settings::get('ftp_password'));

        // Buscar arquivos modificados (últimos 7 dias)
        $modifiedFiles = $this->getModifiedFiles(7);

        // Buscar histórico de transferências
        $db = DB::connect();
        $history = $db->query(
            "SELECT * FROM tbl_ftp_sync_log ORDER BY created_at DESC LIMIT 20"
        );

        require __DIR__ . '/../views/ftp-sync.php';
    }

    /**
     * Upload de arquivos selecionados
     */
    public function upload() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        header('Content-Type: application/json');

        try {
            // Validar se FTP está configurado
            $ftpHost = Settings::get('ftp_host');
            $ftpPort = Settings::get('ftp_port') ?: 21;
            $ftpUsername = Settings::get('ftp_username');
            $ftpPassword = Settings::get('ftp_password');
            $ftpRemotePath = Settings::get('ftp_remote_path') ?: '/';

            if (empty($ftpHost) || empty($ftpUsername) || empty($ftpPassword)) {
                throw new Exception('Configure o FTP em Settings primeiro');
            }

            // Arquivos selecionados
            $files = $_POST['files'] ?? [];

            if (empty($files)) {
                throw new Exception('Nenhum arquivo selecionado');
            }

            // Validar arquivos (blacklist)
            $blacklist = [
                '_config.php',
                '.env',
                'vendor/',
                'node_modules/',
                '.git/',
                'deploys/',
                'storage/logs/',
                'storage/cache/'
            ];

            foreach ($files as $file) {
                foreach ($blacklist as $blocked) {
                    if (strpos($file, $blocked) !== false) {
                        throw new Exception("Arquivo bloqueado: {$file}");
                    }
                }
            }

            // Conectar FTP
            $conn = ftp_connect($ftpHost, $ftpPort, 10);

            if (!$conn) {
                throw new Exception('Falha ao conectar no FTP');
            }

            $login = @ftp_login($conn, $ftpUsername, $ftpPassword);

            if (!$login) {
                ftp_close($conn);
                throw new Exception('Falha na autenticação FTP');
            }

            // Modo passivo (melhor compatibilidade)
            ftp_pasv($conn, true);

            // Upload dos arquivos
            $uploaded = [];
            $errors = [];
            $db = DB::connect();

            foreach ($files as $file) {
                $localPath = ROOT_PATH . $file;

                if (!file_exists($localPath)) {
                    $errors[] = "Arquivo não encontrado: {$file}";
                    continue;
                }

                // Criar diretórios remotos se necessário
                $remoteFile = rtrim($ftpRemotePath, '/') . '/' . ltrim($file, '/');
                $remoteDir = dirname($remoteFile);
                $this->createRemoteDir($conn, $remoteDir);

                // Backup remoto (se existir)
                $remoteExists = @ftp_size($conn, $remoteFile) !== -1;

                if ($remoteExists) {
                    $backupFile = $remoteFile . '.backup-' . date('YmdHis');
                    @ftp_rename($conn, $remoteFile, $backupFile);
                }

                // Upload
                $upload = ftp_put($conn, $remoteFile, $localPath, FTP_BINARY);

                if ($upload) {
                    $uploaded[] = $file;

                    // Log no banco
                    $db->insert('tbl_ftp_sync_log', [
                        'id' => Core::generateUUID(),
                        'file_path' => $file,
                        'action' => 'upload',
                        'status' => 'success',
                        'user_id' => Auth::id(),
                        'file_size' => filesize($localPath),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    $errors[] = "Falha ao enviar: {$file}";
                }
            }

            ftp_close($conn);

            // Resposta
            $message = count($uploaded) . ' arquivo(s) enviado(s) com sucesso!';

            if (!empty($errors)) {
                $message .= ' Erros: ' . implode(', ', $errors);
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'uploaded' => $uploaded,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Buscar arquivos modificados nos últimos N dias
     */
    private function getModifiedFiles($days = 7) {
        $files = [];
        $cutoffTime = time() - ($days * 86400);

        // Diretórios para escanear
        $dirs = [
            'admin/',
            'core/',
            'frontend/',
            'modules/',
            'assets/',
            'public/'
        ];

        // Blacklist
        $blacklist = [
            '_config.php',
            '.env',
            'vendor/',
            'node_modules/',
            '.git/',
            'deploys/',
            'storage/logs/',
            'storage/cache/',
            '.DS_Store',
            'Thumbs.db'
        ];

        foreach ($dirs as $dir) {
            $fullPath = ROOT_PATH . $dir;

            if (!is_dir($fullPath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $filePath = $item->getPathname();
                    $relativePath = str_replace(ROOT_PATH, '', $filePath);

                    // Verificar blacklist
                    $blocked = false;
                    foreach ($blacklist as $pattern) {
                        if (strpos($relativePath, $pattern) !== false) {
                            $blocked = true;
                            break;
                        }
                    }

                    if ($blocked) {
                        continue;
                    }

                    // Verificar se foi modificado
                    if ($item->getMTime() >= $cutoffTime) {
                        $files[] = [
                            'path' => $relativePath,
                            'size' => $item->getSize(),
                            'modified' => date('d/m/Y H:i:s', $item->getMTime()),
                            'modified_timestamp' => $item->getMTime(),
                            'extension' => $item->getExtension()
                        ];
                    }
                }
            }
        }

        // Ordenar por data modificação (mais recente primeiro)
        usort($files, function($a, $b) {
            return $b['modified_timestamp'] - $a['modified_timestamp'];
        });

        return $files;
    }

    /**
     * Criar diretório remoto recursivamente
     */
    private function createRemoteDir($conn, $dir) {
        $parts = explode('/', $dir);
        $path = '';

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $path .= '/' . $part;

            // Tentar criar (ignora se já existe)
            @ftp_mkdir($conn, $path);
        }
    }

    /**
     * Limpar logs antigos (+ 30 dias)
     */
    public function cleanLogs() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $db = DB::connect();
        $db->query(
            "DELETE FROM tbl_ftp_sync_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $_SESSION['success'] = 'Logs antigos removidos!';
        header('Location: ' . url('/admin/ftp-sync'));
        exit;
    }
}
