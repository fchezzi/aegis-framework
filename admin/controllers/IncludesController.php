<?php
/**
 * Includes Controller
 * Gerenciamento de arquivos includes do frontend
 */

class IncludesController {

    private $includesDir = null;
    private $protectedIncludes = ['_header.php', '_footer.php'];

    public function __construct() {
        $this->includesDir = ROOT_PATH . 'frontend/includes/';
    }

    /**
     * Listar todos os includes
     */
    public function index() {
        Auth::require();
        $user = Auth::user();

        // Parâmetros de ordenação
        $sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'name';
        $sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';

        // Validar coluna de ordenação
        $validColumns = ['name', 'description', 'size', 'modified'];
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = 'name';
        }

        $allIncludes = [];

        if (is_dir($this->includesDir)) {
            $files = scandir($this->includesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
                if (strpos($file, '_') !== 0) continue; // Só arquivos com underscore

                $filePath = $this->includesDir . $file;
                $size = filesize($filePath);
                $modified = filemtime($filePath);
                $description = $this->getFileDescription($filePath);
                $isProtected = $this->isCriticalInclude($filePath);
                $hasBackup = file_exists($filePath . '.backup');

                $allIncludes[] = [
                    'name' => $file,
                    'description' => $description,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size, // Para ordenação
                    'modified' => date('d/m/Y H:i', $modified),
                    'modified_timestamp' => $modified, // Para ordenação
                    'is_protected' => $isProtected,
                    'has_backup' => $hasBackup
                ];
            }
        }

        // Ordenar
        usort($allIncludes, function($a, $b) use ($sortColumn, $sortOrder) {
            // Protegidos sempre primeiro (independente da ordenação)
            if ($a['is_protected'] && !$b['is_protected']) return -1;
            if (!$a['is_protected'] && $b['is_protected']) return 1;

            // Determinar valor de comparação baseado na coluna
            $valueA = $a[$sortColumn];
            $valueB = $b[$sortColumn];

            // Para tamanho, usar bytes
            if ($sortColumn === 'size') {
                $valueA = $a['size_bytes'];
                $valueB = $b['size_bytes'];
            }

            // Para data, usar timestamp
            if ($sortColumn === 'modified') {
                $valueA = $a['modified_timestamp'];
                $valueB = $b['modified_timestamp'];
            }

            // Comparação
            if ($valueA === $valueB) return 0;

            if ($sortOrder === 'asc') {
                return $valueA < $valueB ? -1 : 1;
            } else {
                return $valueA > $valueB ? -1 : 1;
            }
        });

        // Paginação
        $perPage = 15;
        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $totalIncludes = count($allIncludes);
        $totalPages = ceil($totalIncludes / $perPage);
        $offset = ($currentPage - 1) * $perPage;

        // Fatiar array para página atual
        $includes = array_slice($allIncludes, $offset, $perPage);

