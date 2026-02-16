<?php
/**
 * CrudManagerController
 * Sistema de gerenciamento de CRUDs
 *
 * Permite criar, listar e gerenciar CRUDs do sistema
 */

class CrudManagerController extends BaseController {

    /**
     * Listar todos os CRUDs cadastrados
     */
    public function index() {
        $this->requireAuth();
        $user = $this->getUser();

        // Buscar CRUDs cadastrados
        $cruds = $this->db()->query(
            "SELECT id, name, table_name, route, status,
                    JSON_LENGTH(fields) as fields_count,
                    has_frontend, has_upload, has_ordering, has_status,
                    frontend_format, created_at, generated_at
             FROM tbl_cruds
             ORDER BY created_at DESC"
        );

        require_once ROOT_PATH . 'admin/views/cruds/index.php';
    }

    /**
     * Exibir formulário de criação de CRUD
     */
    public function create() {
        $this->requireAuth();
        $user = $this->getUser();

        // Buscar todas as tabelas do banco para relacionamentos
        $tables = $this->db()->query("SHOW TABLES");

        require_once ROOT_PATH . 'admin/views/cruds/create.php';
    }

    /**
     * Processar criação de novo CRUD
     */
    public function store() {
        $this->requireAuth();

        try {
            // CSRF
            $this->validateCSRF();

            // Rate Limiting
            if (!RateLimiter::check('crud_create', Auth::id(), 5, 60)) {
                http_response_code(429);
                die('Muitas tentativas. Aguarde 1 minuto.');
            }

            // Validar dados
            $name = Security::sanitize($_POST['name']);
            $tableName = Security::sanitize($_POST['table_name']);

            if (empty($name) || empty($tableName)) {
                throw new Exception('Nome e tabela são obrigatórios');
            }

            // Validar formato da tabela
            if (!preg_match('/^tbl_[a-z_]+$/', $tableName)) {
                throw new Exception('Nome da tabela deve começar com tbl_ e usar apenas letras minúsculas');
            }

            // Verificar se tabela já existe
            $existing = $this->db()->query(
                "SELECT id FROM tbl_cruds WHERE table_name = ?",
                [$tableName]
            );

            if (!empty($existing)) {
                throw new Exception('Já existe um CRUD para esta tabela');
            }

            // Processar campos
            $fields = [];
            if (!empty($_POST['fields'])) {
                foreach ($_POST['fields'] as $fieldData) {
                    if (empty($fieldData['name']) || empty($fieldData['type'])) {
                        continue;
                    }

                    $field = [
                        'name' => Security::sanitize($fieldData['name']),
                        'type' => Security::sanitize($fieldData['type']),
                        'required' => (int)($fieldData['required'] ?? 0),
                        'max_length' => (int)($fieldData['max_length'] ?? 255)
                    ];

                    // Dados específicos por tipo
                    if ($fieldData['type'] === 'upload' && !empty($fieldData['mime_types'])) {
                        $field['mime_types'] = array_map('trim', explode(',', $fieldData['mime_types']));
                    }

                    if ($fieldData['type'] === 'fk') {
                        $field['fk_table'] = Security::sanitize($fieldData['fk_table'] ?? '');
                        $field['fk_column'] = Security::sanitize($fieldData['fk_column'] ?? 'id');
                        $field['display_field'] = Security::sanitize($fieldData['display_field'] ?? '');
                    }

                    $fields[] = $field;
                }
            }

            if (empty($fields)) {
                throw new Exception('Adicione pelo menos um campo');
            }

            // Preparar config
            $hasSlug = isset($_POST['has_slug']) ? 1 : 0;
            $slugSource = Security::sanitize($_POST['slug_source'] ?? '');

            // Validar slug_source se has_slug está ativado
            if ($hasSlug && empty($slugSource)) {
                throw new Exception('Campo base para slug é obrigatório quando slug está ativado');
            }

            $config = [
                'name' => $name,
                'table_name' => $tableName,
                'fields' => $fields,
                'has_ordering' => isset($_POST['has_ordering']) ? 1 : 0,
                'has_status' => isset($_POST['has_status']) ? 1 : 0,
                'has_slug' => $hasSlug,
                'slug_source' => $slugSource,
                'has_frontend' => isset($_POST['has_frontend']) ? 1 : 0,
                'frontend_format' => Security::sanitize($_POST['frontend_format'] ?? 'grid'),
                'has_upload' => $this->hasUploadField($fields) ? 1 : 0
            ];

            // Gerar CRUD
            require_once ROOT_PATH . 'core/CrudGenerator.php';
            $generator = new CrudGenerator($config);
            $result = $generator->generate();

            // Salvar config no banco
            $crudId = Security::generateUUID();

            $this->db()->query(
                "INSERT INTO tbl_cruds (
                    id, name, table_name, controller_name, route,
                    fields, has_ordering, has_status, has_slug, slug_source,
                    has_frontend, frontend_format, has_upload, status, generated_files, generated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $crudId,
                    $config['name'],
                    $config['table_name'],
                    $result['names']['controller'],
                    $result['names']['route'],
                    json_encode($config['fields']),
                    $config['has_ordering'],
                    $config['has_status'],
                    $config['has_slug'],
                    $config['slug_source'],
                    $config['has_frontend'],
                    $config['frontend_format'],
                    $config['has_upload'],
                    'generated',
                    json_encode($result['files']),
                    date('Y-m-d H:i:s')
                ]
            );

            // Audit log
            Logger::getInstance()->audit('CREATE_CRUD_SYSTEM', Auth::id(), [
                'crud_id' => $crudId,
                'name' => $config['name'],
                'table' => $config['table_name']
            ]);

            RateLimiter::increment('crud_create', Auth::id(), 60);

            $_SESSION['success'] = "CRUD '{$config['name']}' criado com sucesso! " . count($result['files']) . " arquivos gerados.";
            $this->redirect('/admin/cruds');

        } catch (Exception $e) {
            Logger::getInstance()->warning('CREATE_CRUD_ERROR', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/cruds/create');
        }
    }

    /**
     * Deletar CRUD completo (hard delete - tudo)
     */
    public function delete($id) {
        $this->requireAuth();

        try {
            // CSRF
            $this->validateCSRF();

            // UUID Validation
            if (!Security::isValidUUID($id)) {
                throw new Exception('ID inválido');
            }

            // Buscar CRUD
            $crud = $this->db()->query(
                "SELECT * FROM tbl_cruds WHERE id = ?",
                [$id]
            );

            if (empty($crud)) {
                throw new Exception('CRUD não encontrado');
            }

            $crud = $crud[0];
            $tableName = $crud['table_name'];
            $route = $crud['route'];
            $generatedFiles = json_decode($crud['generated_files'], true) ?? [];

            // 1. Deletar tabela do banco de dados
            $this->db()->query("DROP TABLE IF EXISTS `{$tableName}`");

            // 2. Deletar pasta de uploads
            $uploadDir = ROOT_PATH . "storage/uploads/{$route}/";
            if (is_dir($uploadDir)) {
                $this->deleteDirectory($uploadDir);
            }

            // 3. Deletar arquivos gerados
            foreach ($generatedFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            // 4. Deletar diretórios vazios
            $viewsDir = ROOT_PATH . "admin/views/{$route}/";
            if (is_dir($viewsDir)) {
                $this->deleteDirectory($viewsDir);
            }

            // 5. Remover do _components.sass
            $componentsFile = ROOT_PATH . "assets/sass/frontend/components/_components.sass";
            if (file_exists($componentsFile)) {
                $content = file_get_contents($componentsFile);
                $content = preg_replace("/\n@use '{$route}'/", '', $content);
                file_put_contents($componentsFile, $content);
            }

            // 6. Deletar registro do tbl_cruds
            $this->db()->query("DELETE FROM tbl_cruds WHERE id = ?", [$id]);

            // Audit log
            Logger::getInstance()->audit('DELETE_CRUD_SYSTEM', Auth::id(), [
                'crud_id' => $id,
                'name' => $crud['name'],
                'table' => $tableName
            ]);

            $_SESSION['success'] = "CRUD '{$crud['name']}' deletado completamente! Tabela, arquivos e uploads removidos.";
            $this->redirect('/admin/cruds');

        } catch (Exception $e) {
            Logger::getInstance()->warning('DELETE_CRUD_ERROR', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/cruds');
        }
    }

    /**
     * Deletar diretório recursivamente
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Verifica se há campo de upload
     */
    private function hasUploadField($fields) {
        foreach ($fields as $field) {
            if ($field['type'] === 'upload') {
                return true;
            }
        }
        return false;
    }
}