        // Dados de paginação
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPages,
            'per_page' => $perPage,
            'total_items' => $totalIncludes
        ];

        // Dados de ordenação para a view
        $currentSort = $sortColumn;
        $currentOrder = $sortOrder;

        require_once ROOT_PATH . 'admin/views/includes/index.php';
    }

    /**
     * Formulário criar novo include
     */
    public function create() {
        Auth::require();
        $user = Auth::user();
        require_once ROOT_PATH . 'admin/views/includes/create.php';
    }

    /**
     * Salvar novo include
     */
    public function store() {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $name = Security::sanitize($_POST['name'] ?? '');
        $code = $_POST['code'] ?? ''; // Não sanitizar código
        $isCritical = isset($_POST['is_critical']) && $_POST['is_critical'] == '1';

        if (empty($name)) {
            $_SESSION['error'] = 'Nome do include é obrigatório';
            Core::redirect(url('/admin/includes/create'));
            return;
        }

        // Validar nome (só letras, números, hífen)
        if (!preg_match('/^[a-z0-9-]+$/i', $name)) {
            $_SESSION['error'] = 'Nome inválido. Use apenas letras, números e hífen';
            Core::redirect(url('/admin/includes/create'));
            return;
        }

        // Adicionar underscore no início e .php no final
        $filename = '_' . $name . '.php';
        $filePath = $this->includesDir . $filename;

        // Verificar se já existe
        if (file_exists($filePath)) {
            $_SESSION['error'] = 'Já existe um include com esse nome';
            Core::redirect(url('/admin/includes/create'));
            return;
        }

        // ✅ VALIDAR SINTAXE antes de criar arquivo
        $testContent = "<?php\n?>\n" . $code;
        $tempFile = tempnam(sys_get_temp_dir(), 'validate_');
        file_put_contents($tempFile, $testContent);

        // Detectar caminho do PHP (compatível com MAMP, Homebrew, etc)
        $phpPath = $this->getPhpPath();
        exec(escapeshellarg($phpPath) . ' -l ' . escapeshellarg($tempFile) . ' 2>&1', $output, $returnCode);
        unlink($tempFile);

        if ($returnCode !== 0) {
            $_SESSION['error'] = 'Erro de sintaxe no código: ' . implode('<br>', array_slice($output, 0, 3));
            Core::redirect(url('/admin/includes/create'));
            return;
        }

        // Criar arquivo com metadados
        $criticalTag = $isCritical ? "\n * @critical: true" : '';
        $content = "<?php\n/**\n * Include: {$name}{$criticalTag}\n */\n?>\n" . $code;
        file_put_contents($filePath, $content);

        $_SESSION['success'] = 'Include criado com sucesso!';
        Core::redirect(url('/admin/includes'));
    }

    /**
     * Formulário editar include
     */
    public function edit($name) {
        Auth::require();
        $user = Auth::user();

        $filePath = $this->includesDir . $name;

        if (!file_exists($filePath)) {
            $_SESSION['error'] = 'Include não encontrado';
            Core::redirect(url('/admin/includes'));
            return;
        }

        $code = file_get_contents($filePath);
        $isProtected = $this->isCriticalInclude($filePath);
        $hasBackup = file_exists($filePath . '.backup');

        require_once ROOT_PATH . 'admin/views/includes/edit.php';
    }

    /**
     * Atualizar include
     */
    public function update($name) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $filePath = $this->includesDir . $name;

        if (!file_exists($filePath)) {
            $_SESSION['error'] = 'Include não encontrado';
            Core::redirect(url('/admin/includes'));
            return;
        }

        $code = $_POST['code'] ?? '';
        $isCritical = isset($_POST['is_critical']) && $_POST['is_critical'] == '1';

        if (empty($code)) {
            $_SESSION['error'] = 'Código não pode estar vazio';
            Core::redirect(url('/admin/includes/' . $name . '/edit'));
            return;
        }

        // Fazer backup automático (sobrescrever)
        copy($filePath, $filePath . '.backup');

        // Processar flag @critical
        $updatedCode = $this->processIsCriticalFlag($code, $name, $isCritical);

        // ✅ VALIDAR SINTAXE do código final antes de salvar (apenas se tiver PHP)
        if (strpos($updatedCode, '<?php') !== false) {
            $tempFile = tempnam(sys_get_temp_dir(), 'validate_');
            file_put_contents($tempFile, $updatedCode);

            $phpPath = $this->getPhpPath();
            exec(escapeshellarg($phpPath) . ' -l ' . escapeshellarg($tempFile) . ' 2>&1', $output, $returnCode);
            unlink($tempFile);

            if ($returnCode !== 0) {
                $_SESSION['error'] = 'Erro de sintaxe no código: ' . implode('<br>', array_slice($output, 0, 3));
                Core::redirect(url('/admin/includes/' . $name . '/edit'));
                return;
            }
        }

        // Salvar alterações
        file_put_contents($filePath, $updatedCode);

        $_SESSION['success'] = 'Include atualizado com sucesso! Backup criado.';
        Core::redirect(url('/admin/includes'));
    }

    /**
     * Restaurar backup
     */
    public function restore($name) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $filePath = $this->includesDir . $name;
        $backupPath = $filePath . '.backup';

        if (!file_exists($backupPath)) {
            $_SESSION['error'] = 'Backup não encontrado';
            Core::redirect(url('/admin/includes/' . $name . '/edit'));
            return;
        }

        // Restaurar backup
        copy($backupPath, $filePath);

        $_SESSION['success'] = 'Backup restaurado com sucesso!';
        Core::redirect(url('/admin/includes/' . $name . '/edit'));
    }

    /**
     * Deletar include
     */
    public function destroy($name) {
        Auth::require();
        Security::validateCSRF($_POST['csrf_token']);

        $filePath = $this->includesDir . $name;

        // PROTEÇÃO: Não permitir deletar includes críticos
        if (file_exists($filePath) && $this->isCriticalInclude($filePath)) {
            $_SESSION['error'] = 'Este include é crítico e não pode ser deletado!';
            Core::redirect(url('/admin/includes'));
            return;
        }

        if (!file_exists($filePath)) {
            $_SESSION['error'] = 'Include não encontrado';
            Core::redirect(url('/admin/includes'));
            return;
        }

        // Verificar se está sendo usado
        $usage = $this->isIncludeInUse($name);
        if (!empty($usage)) {
            $_SESSION['error'] = 'Não é possível deletar! Include está sendo usado em: ' . implode(', ', $usage);
            Core::redirect(url('/admin/includes'));
            return;
        }

        // Deletar arquivo
        unlink($filePath);

        // Deletar backup se existir
        if (file_exists($filePath . '.backup')) {
            unlink($filePath . '.backup');
        }

        $_SESSION['success'] = 'Include deletado com sucesso!';
        Core::redirect(url('/admin/includes'));
    }

    /**
     * Verificar se include está sendo usado
     *
     * @param string $filename Nome do arquivo
     * @return array Lista de arquivos que usam o include
     */
    private function isIncludeInUse($filename) {
        $usage = [];

        // Buscar em templates
        $templates = glob(ROOT_PATH . 'frontend/templates/*.php');
        foreach ($templates as $template) {
            $content = file_get_contents($template);
            if (strpos($content, $filename) !== false) {
                $usage[] = 'Template: ' . basename($template);
            }
        }

        // Buscar em páginas
        $pages = glob(ROOT_PATH . 'frontend/pages/*.php');
        foreach ($pages as $page) {
            $content = file_get_contents($page);
            if (strpos($content, $filename) !== false) {
                $usage[] = 'Página: ' . basename($page);
            }
        }

        return $usage;
    }

    /**
     * Obter descrição do arquivo do comentário
     */
    private function getFileDescription($filePath) {
        $content = file_get_contents($filePath);

        // Tentar pegar do comentário "Include: xxx"
        if (preg_match('/\*\s*Include:\s*(.+?)\n/', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'Sem descrição';
    }

    /**
     * Processar flag @critical no código do include
     */
    private function processIsCriticalFlag($code, $filename, $isCritical) {
        // Verificar se tem comentário Include no formato padrão
        if (preg_match('/^<\?php\s*\/\*\*(.*?)\*\//s', $code, $matches)) {
            // Tem comentário, atualizar
            $fullComment = $matches[0];
            $commentBody = $matches[1];
            $restOfCode = substr($code, strlen($fullComment));

            // Remover qualquer @critical existente
            $commentBody = preg_replace('/\s*\*\s*@critical:\s*true\s*/', '', $commentBody);

            // Construir novo comentário
            if ($isCritical) {
                $newComment = "<?php\n/**" . rtrim($commentBody) . "\n * @critical: true\n */";
            } else {
                $newComment = "<?php\n/**" . rtrim($commentBody) . "\n */";
            }

            // Verificar se precisa fechar tag PHP antes de HTML
            $startsWithHtml = preg_match('/^\s*<[^?]/', $restOfCode);
            if ($startsWithHtml) {
                $newComment .= "\n?>";
            }

            return $newComment . $restOfCode;
        }

        // Não tem comentário padrão - adicionar se for crítico
        if ($isCritical) {
            $includeBaseName = str_replace(array('_', '.php'), '', $filename);
            $codeWithoutPhp = preg_replace('/^<\?php\s*\n?/', '', $code);

            $newComment = "<?php\n/**\n * Include: " . $includeBaseName . "\n * @critical: true\n */";

            // Verificar se precisa fechar tag PHP
            if (preg_match('/^\s*<[^?]/', $codeWithoutPhp)) {
                $newComment .= "\n?>";
            }

            return $newComment . "\n" . $codeWithoutPhp;
        }

        // Não é crítico e não tem comentário - retornar original
        return $code;
    }

    /**
     * Verificar se include é crítico pelo comentário @critical
     */
    private function isCriticalInclude($filePath) {
        $content = file_get_contents($filePath);

        // Verificar se tem @critical: true no comentário (flexível com espaços)
        if (preg_match('/@critical\s*:\s*true/i', $content)) {
            return true;
        }

        // Fallback: verificar array hardcoded (compatibilidade)
        $filename = basename($filePath);
        return in_array($filename, $this->protectedIncludes);
    }

    /**
     * Formatar bytes para leitura humana
     */
    private function formatBytes($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Detectar caminho do PHP executável
     * Compatível com MAMP, Homebrew, e produção
     */
    private function getPhpPath() {
        // 1. Tentar PHP_BINARY se estiver definido e não vazio
        if (defined('PHP_BINARY') && !empty(PHP_BINARY)) {
            return PHP_BINARY;
        }

        // 2. Detectar MAMP automaticamente (macOS)
        $mampBasePath = '/Applications/MAMP/bin/php';
        if (is_dir($mampBasePath)) {
            // Pegar versão atual do PHP
            $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
            $mampPhpPath = $mampBasePath . '/php' . $phpVersion . '/bin/php';

            if (file_exists($mampPhpPath)) {
                return $mampPhpPath;
            }

            // Fallback: buscar qualquer versão disponível no MAMP
            $dirs = scandir($mampBasePath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $phpBin = $mampBasePath . '/' . $dir . '/bin/php';
                if (file_exists($phpBin)) {
                    return $phpBin;
                }
            }
        }

        // 3. Fallback: tentar 'php' no PATH (funciona em produção Linux)
        return 'php';
    }
}
