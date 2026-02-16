<?php
/**
 * CrudGenerator
 * Gera automaticamente CRUDs completos com segurança e performance
 *
 * Responsável por:
 * - Gerar migration SQL
 * - Gerar controller admin
 * - Gerar views admin (index, create, edit)
 * - Gerar rotas
 * - Gerar frontend (opcional)
 */

class CrudGenerator {

    private $config;
    private $names;

    public function __construct($config) {
        $this->config = $config;
        $this->names = $this->generateNames($config['name'], $config['table_name']);
    }

    /**
     * Gerar nomes derivados (controller, route, etc)
     */
    private function generateNames($humanName, $tableName) {
        // Remove tbl_ prefix
        $baseName = str_replace('tbl_', '', $tableName);

        // Convert snake_case to PascalCase
        $pascalCase = str_replace('_', '', ucwords($baseName, '_'));

        // Convert to kebab-case
        $kebabCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $pascalCase));

        return [
            'human' => $humanName,                    // Banner Hero
            'table' => $tableName,                    // tbl_banner_hero
            'class' => $pascalCase,                   // BannerHero
            'controller' => $pascalCase . 'Controller', // BannerHeroController
            'route' => $kebabCase,                    // banner-hero
            'var' => lcfirst($pascalCase)             // bannerHero
        ];
    }

    /**
     * Gerar TUDO
     */
    public function generate() {
        $generatedFiles = [];

        // 1. Migration SQL
        $migrationPath = $this->generateMigration();
        $generatedFiles[] = $migrationPath;

        // 2. Executar migration automaticamente
        $this->executeMigration($migrationPath);

        // 3. Controller
        $controllerPath = $this->generateController();
        $generatedFiles[] = $controllerPath;

        // 4. Views (index, create, edit)
        $viewsPaths = $this->generateViews();
        $generatedFiles = array_merge($generatedFiles, $viewsPaths);

        // 5. Rotas (append to admin.php)
        $this->appendRoutes();

        // 6. Frontend (se solicitado)
        if ($this->config['has_frontend']) {
            $frontendPaths = $this->generateFrontend();
            $generatedFiles = array_merge($generatedFiles, $frontendPaths);
        }

        return [
            'success' => true,
            'files' => $generatedFiles,
            'names' => $this->names
        ];
    }

    /**
     * Executar migration automaticamente
     */
    private function executeMigration($migrationPath) {
        $sql = file_get_contents($migrationPath);

        // Conectar ao banco
        $db = DB::connect();

        // Executar SQL
        try {
            $db->query($sql);
        } catch (Exception $e) {
            throw new Exception('Erro ao executar migration: ' . $e->getMessage());
        }
    }

    /**
     * Gerar migration SQL
     */
    private function generateMigration() {
        $table = $this->names['table'];
        $fields = $this->config['fields'];

        $sql = "-- Migration: {$this->names['human']}\n";
        $sql .= "-- Gerado automaticamente em: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "CREATE TABLE IF NOT EXISTS `{$table}` (\n";
        $sql .= "  `id` VARCHAR(36) PRIMARY KEY,\n";

        // Campos customizados
        foreach ($fields as $field) {
            $sql .= "  " . $this->generateFieldSQL($field) . ",\n";
        }

        // Campos opcionais
        if ($this->config['has_ordering']) {
            $sql .= "  `order` INT DEFAULT 0,\n";
        }

        if ($this->config['has_status']) {
            $sql .= "  `ativo` TINYINT(1) DEFAULT 1,\n";
        }

        if ($this->config['has_slug']) {
            $sql .= "  `slug` VARCHAR(255) UNIQUE,\n";
        }

        // Timestamps
        $sql .= "  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";

        // Índices
        if ($this->config['has_slug']) {
            $sql .= "\n  INDEX `idx_slug` (`slug`),\n";
        }
        if ($this->config['has_status']) {
            $sql .= "  INDEX `idx_ativo` (`ativo`),\n";
        }

        // Remover última vírgula
        $sql = rtrim($sql, ",\n") . "\n";

        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        // Salvar arquivo
        $filename = "create_{$table}.sql";
        $path = ROOT_PATH . "database/migrations/{$filename}";
        file_put_contents($path, $sql);

        return $path;
    }

    /**
     * Gerar SQL para um campo específico
     */
    private function generateFieldSQL($field) {
        $name = $field['name'];
        $type = $field['type'];
        $required = $field['required'] ?? false;
        $maxLength = !empty($field['max_length']) ? (int)$field['max_length'] : 255;

        $sql = "`{$name}` ";

        switch ($type) {
            case 'string':
                $sql .= "VARCHAR({$maxLength})";
                break;
            case 'text':
                $sql .= "TEXT";
                break;
            case 'int':
                $sql .= "INT";
                break;
            case 'decimal':
                $sql .= "DECIMAL(10,2)";
                break;
            case 'date':
                $sql .= "DATE";
                break;
            case 'datetime':
                $sql .= "DATETIME";
                break;
            case 'upload':
                $sql .= "VARCHAR(500)"; // Path do arquivo
                break;
            case 'fk':
                $sql .= "VARCHAR(36)"; // UUID
                break;
        }

        $sql .= $required ? " NOT NULL" : " DEFAULT NULL";

        return $sql;
    }

    /**
     * Gerar controller completo
     */
    private function generateController() {
        $className = $this->names['controller'];
        $tableName = $this->names['table'];
        $route = $this->names['route'];
        $humanName = $this->names['human'];
        $varName = $this->names['var'];

        // Gerar campos SELECT
        $selectFields = $this->generateSelectFields();

        // Gerar ORDER BY
        $orderBy = $this->config['has_ordering'] ? '`order` ASC' : 'created_at DESC';

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * {$className}\n";
        $content .= " * Gerencia {$humanName}\n";
        $content .= " * \n";
        $content .= " * Gerado automaticamente pelo Sistema de CRUDs\n";
        $content .= " * Data: " . date('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";
        $content .= "class {$className} extends BaseController {\n\n";

        // ===== INDEX =====
        $content .= "    /**\n";
        $content .= "     * Listar todos (com paginação obrigatória)\n";
        $content .= "     */\n";
        $content .= "    public function index() {\n";
        $content .= "        \$this->requireAuth();\n";
        $content .= "        \$user = \$this->getUser();\n\n";
        $content .= "        // Paginação obrigatória\n";
        $content .= "        \$page = (int) (\$_GET['page'] ?? 1);\n";
        $content .= "        \$perPage = 50;\n";
        $content .= "        if (\$page < 1) \$page = 1;\n";
        $content .= "        \$offset = (\$page - 1) * \$perPage;\n\n";
        $content .= "        // Busca (opcional)\n";
        $content .= "        \$search = Security::sanitize(\$_GET['search'] ?? '');\n";
        $content .= "        \$whereClause = '';\n";
        $content .= "        \$params = [];\n\n";
        $content .= "        if (!empty(\$search)) {\n";
        $content .= "            \$whereClause = 'WHERE ' . " . $this->generateSearchWhere() . ";\n";
        $content .= "            \$params = array_fill(0, " . count($this->getSearchableFields()) . ", \"%{\$search}%\");\n";
        $content .= "        }\n\n";
        $content .= "        // Contar total\n";
        $content .= "        \$countQuery = \"SELECT COUNT(*) as total FROM {$tableName} {\$whereClause}\";\n";
        $content .= "        \$totalResult = \$this->db()->query(\$countQuery, \$params);\n";
        $content .= "        \$total = \$totalResult[0]['total'] ?? 0;\n";
        $content .= "        \$totalPages = ceil(\$total / \$perPage);\n\n";
        $content .= "        // Buscar registros (SELECT específico, NÃO SELECT *)\n";
        $content .= "        \$query = \"SELECT {$selectFields}\n";
        $content .= "                 FROM {$tableName}\n";
        $content .= "                 {\$whereClause}\n";
        $content .= "                 ORDER BY {$orderBy}\n";
        $content .= "                 LIMIT ? OFFSET ?\";\n\n";
        $content .= "        \$params[] = \$perPage;\n";
        $content .= "        \$params[] = \$offset;\n\n";
        $content .= "        \$registros = \$this->db()->query(\$query, \$params);\n\n";
        $content .= "        require_once ROOT_PATH . 'admin/views/{$route}/index.php';\n";
        $content .= "    }\n\n";

        // ===== CREATE =====
        $content .= "    /**\n";
        $content .= "     * Exibir formulário de criação\n";
        $content .= "     */\n";
        $content .= "    public function create() {\n";
        $content .= "        \$this->requireAuth();\n";
        $content .= "        \$user = \$this->getUser();\n\n";
        $content .= $this->generateRelationshipLoads();
        $content .= "        require_once ROOT_PATH . 'admin/views/{$route}/create.php';\n";
        $content .= "    }\n\n";

        // ===== STORE =====
        $content .= $this->generateStoreMethod();

        // ===== EDIT =====
        $content .= $this->generateEditMethod();

        // ===== UPDATE =====
        $content .= $this->generateUpdateMethod();

        // ===== DESTROY =====
        $content .= $this->generateDestroyMethod();

        // Métodos auxiliares (upload, slug, etc)
        if ($this->config['has_upload']) {
            $content .= $this->generateUploadHelper();
        }

        if ($this->config['has_slug']) {
            $content .= $this->generateSlugHelper();
        }

        $content .= "}\n";

        $path = ROOT_PATH . "admin/controllers/{$className}.php";
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Gerar lista de campos para SELECT
     */
    private function generateSelectFields() {
        $fields = ['id'];

        foreach ($this->config['fields'] as $field) {
            $fields[] = $field['name'];
        }

        if ($this->config['has_ordering']) $fields[] = '`order`';
        if ($this->config['has_status']) $fields[] = 'ativo';
        if ($this->config['has_slug']) $fields[] = 'slug';

        $fields[] = 'created_at';
        $fields[] = 'updated_at';

        return implode(', ', $fields);
    }

    /**
     * Gerar WHERE para busca
     */
    private function generateSearchWhere() {
        $searchable = $this->getSearchableFields();
        $conditions = [];

        foreach ($searchable as $field) {
            $conditions[] = "{$field} LIKE ?";
        }

        return "'" . implode(' OR ', $conditions) . "'";
    }

    /**
     * Campos pesquisáveis (string e text)
     */
    private function getSearchableFields() {
        $searchable = [];

        foreach ($this->config['fields'] as $field) {
            if (in_array($field['type'], ['string', 'text'])) {
                $searchable[] = $field['name'];
            }
        }

        return $searchable;
    }

    /**
     * Gerar código para carregar relacionamentos
     */
    private function generateRelationshipLoads() {
        $code = '';

        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'fk' && !empty($field['fk_table'])) {
                $varName = str_replace('tbl_', '', $field['fk_table']);
                $displayField = $field['display_field'] ?: 'name';

                $code .= "        // Carregar opções para {$field['name']}\n";
                $code .= "        \${$varName} = \$this->db()->query(\n";
                $code .= "            \"SELECT id, {$displayField} FROM {$field['fk_table']} ORDER BY {$displayField}\"\n";
                $code .= "        );\n\n";
            }
        }

        return $code;
    }

    /**
     * Gerar método store() completo
     */
    private function generateStoreMethod() {
        $tableName = $this->names['table'];
        $route = $this->names['route'];
        $humanUpper = strtoupper(str_replace(' ', '_', $this->names['human']));

        $content = "    /**\n";
        $content .= "     * Processar criação\n";
        $content .= "     */\n";
        $content .= "    public function store() {\n";
        $content .= "        \$this->requireAuth();\n\n";
        $content .= "        try {\n";
        $content .= "            // CSRF Validation\n";
        $content .= "            \$this->validateCSRF();\n\n";
        $content .= "            // Rate Limiting\n";
        $content .= "            if (!RateLimiter::check('{$route}_create', Auth::id(), 5, 60)) {\n";
        $content .= "                http_response_code(429);\n";
        $content .= "                die('Muitas tentativas. Aguarde 1 minuto.');\n";
        $content .= "            }\n\n";

        // Sanitizar campos
        $content .= "            // Sanitizar inputs\n";
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] !== 'upload') {
                $content .= "            \${$field['name']} = Security::sanitize(\$_POST['{$field['name']}'] ?? '');\n";
            }
        }
        $content .= "\n";

        // Validações
        $content .= "            // Validações\n";
        foreach ($this->config['fields'] as $field) {
            // Skip upload fields - they have their own validation
            if ($field['type'] === 'upload') {
                continue;
            }

            if ($field['required']) {
                $content .= "            if (empty(\${$field['name']})) {\n";
                $content .= "                throw new Exception('{$field['name']} é obrigatório');\n";
                $content .= "            }\n";
            }

            if ($field['type'] === 'string' && !empty($field['max_length'])) {
                $content .= "            if (strlen(\${$field['name']}) > {$field['max_length']}) {\n";
                $content .= "                throw new Exception('{$field['name']} deve ter no máximo {$field['max_length']} caracteres');\n";
                $content .= "            }\n";
            }
        }
        $content .= "\n";

        // Upload de arquivos
        if ($this->config['has_upload']) {
            $content .= $this->generateUploadLogic('store');
        }

        // Slug
        if ($this->config['has_slug']) {
            $content .= "            // Gerar slug\n";
            $content .= "            \$slug = \$this->generateSlug(\${$this->config['slug_source']});\n\n";
        }

        // INSERT
        $content .= "            // Gerar ID\n";
        $content .= "            \$id = Security::generateUUID();\n\n";

        $insertFields = ['id'];
        $insertPlaceholders = ['?'];
        $insertValues = ['$id'];

        foreach ($this->config['fields'] as $field) {
            $insertFields[] = $field['name'];
            $insertPlaceholders[] = '?';

            if ($field['type'] === 'upload') {
                $insertValues[] = '$' . $field['name'] . 'Path';
            } else {
                $insertValues[] = '$' . $field['name'];
            }
        }

        if ($this->config['has_ordering']) {
            $insertFields[] = '`order`';
            $insertPlaceholders[] = '?';
            $insertValues[] = '(int)($_POST[\'order\'] ?? 0)';
        }

        if ($this->config['has_status']) {
            $insertFields[] = 'ativo';
            $insertPlaceholders[] = '?';
            $insertValues[] = 'isset($_POST[\'ativo\']) ? 1 : 0';
        }

        if ($this->config['has_slug']) {
            $insertFields[] = 'slug';
            $insertPlaceholders[] = '?';
            $insertValues[] = '$slug';
        }

        $insertFields[] = 'created_at';
        $insertPlaceholders[] = '?';
        $insertValues[] = 'date(\'Y-m-d H:i:s\')';

        $content .= "            // INSERT\n";
        $content .= "            \$this->db()->query(\n";
        $content .= "                \"INSERT INTO {$tableName} (" . implode(', ', $insertFields) . ")\n";
        $content .= "                 VALUES (" . implode(', ', $insertPlaceholders) . ")\",\n";
        $content .= "                [" . implode(', ', $insertValues) . "]\n";
        $content .= "            );\n\n";

        // Audit Log
        $content .= "            // Audit Log\n";
        $content .= "            Logger::getInstance()->audit('CREATE_{$humanUpper}', Auth::id(), [\n";
        $content .= "                'id' => \$id,\n";
        $content .= "                'table' => '{$tableName}'\n";
        $content .= "            ]);\n\n";

        // Rate Limit increment
        $content .= "            RateLimiter::increment('{$route}_create', Auth::id(), 60);\n\n";

        // Sucesso
        $content .= "            \$_SESSION['success'] = 'Registro criado com sucesso!';\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n\n";

        $content .= "        } catch (Exception \$e) {\n";
        $content .= "            Logger::getInstance()->warning('CREATE_{$humanUpper}_ERROR', [\n";
        $content .= "                'error' => \$e->getMessage(),\n";
        $content .= "                'admin_id' => Auth::id()\n";
        $content .= "            ]);\n\n";
        $content .= "            \$_SESSION['error'] = \$e->getMessage();\n";
        $content .= "            \$this->redirect('/admin/{$route}/create');\n";
        $content .= "        }\n";
        $content .= "    }\n\n";

        return $content;
    }

    /**
     * Gerar lógica de upload
     */
    private function generateUploadLogic($context) {
        $code = "";

        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'upload') {
                $required = $field['required'] && $context === 'store';
                $mimes = $field['mime_types'] ?? ['image/jpeg', 'image/png', 'image/webp'];

                // Normalizar MIME types (aceitar extensões e converter)
                $mimes = $this->normalizeMimeTypes($mimes);

                $code .= "            // Upload: {$field['name']}\n";

                if ($required) {
                    $code .= "            if (!isset(\$_FILES['{$field['name']}']) || \$_FILES['{$field['name']}']['error'] === UPLOAD_ERR_NO_FILE) {\n";
                    $code .= "                throw new Exception('{$field['name']} é obrigatório');\n";
                    $code .= "            }\n";
                    $code .= "            if (\$_FILES['{$field['name']}']['error'] !== UPLOAD_ERR_OK) {\n";
                    $code .= "                throw new Exception('Erro no upload de {$field['name']}');\n";
                    $code .= "            }\n\n";
                } else {
                    $code .= "            if (!empty(\$_FILES['{$field['name']}']['tmp_name'])) {\n";
                }

                $code .= "                // Validar tamanho (5MB máximo)\n";
                $code .= "                \$maxSize = 5 * 1024 * 1024;\n";
                $code .= "                if (\$_FILES['{$field['name']}']['size'] > \$maxSize) {\n";
                $code .= "                    throw new Exception('{$field['name']}: arquivo muito grande. Máximo 5MB');\n";
                $code .= "                }\n\n";

                $code .= "                // Validar MIME type\n";
                $code .= "                \$allowedMimes = " . var_export($mimes, true) . ";\n";
                $code .= "                \$finfo = finfo_open(FILEINFO_MIME_TYPE);\n";
                $code .= "                \$mimeType = finfo_file(\$finfo, \$_FILES['{$field['name']}']['tmp_name']);\n";
                $code .= "                finfo_close(\$finfo);\n\n";
                $code .= "                if (!in_array(\$mimeType, \$allowedMimes)) {\n";
                $code .= "                    throw new Exception('{$field['name']}: tipo de arquivo não permitido');\n";
                $code .= "                }\n\n";

                $code .= "                // Validar extensão\n";
                $code .= "                \$extension = strtolower(pathinfo(\$_FILES['{$field['name']}']['name'], PATHINFO_EXTENSION));\n";
                $code .= "                \$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];\n";
                $code .= "                if (!in_array(\$extension, \$allowedExtensions)) {\n";
                $code .= "                    throw new Exception('{$field['name']}: extensão não permitida');\n";
                $code .= "                }\n\n";

                $code .= "                // Criar diretório\n";
                $code .= "                \$uploadDir = __DIR__ . '/../../storage/uploads/{$this->names['route']}/';\n";
                $code .= "                if (!is_dir(\$uploadDir)) {\n";
                $code .= "                    mkdir(\$uploadDir, 0755, true);\n";
                $code .= "                }\n\n";

                $code .= "                // Gerar nome único\n";
                $code .= "                \$fileName = Security::generateUUID() . '_' . time() . '.' . \$extension;\n";
                $code .= "                \$filePath = \$uploadDir . \$fileName;\n\n";

                $code .= "                // Mover arquivo\n";
                $code .= "                if (!move_uploaded_file(\$_FILES['{$field['name']}']['tmp_name'], \$filePath)) {\n";
                $code .= "                    throw new Exception('Erro ao salvar {$field['name']}');\n";
                $code .= "                }\n\n";

                $code .= "                chmod(\$filePath, 0644);\n";
                $code .= "                \${$field['name']}Path = '/storage/uploads/{$this->names['route']}/' . \$fileName;\n";

                if (!$required) {
                    $code .= "            } else {\n";
                    $code .= "                \${$field['name']}Path = null;\n";
                    $code .= "            }\n";
                }

                $code .= "\n";
            }
        }

        return $code;
    }

    /**
     * Gerar método edit()
     */
    private function generateEditMethod() {
        $tableName = $this->names['table'];
        $route = $this->names['route'];

        $content = "    /**\n";
        $content .= "     * Exibir formulário de edição\n";
        $content .= "     */\n";
        $content .= "    public function edit(\$id) {\n";
        $content .= "        \$this->requireAuth();\n";
        $content .= "        \$user = \$this->getUser();\n\n";
        $content .= "        // UUID Validation (PASSO 0)\n";
        $content .= "        if (!Security::isValidUUID(\$id)) {\n";
        $content .= "            \$_SESSION['error'] = 'ID inválido';\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n";
        $content .= "        }\n\n";
        $content .= "        // Buscar registro\n";
        $content .= "        \$registro = \$this->db()->query(\n";
        $content .= "            \"SELECT * FROM {$tableName} WHERE id = ?\",\n";
        $content .= "            [\$id]\n";
        $content .= "        );\n\n";
        $content .= "        if (empty(\$registro)) {\n";
        $content .= "            \$_SESSION['error'] = 'Registro não encontrado';\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n";
        $content .= "        }\n\n";
        $content .= "        \$registro = \$registro[0];\n\n";
        $content .= $this->generateRelationshipLoads();
        $content .= "        require_once ROOT_PATH . 'admin/views/{$route}/edit.php';\n";
        $content .= "    }\n\n";

        return $content;
    }

    /**
     * Gerar método update()
     */
    private function generateUpdateMethod() {
        $tableName = $this->names['table'];
        $route = $this->names['route'];
        $humanUpper = strtoupper(str_replace(' ', '_', $this->names['human']));

        $content = "    /**\n";
        $content .= "     * Processar atualização\n";
        $content .= "     */\n";
        $content .= "    public function update(\$id) {\n";
        $content .= "        \$this->requireAuth();\n\n";
        $content .= "        try {\n";
        $content .= "            // CSRF Validation\n";
        $content .= "            \$this->validateCSRF();\n\n";
        $content .= "            // UUID Validation\n";
        $content .= "            if (!Security::isValidUUID(\$id)) {\n";
        $content .= "                throw new Exception('ID inválido');\n";
        $content .= "            }\n\n";
        $content .= "            // Rate Limiting\n";
        $content .= "            if (!RateLimiter::check('{$route}_update', Auth::id(), 10, 60)) {\n";
        $content .= "                http_response_code(429);\n";
        $content .= "                die('Muitas tentativas');\n";
        $content .= "            }\n\n";

        // Sanitizar campos
        $content .= "            // Sanitizar inputs\n";
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] !== 'upload') {
                $content .= "            \${$field['name']} = Security::sanitize(\$_POST['{$field['name']}'] ?? '');\n";
            }
        }
        $content .= "\n";

        // Validações
        $content .= "            // Validações\n";
        foreach ($this->config['fields'] as $field) {
            if ($field['required'] && $field['type'] !== 'upload') {
                $content .= "            if (empty(\${$field['name']})) {\n";
                $content .= "                throw new Exception('{$field['name']} é obrigatório');\n";
                $content .= "            }\n";
            }
        }
        $content .= "\n";

        // Upload (opcional no update)
        if ($this->config['has_upload']) {
            $content .= $this->generateUploadWithDelete();
        }

        // Slug
        if ($this->config['has_slug']) {
            $content .= "            // Atualizar slug se campo base mudou\n";
            $content .= "            \$slug = \$this->generateSlug(\${$this->config['slug_source']});\n\n";
        }

        // UPDATE
        $content .= "            // Preparar dados para UPDATE\n";
        $content .= "            \$data = [];\n";
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] !== 'upload') {
                $content .= "            \$data['{$field['name']}'] = \${$field['name']};\n";
            }
        }

        if ($this->config['has_ordering']) {
            $content .= "            \$data['order'] = (int)(\$_POST['order'] ?? 0);\n";
        }

        if ($this->config['has_status']) {
            $content .= "            \$data['ativo'] = isset(\$_POST['ativo']) ? 1 : 0;\n";
        }

        if ($this->config['has_slug']) {
            $content .= "            \$data['slug'] = \$slug;\n";
        }

        // Adicionar uploads se houver
        if ($this->config['has_upload']) {
            foreach ($this->config['fields'] as $field) {
                if ($field['type'] === 'upload') {
                    $content .= "            if (isset(\${$field['name']}Path)) {\n";
                    $content .= "                \$data['{$field['name']}'] = \${$field['name']}Path;\n";
                    $content .= "            }\n";
                }
            }
        }

        $content .= "            \$data['updated_at'] = date('Y-m-d H:i:s');\n\n";

        $content .= "            // Montar query UPDATE\n";
        $content .= "            \$setClauses = [];\n";
        $content .= "            \$values = [];\n";
        $content .= "            foreach (\$data as \$key => \$value) {\n";
        $content .= "                \$setClauses[] = \"`\$key` = ?\";\n";
        $content .= "                \$values[] = \$value;\n";
        $content .= "            }\n";
        $content .= "            \$values[] = \$id;\n\n";
        $content .= "            \$sql = \"UPDATE {$tableName} SET \" . implode(', ', \$setClauses) . \" WHERE id = ?\";\n";
        $content .= "            \$this->db()->query(\$sql, \$values);\n\n";

        // Audit Log
        $content .= "            // Audit Log\n";
        $content .= "            Logger::getInstance()->audit('UPDATE_{$humanUpper}', Auth::id(), [\n";
        $content .= "                'id' => \$id,\n";
        $content .= "                'fields_updated' => array_keys(\$data)\n";
        $content .= "            ]);\n\n";

        $content .= "            RateLimiter::increment('{$route}_update', Auth::id(), 60);\n\n";
        $content .= "            \$_SESSION['success'] = 'Registro atualizado com sucesso!';\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n\n";

        $content .= "        } catch (Exception \$e) {\n";
        $content .= "            Logger::getInstance()->warning('UPDATE_{$humanUpper}_ERROR', [\n";
        $content .= "                'error' => \$e->getMessage(),\n";
        $content .= "                'id' => \$id\n";
        $content .= "            ]);\n\n";
        $content .= "            \$_SESSION['error'] = \$e->getMessage();\n";
        $content .= "            \$this->redirect('/admin/{$route}/' . \$id . '/edit');\n";
        $content .= "        }\n";
        $content .= "    }\n\n";

        return $content;
    }

    /**
     * Upload com delete de arquivo antigo
     */
    private function generateUploadWithDelete() {
        $code = "";

        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'upload') {
                $code .= "            // Upload: {$field['name']} (opcional no update)\n";
                $code .= "            if (!empty(\$_FILES['{$field['name']}']['tmp_name'])) {\n";
                $code .= "                // Buscar registro atual para deletar arquivo antigo\n";
                $code .= "                \$current = \$this->db()->query(\"SELECT {$field['name']} FROM {$this->names['table']} WHERE id = ?\", [\$id]);\n";
                $code .= "                if (!empty(\$current) && !empty(\$current[0]['{$field['name']}'])) {\n";
                $code .= "                    \$oldFile = __DIR__ . '/../../' . ltrim(\$current[0]['{$field['name']}'], '/');\n";
                $code .= "                    \n";
                $code .= "                    // Path traversal protection\n";
                $code .= "                    \$uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');\n";
                $code .= "                    \$oldFileRealPath = realpath(\$oldFile);\n";
                $code .= "                    \n";
                $code .= "                    if (\$oldFileRealPath && strpos(\$oldFileRealPath, \$uploadBasePath) === 0) {\n";
                $code .= "                        if (file_exists(\$oldFile)) {\n";
                $code .= "                            unlink(\$oldFile);\n";
                $code .= "                        }\n";
                $code .= "                    } else {\n";
                $code .= "                        Logger::getInstance()->critical('PATH_TRAVERSAL_ATTEMPT', [\n";
                $code .= "                            'file' => \$oldFile,\n";
                $code .= "                            'admin_id' => Auth::id()\n";
                $code .= "                        ]);\n";
                $code .= "                    }\n";
                $code .= "                }\n\n";
                $code .= "                // Upload novo arquivo (mesma lógica do store)\n";
                $code .= "                \$maxSize = 5 * 1024 * 1024;\n";
                $code .= "                if (\$_FILES['{$field['name']}']['size'] > \$maxSize) {\n";
                $code .= "                    throw new Exception('{$field['name']}: arquivo muito grande');\n";
                $code .= "                }\n\n";
                $code .= "                \$finfo = finfo_open(FILEINFO_MIME_TYPE);\n";
                $code .= "                \$mimeType = finfo_file(\$finfo, \$_FILES['{$field['name']}']['tmp_name']);\n";
                $code .= "                finfo_close(\$finfo);\n\n";
                $code .= "                \$allowedMimes = " . var_export($field['mime_types'] ?? ['image/jpeg', 'image/png'], true) . ";\n";
                $code .= "                if (!in_array(\$mimeType, \$allowedMimes)) {\n";
                $code .= "                    throw new Exception('{$field['name']}: tipo não permitido');\n";
                $code .= "                }\n\n";
                $code .= "                \$extension = strtolower(pathinfo(\$_FILES['{$field['name']}']['name'], PATHINFO_EXTENSION));\n";
                $code .= "                \$uploadDir = __DIR__ . '/../../storage/uploads/{$this->names['route']}/';\n";
                $code .= "                \$fileName = Security::generateUUID() . '_' . time() . '.' . \$extension;\n";
                $code .= "                \$filePath = \$uploadDir . \$fileName;\n\n";
                $code .= "                if (!move_uploaded_file(\$_FILES['{$field['name']}']['tmp_name'], \$filePath)) {\n";
                $code .= "                    throw new Exception('Erro ao salvar {$field['name']}');\n";
                $code .= "                }\n\n";
                $code .= "                chmod(\$filePath, 0644);\n";
                $code .= "                \${$field['name']}Path = '/storage/uploads/{$this->names['route']}/' . \$fileName;\n";
                $code .= "            }\n\n";
            }
        }

        return $code;
    }

    /**
     * Gerar método destroy()
     */
    private function generateDestroyMethod() {
        $tableName = $this->names['table'];
        $route = $this->names['route'];
        $humanUpper = strtoupper(str_replace(' ', '_', $this->names['human']));

        $content = "    /**\n";
        $content .= "     * Deletar registro (hard delete)\n";
        $content .= "     */\n";
        $content .= "    public function destroy(\$id) {\n";
        $content .= "        \$this->requireAuth();\n\n";
        $content .= "        try {\n";
        $content .= "            // CSRF Validation\n";
        $content .= "            \$this->validateCSRF();\n\n";
        $content .= "            // UUID Validation\n";
        $content .= "            if (!Security::isValidUUID(\$id)) {\n";
        $content .= "                throw new Exception('ID inválido');\n";
        $content .= "            }\n\n";
        $content .= "            // Rate Limiting\n";
        $content .= "            if (!RateLimiter::check('{$route}_delete', Auth::id(), 5, 60)) {\n";
        $content .= "                http_response_code(429);\n";
        $content .= "                die('Muitas tentativas');\n";
        $content .= "            }\n\n";
        $content .= "            // Buscar registro\n";
        $content .= "            \$registro = \$this->db()->query(\n";
        $content .= "                \"SELECT * FROM {$tableName} WHERE id = ?\",\n";
        $content .= "                [\$id]\n";
        $content .= "            );\n\n";
        $content .= "            if (empty(\$registro)) {\n";
        $content .= "                throw new Exception('Registro não encontrado');\n";
        $content .= "            }\n\n";
        $content .= "            \$registro = \$registro[0];\n\n";

        // Deletar arquivos físicos
        if ($this->config['has_upload']) {
            $content .= "            // Deletar arquivos físicos\n";
            foreach ($this->config['fields'] as $field) {
                if ($field['type'] === 'upload') {
                    $content .= "            if (!empty(\$registro['{$field['name']}'])) {\n";
                    $content .= "                \$filePath = __DIR__ . '/../../' . ltrim(\$registro['{$field['name']}'], '/');\n";
                    $content .= "                \n";
                    $content .= "                // Path traversal protection\n";
                    $content .= "                \$uploadBasePath = realpath(__DIR__ . '/../../storage/uploads/');\n";
                    $content .= "                \$fileRealPath = realpath(\$filePath);\n";
                    $content .= "                \n";
                    $content .= "                if (\$fileRealPath && strpos(\$fileRealPath, \$uploadBasePath) === 0) {\n";
                    $content .= "                    if (file_exists(\$filePath)) {\n";
                    $content .= "                        unlink(\$filePath);\n";
                    $content .= "                    }\n";
                    $content .= "                }\n";
                    $content .= "            }\n\n";
                }
            }
        }

        // DELETE
        $content .= "            // DELETE\n";
        $content .= "            \$this->db()->delete('{$tableName}', ['id' => \$id]);\n\n";

        // Audit Log
        $content .= "            // Audit Log (com snapshot)\n";
        $content .= "            Logger::getInstance()->audit('DELETE_{$humanUpper}', Auth::id(), [\n";
        $content .= "                'id' => \$id,\n";
        $content .= "                'snapshot' => \$registro\n";
        $content .= "            ]);\n\n";

        $content .= "            RateLimiter::increment('{$route}_delete', Auth::id(), 60);\n\n";
        $content .= "            \$_SESSION['success'] = 'Registro removido com sucesso!';\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n\n";

        $content .= "        } catch (Exception \$e) {\n";
        $content .= "            Logger::getInstance()->warning('DELETE_{$humanUpper}_ERROR', [\n";
        $content .= "                'error' => \$e->getMessage(),\n";
        $content .= "                'id' => \$id\n";
        $content .= "            ]);\n\n";
        $content .= "            \$_SESSION['error'] = \$e->getMessage();\n";
        $content .= "            \$this->redirect('/admin/{$route}');\n";
        $content .= "        }\n";
        $content .= "    }\n\n";

        return $content;
    }

    /**
     * Gerar método helper de upload
     */
    private function generateUploadHelper() {
        return "";  // Lógica já está inline nos métodos
    }

    /**
     * Gerar método helper de slug
     */
    private function generateSlugHelper() {
        $content = "    /**\n";
        $content .= "     * Gerar slug único\n";
        $content .= "     */\n";
        $content .= "    private function generateSlug(\$text) {\n";
        $content .= "        \$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', \$text)));\n";
        $content .= "        \$slug = preg_replace('/-+/', '-', \$slug);\n";
        $content .= "        \$slug = trim(\$slug, '-');\n\n";
        $content .= "        // Garantir unicidade\n";
        $content .= "        \$originalSlug = \$slug;\n";
        $content .= "        \$counter = 1;\n\n";
        $content .= "        while (true) {\n";
        $content .= "            \$exists = \$this->db()->query(\n";
        $content .= "                \"SELECT id FROM {$this->names['table']} WHERE slug = ?\",\n";
        $content .= "                [\$slug]\n";
        $content .= "            );\n\n";
        $content .= "            if (empty(\$exists)) {\n";
        $content .= "                break;\n";
        $content .= "            }\n\n";
        $content .= "            \$slug = \$originalSlug . '-' . \$counter;\n";
        $content .= "            \$counter++;\n";
        $content .= "        }\n\n";
        $content .= "        return \$slug;\n";
        $content .= "    }\n\n";

        return $content;
    }

    /**
     * Gerar views completas
     */
    private function generateViews() {
        $route = $this->names['route'];
        $viewsDir = ROOT_PATH . "admin/views/{$route}";

        if (!is_dir($viewsDir)) {
            mkdir($viewsDir, 0755, true);
        }

        $paths = [];

        // index.php
        $indexPath = $viewsDir . '/index.php';
        file_put_contents($indexPath, $this->generateIndexView());
        $paths[] = $indexPath;

        // create.php
        $createPath = $viewsDir . '/create.php';
        file_put_contents($createPath, $this->generateCreateView());
        $paths[] = $createPath;

        // edit.php
        $editPath = $viewsDir . '/edit.php';
        file_put_contents($editPath, $this->generateEditView());
        $paths[] = $editPath;

        return $paths;
    }

    /**
     * Gerar view index.php (listagem)
     */
    private function generateIndexView() {
        $route = $this->names['route'];
        $humanName = $this->names['human'];
        $humanPlural = $humanName . 's'; // Simplificado

        $content = "<!DOCTYPE html>\n";
        $content .= "<html lang=\"pt-BR\">\n\n";
        $content .= "<head>\n";
        $content .= "\t<?php\n";
        $content .= "\t\$loadAdminJs = true;\n";
        $content .= "\trequire_once __DIR__ . '/../../includes/_admin-head.php';\n";
        $content .= "\t?>\n";
        $content .= "\t<title>{$humanPlural} - <?= ADMIN_NAME ?></title>\n";
        $content .= "</head>\n\n";
        $content .= "<body class=\"m-pagebasebody\">\n\n";
        $content .= "  <?php require_once __DIR__ . '/../../includes/header.php'; ?>\n\n";
        $content .= "\t<div class=\"m-pagebase\">\n\n";
        $content .= "\t\t<div class=\"m-pagebase__header\">\n";
        $content .= "\t\t\t<h1>{$humanPlural} (<?= \$total ?>)</h1>\n";
        $content .= "\t\t\t<a href=\"<?= url('/admin/{$route}/create') ?>\" class=\"m-pagebase__btn m-pagebase__btn--widthauto\">+ Novo</a>\n";
        $content .= "\t\t</div>\n\n";

        // Alerts
        $content .= "\t\t<?php if (isset(\$_SESSION['success'])): ?>\n";
        $content .= "\t\t\t<div class=\"alert alert--success\"><?= htmlspecialchars(\$_SESSION['success']) ?></div>\n";
        $content .= "\t\t\t<?php unset(\$_SESSION['success']); ?>\n";
        $content .= "\t\t<?php endif; ?>\n\n";
        $content .= "\t\t<?php if (isset(\$_SESSION['error'])): ?>\n";
        $content .= "\t\t\t<div class=\"alert alert--error\"><?= htmlspecialchars(\$_SESSION['error']) ?></div>\n";
        $content .= "\t\t\t<?php unset(\$_SESSION['error']); ?>\n";
        $content .= "\t\t<?php endif; ?>\n\n";

        // Frontend usage box (se tiver frontend)
        if ($this->config['has_frontend']) {
            $format = $this->config['frontend_format'] ?? 'grid';
            $content .= "\t\t<!-- Como usar no frontend -->\n";
            $content .= "\t\t<div style=\"background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; margin-bottom: 20px; border-radius: 4px;\">\n";
            $content .= "\t\t\t<div style=\"margin-bottom: 10px;\">\n";
            $content .= "\t\t\t\t<strong style=\"color: #0369a1; font-size: 15px;\">💡 Como usar no frontend</strong>\n";
            $content .= "\t\t\t\t<span style=\"margin-left: 15px; color: #64748b; font-size: 13px;\">Formato: <strong>{$format}</strong></span>\n";
            $content .= "\t\t\t</div>\n";
            $content .= "\t\t\t<div style=\"margin-bottom: 10px;\">\n";
            $content .= "\t\t\t\t<strong style=\"color: #475569; font-size: 13px;\">Implementação:</strong>\n";
            $content .= "\t\t\t</div>\n";
            $content .= "\t\t\t<pre style=\"background: #fff; padding: 12px; margin: 0 0 10px 0; border-radius: 4px; overflow-x: auto; border: 1px solid #cbd5e1; font-size: 13px;\"><code>&lt;?php Core::requireInclude('frontend/views/partials/{$route}.php'); ?&gt;</code></pre>\n";
            $content .= "\t\t\t<div style=\"margin-bottom: 10px;\">\n";
            $content .= "\t\t\t\t<strong style=\"color: #475569; font-size: 13px;\">Personalização (SASS):</strong>\n";
            $content .= "\t\t\t</div>\n";
            $content .= "\t\t\t<pre style=\"background: #fff; padding: 12px; margin: 0; border-radius: 4px; overflow-x: auto; border: 1px solid #cbd5e1; font-size: 13px;\"><code>assets/sass/frontend/components/_{$route}.sass</code></pre>\n";
            $content .= "\t\t</div>\n\n";
        }

        // Busca
        $content .= "\t\t<!-- Busca -->\n";
        $content .= "\t\t<form method=\"GET\" action=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__search\">\n";
        $content .= "\t\t\t<input type=\"text\" name=\"search\" placeholder=\"Buscar...\" value=\"<?= htmlspecialchars(\$search ?? '') ?>\" class=\"m-pagebase__input\" />\n";
        $content .= "\t\t\t<button type=\"submit\" class=\"m-pagebase__btn m-pagebase__btn--sm\">Buscar</button>\n";
        $content .= "\t\t\t<?php if (!empty(\$search)): ?>\n";
        $content .= "\t\t\t\t<a href=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__btn-secondary m-pagebase__btn--sm\">Limpar</a>\n";
        $content .= "\t\t\t<?php endif; ?>\n";
        $content .= "\t\t</form>\n\n";

        // Tabela
        $content .= "\t\t<?php if (!empty(\$registros)): ?>\n";
        $content .= "\t\t<table class=\"m-pagebase__table\">\n";
        $content .= "\t\t\t<thead>\n";
        $content .= "\t\t\t\t<tr>\n";

        // Cabeçalhos das colunas
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] !== 'upload') {
                $label = ucfirst(str_replace('_', ' ', $field['name']));
                $content .= "\t\t\t\t\t<th>{$label}</th>\n";
            } elseif ($field['type'] === 'upload') {
                $content .= "\t\t\t\t\t<th>Imagem</th>\n";
            }
        }

        if ($this->config['has_ordering']) {
            $content .= "\t\t\t\t\t<th>Ordem</th>\n";
        }

        if ($this->config['has_status']) {
            $content .= "\t\t\t\t\t<th>Status</th>\n";
        }

        $content .= "\t\t\t\t\t<th>Ações</th>\n";
        $content .= "\t\t\t\t</tr>\n";
        $content .= "\t\t\t</thead>\n";
        $content .= "\t\t\t<tbody>\n";
        $content .= "\t\t\t\t<?php foreach (\$registros as \$item): ?>\n";
        $content .= "\t\t\t\t<tr>\n";

        // Colunas de dados
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'upload') {
                $content .= "\t\t\t\t\t<td>\n";
                $content .= "\t\t\t\t\t\t<?php if (!empty(\$item['{$field['name']}'])): ?>\n";
                $content .= "\t\t\t\t\t\t\t<img src=\"<?= url(\$item['{$field['name']}']) ?>\" class=\"m-pagebase__thumb\" alt=\"\" />\n";
                $content .= "\t\t\t\t\t\t<?php else: ?>\n";
                $content .= "\t\t\t\t\t\t\t<span>—</span>\n";
                $content .= "\t\t\t\t\t\t<?php endif; ?>\n";
                $content .= "\t\t\t\t\t</td>\n";
            } elseif ($field['type'] === 'text') {
                $content .= "\t\t\t\t\t<td><?= !empty(\$item['{$field['name']}']) ? substr(htmlspecialchars(\$item['{$field['name']}']), 0, 50) . '...' : '—' ?></td>\n";
            } else {
                $content .= "\t\t\t\t\t<td><?= htmlspecialchars(\$item['{$field['name']}'] ?? '—') ?></td>\n";
            }
        }

        if ($this->config['has_ordering']) {
            $content .= "\t\t\t\t\t<td><?= htmlspecialchars(\$item['order']) ?></td>\n";
        }

        if ($this->config['has_status']) {
            $content .= "\t\t\t\t\t<td>\n";
            $content .= "\t\t\t\t\t\t<?php if (\$item['ativo']): ?>\n";
            $content .= "\t\t\t\t\t\t\t<span class=\"m-pagebase__badge m-pagebase__badge--success\">ATIVO</span>\n";
            $content .= "\t\t\t\t\t\t<?php else: ?>\n";
            $content .= "\t\t\t\t\t\t\t<span class=\"m-pagebase__badge m-pagebase__badge--inactive\">INATIVO</span>\n";
            $content .= "\t\t\t\t\t\t<?php endif; ?>\n";
            $content .= "\t\t\t\t\t</td>\n";
        }

        // Ações
        $content .= "\t\t\t\t\t<td class=\"m-pagebase__actions\">\n";
        $content .= "\t\t\t\t\t\t<a href=\"<?= url('/admin/{$route}/' . htmlspecialchars(\$item['id']) . '/edit') ?>\" class=\"m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--edit\"><i data-lucide=\"pencil\"></i> Editar</a>\n";
        $content .= "\t\t\t\t\t\t<form method=\"POST\" action=\"<?= url('/admin/{$route}/' . htmlspecialchars(\$item['id']) . '/delete') ?>\" style=\"display:inline;\">\n";
        $content .= "\t\t\t\t\t\t\t<input type=\"hidden\" name=\"csrf_token\" value=\"<?= Security::generateCSRF() ?>\">\n";
        $content .= "\t\t\t\t\t\t\t<button type=\"submit\" class=\"m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--widthauto m-pagebase__btn--danger\" onclick=\"return confirm('Tem certeza?')\"><i data-lucide=\"trash-2\"></i> Deletar</button>\n";
        $content .= "\t\t\t\t\t\t</form>\n";
        $content .= "\t\t\t\t\t</td>\n";
        $content .= "\t\t\t\t</tr>\n";
        $content .= "\t\t\t\t<?php endforeach; ?>\n";
        $content .= "\t\t\t</tbody>\n";
        $content .= "\t\t</table>\n\n";

        // Paginação
        $content .= "\t\t<!-- Paginação -->\n";
        $content .= "\t\t<?php if (\$totalPages > 1): ?>\n";
        $content .= "\t\t<div class=\"m-pagebase__pagination\">\n";
        $content .= "\t\t\t<?php if (\$page > 1): ?>\n";
        $content .= "\t\t\t\t<a href=\"<?= url('/admin/{$route}?page=' . (\$page - 1) . (!empty(\$search) ? '&search=' . urlencode(\$search) : '')) ?>\" class=\"m-pagebase__btn m-pagebase__btn--sm\">← Anterior</a>\n";
        $content .= "\t\t\t<?php endif; ?>\n\n";
        $content .= "\t\t\t<span>Página <?= \$page ?> de <?= \$totalPages ?></span>\n\n";
        $content .= "\t\t\t<?php if (\$page < \$totalPages): ?>\n";
        $content .= "\t\t\t\t<a href=\"<?= url('/admin/{$route}?page=' . (\$page + 1) . (!empty(\$search) ? '&search=' . urlencode(\$search) : '')) ?>\" class=\"m-pagebase__btn m-pagebase__btn--sm\">Próxima →</a>\n";
        $content .= "\t\t\t<?php endif; ?>\n";
        $content .= "\t\t</div>\n";
        $content .= "\t\t<?php endif; ?>\n\n";

        $content .= "\t\t<?php else: ?>\n";
        $content .= "\t\t\t<p class=\"m-pagebase__empty\">Nenhum registro encontrado. <a href=\"<?= url('/admin/{$route}/create') ?>\">Criar o primeiro?</a></p>\n";
        $content .= "\t\t<?php endif; ?>\n\n";
        $content .= "\t</div>\n\n";
        $content .= "  <script src=\"https://unpkg.com/lucide@latest\"></script>\n";
        $content .= "  <script>\n";
        $content .= "    lucide.createIcons();\n";
        $content .= "  </script>\n\n";
        $content .= "</body>\n";
        $content .= "</html>\n";

        return $content;
    }

    /**
     * Gerar view create.php (formulário)
     */
    private function generateCreateView() {
        $route = $this->names['route'];
        $humanName = $this->names['human'];

        $content = "<!DOCTYPE html>\n";
        $content .= "<html lang=\"pt-BR\">\n\n";
        $content .= "<head>\n";
        $content .= "\t<?php\n";
        $content .= "\t\$loadAdminJs = true;\n";
        $content .= "\trequire_once __DIR__ . '/../../includes/_admin-head.php';\n";
        $content .= "\t?>\n";
        $content .= "\t<title>Novo {$humanName} - <?= ADMIN_NAME ?></title>\n";
        $content .= "</head>\n\n";
        $content .= "<body class=\"m-pagebasebody\">\n\n";
        $content .= "  <?php require_once __DIR__ . '/../../includes/header.php'; ?>\n\n";
        $content .= "\t<div class=\"m-pagebase\">\n\n";
        $content .= "\t\t<div class=\"m-pagebase__header\">\n";
        $content .= "\t\t\t<a href=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__back\">← Voltar</a>\n";
        $content .= "\t\t\t<h1>Novo {$humanName}</h1>\n";
        $content .= "\t\t</div>\n\n";

        // Alerts
        $content .= "\t\t<?php if (isset(\$_SESSION['error'])): ?>\n";
        $content .= "\t\t\t<div class=\"alert alert--error\"><?= htmlspecialchars(\$_SESSION['error']) ?></div>\n";
        $content .= "\t\t\t<?php unset(\$_SESSION['error']); ?>\n";
        $content .= "\t\t<?php endif; ?>\n\n";

        // Form
        $hasUpload = $this->config['has_upload'];
        $enctype = $hasUpload ? ' enctype="multipart/form-data"' : '';

        $content .= "\t\t<form method=\"POST\" action=\"<?= url('/admin/{$route}') ?>\"{$enctype} class=\"m-pagebase__form\">\n";
        $content .= "\t\t\t<input type=\"hidden\" name=\"csrf_token\" value=\"<?= Security::generateCSRF() ?>\">\n\n";

        // Campos dinâmicos
        foreach ($this->config['fields'] as $field) {
            $content .= $this->generateFormField($field, 'create');
        }

        // Campos opcionais
        if ($this->config['has_ordering']) {
            $content .= "\t\t\t<div class=\"m-pagebase__form-group\">\n";
            $content .= "\t\t\t\t<label class=\"m-pagebase__label\">Ordem</label>\n";
            $content .= "\t\t\t\t<input type=\"number\" name=\"order\" class=\"m-pagebase__input\" value=\"0\" />\n";
            $content .= "\t\t\t</div>\n\n";
        }

        if ($this->config['has_status']) {
            $content .= "\t\t\t<div class=\"m-pagebase__form-group\">\n";
            $content .= "\t\t\t\t<label>\n";
            $content .= "\t\t\t\t\t<input type=\"checkbox\" name=\"ativo\" value=\"1\" checked /> Ativo\n";
            $content .= "\t\t\t\t</label>\n";
            $content .= "\t\t\t</div>\n\n";
        }

        // Botões
        $content .= "\t\t\t<div class=\"m-pagebase__actions\">\n";
        $content .= "\t\t\t\t<button type=\"submit\" class=\"m-pagebase__btn\">Criar</button>\n";
        $content .= "\t\t\t\t<a href=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__btn-secondary\">Cancelar</a>\n";
        $content .= "\t\t\t</div>\n\n";
        $content .= "\t\t</form>\n\n";
        $content .= "\t</div>\n\n";
        $content .= "  <script src=\"https://unpkg.com/lucide@latest\"></script>\n";
        $content .= "  <script>\n";
        $content .= "    lucide.createIcons();\n";
        $content .= "  </script>\n\n";
        $content .= "</body>\n";
        $content .= "</html>\n";

        return $content;
    }

    /**
     * Gerar view edit.php (formulário preenchido)
     */
    private function generateEditView() {
        $route = $this->names['route'];
        $humanName = $this->names['human'];

        $content = "<!DOCTYPE html>\n";
        $content .= "<html lang=\"pt-BR\">\n\n";
        $content .= "<head>\n";
        $content .= "\t<?php\n";
        $content .= "\t\$loadAdminJs = true;\n";
        $content .= "\trequire_once __DIR__ . '/../../includes/_admin-head.php';\n";
        $content .= "\t?>\n";
        $content .= "\t<title>Editar {$humanName} - <?= ADMIN_NAME ?></title>\n";
        $content .= "</head>\n\n";
        $content .= "<body class=\"m-pagebasebody\">\n\n";
        $content .= "  <?php require_once __DIR__ . '/../../includes/header.php'; ?>\n\n";
        $content .= "\t<div class=\"m-pagebase\">\n\n";
        $content .= "\t\t<div class=\"m-pagebase__header\">\n";
        $content .= "\t\t\t<a href=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__back\">← Voltar</a>\n";
        $content .= "\t\t\t<h1>Editar {$humanName}</h1>\n";
        $content .= "\t\t</div>\n\n";

        // Alerts
        $content .= "\t\t<?php if (isset(\$_SESSION['error'])): ?>\n";
        $content .= "\t\t\t<div class=\"alert alert--error\"><?= htmlspecialchars(\$_SESSION['error']) ?></div>\n";
        $content .= "\t\t\t<?php unset(\$_SESSION['error']); ?>\n";
        $content .= "\t\t<?php endif; ?>\n\n";

        // Form
        $hasUpload = $this->config['has_upload'];
        $enctype = $hasUpload ? ' enctype="multipart/form-data"' : '';

        $content .= "\t\t<form method=\"POST\" action=\"<?= url('/admin/{$route}/' . htmlspecialchars(\$registro['id'])) ?>\"{$enctype} class=\"m-pagebase__form\">\n";
        $content .= "\t\t\t<input type=\"hidden\" name=\"csrf_token\" value=\"<?= Security::generateCSRF() ?>\">\n\n";

        // Campos dinâmicos
        foreach ($this->config['fields'] as $field) {
            $content .= $this->generateFormField($field, 'edit');
        }

        // Campos opcionais
        if ($this->config['has_ordering']) {
            $content .= "\t\t\t<div class=\"m-pagebase__form-group\">\n";
            $content .= "\t\t\t\t<label class=\"m-pagebase__label\">Ordem</label>\n";
            $content .= "\t\t\t\t<input type=\"number\" name=\"order\" class=\"m-pagebase__input\" value=\"<?= htmlspecialchars(\$registro['order']) ?>\" />\n";
            $content .= "\t\t\t</div>\n\n";
        }

        if ($this->config['has_status']) {
            $content .= "\t\t\t<div class=\"m-pagebase__form-group\">\n";
            $content .= "\t\t\t\t<label>\n";
            $content .= "\t\t\t\t\t<input type=\"checkbox\" name=\"ativo\" value=\"1\" <?= \$registro['ativo'] ? 'checked' : '' ?> /> Ativo\n";
            $content .= "\t\t\t\t</label>\n";
            $content .= "\t\t\t</div>\n\n";
        }

        // Botões
        $content .= "\t\t\t<div class=\"m-pagebase__actions\">\n";
        $content .= "\t\t\t\t<button type=\"submit\" class=\"m-pagebase__btn\">Salvar</button>\n";
        $content .= "\t\t\t\t<a href=\"<?= url('/admin/{$route}') ?>\" class=\"m-pagebase__btn-secondary\">Cancelar</a>\n";
        $content .= "\t\t\t</div>\n\n";
        $content .= "\t\t</form>\n\n";
        $content .= "\t</div>\n\n";
        $content .= "  <script src=\"https://unpkg.com/lucide@latest\"></script>\n";
        $content .= "  <script>\n";
        $content .= "    lucide.createIcons();\n";
        $content .= "  </script>\n\n";
        $content .= "</body>\n";
        $content .= "</html>\n";

        return $content;
    }

    /**
     * Gerar campo de formulário baseado no tipo
     */
    private function generateFormField($field, $mode = 'create') {
        $name = $field['name'];
        $label = ucfirst(str_replace('_', ' ', $name));
        $required = $field['required'] ? ' required' : '';
        $requiredMark = $field['required'] ? ' *' : '';

        $value = $mode === 'edit' ? "<?= htmlspecialchars(\$registro['{$name}'] ?? '') ?>" : '';

        $content = "\t\t\t<div class=\"m-pagebase__form-group\">\n";
        $content .= "\t\t\t\t<label class=\"m-pagebase__label\">{$label}{$requiredMark}</label>\n";

        switch ($field['type']) {
            case 'string':
                $maxLength = !empty($field['max_length']) ? (int)$field['max_length'] : 255;
                $content .= "\t\t\t\t<input type=\"text\" name=\"{$name}\" class=\"m-pagebase__input\" value=\"{$value}\" maxlength=\"{$maxLength}\"{$required} />\n";
                break;

            case 'text':
                $content .= "\t\t\t\t<textarea name=\"{$name}\" class=\"m-pagebase__textarea\" rows=\"5\"{$required}>{$value}</textarea>\n";
                break;

            case 'int':
                $content .= "\t\t\t\t<input type=\"number\" name=\"{$name}\" class=\"m-pagebase__input\" value=\"{$value}\"{$required} />\n";
                break;

            case 'decimal':
                $content .= "\t\t\t\t<input type=\"number\" step=\"0.01\" name=\"{$name}\" class=\"m-pagebase__input\" value=\"{$value}\"{$required} />\n";
                break;

            case 'date':
                $content .= "\t\t\t\t<input type=\"date\" name=\"{$name}\" class=\"m-pagebase__input\" value=\"{$value}\"{$required} />\n";
                break;

            case 'datetime':
                $content .= "\t\t\t\t<input type=\"datetime-local\" name=\"{$name}\" class=\"m-pagebase__input\" value=\"{$value}\"{$required} />\n";
                break;

            case 'upload':
                if ($mode === 'edit') {
                    $content .= "\t\t\t\t<?php if (!empty(\$registro['{$name}'])): ?>\n";
                    $content .= "\t\t\t\t\t<img src=\"<?= url(\$registro['{$name}']) ?>\" style=\"max-width: 200px; margin-bottom: 10px;\" />\n";
                    $content .= "\t\t\t\t\t<p><small>Arquivo atual</small></p>\n";
                    $content .= "\t\t\t\t<?php endif; ?>\n";
                }
                $uploadRequired = $field['required'] && $mode === 'create' ? ' required' : '';
                $content .= "\t\t\t\t<input type=\"file\" name=\"{$name}\" class=\"m-pagebase__input\"{$uploadRequired} />\n";
                if ($mode === 'edit') {
                    $content .= "\t\t\t\t<small>Deixe vazio para manter o arquivo atual</small>\n";
                }
                break;

            case 'fk':
                $fkTable = $field['fk_table'] ?? '';
                $displayField = $field['display_field'] ?? 'name';
                $varName = str_replace('tbl_', '', $fkTable);

                $content .= "\t\t\t\t<select name=\"{$name}\" class=\"m-pagebase__input\"{$required}>\n";
                $content .= "\t\t\t\t\t<option value=\"\">Selecione...</option>\n";
                $content .= "\t\t\t\t\t<?php foreach (\${$varName} as \$opt): ?>\n";

                if ($mode === 'edit') {
                    $content .= "\t\t\t\t\t\t<option value=\"<?= \$opt['id'] ?>\" <?= \$registro['{$name}'] == \$opt['id'] ? 'selected' : '' ?>><?= htmlspecialchars(\$opt['{$displayField}']) ?></option>\n";
                } else {
                    $content .= "\t\t\t\t\t\t<option value=\"<?= \$opt['id'] ?>\"><?= htmlspecialchars(\$opt['{$displayField}']) ?></option>\n";
                }

                $content .= "\t\t\t\t\t<?php endforeach; ?>\n";
                $content .= "\t\t\t\t</select>\n";
                break;
        }

        $content .= "\t\t\t</div>\n\n";

        return $content;
    }

    /**
     * Adicionar rotas ao admin.php
     */
    private function appendRoutes() {
        $route = $this->names['route'];
        $controller = $this->names['controller'];

        $routes = "\n\n";
        $routes .= "// CRUD: {$this->names['human']}\n";
        $routes .= "// Gerado automaticamente em: " . date('Y-m-d H:i:s') . "\n\n";

        $routes .= "Router::get('/admin/{$route}', function() {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->index();\n";
        $routes .= "});\n\n";

        $routes .= "Router::get('/admin/{$route}/create', function() {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->create();\n";
        $routes .= "});\n\n";

        $routes .= "Router::post('/admin/{$route}', function() {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->store();\n";
        $routes .= "});\n\n";

        $routes .= "Router::get('/admin/{$route}/:id/edit', function(\$id) {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->edit(\$id);\n";
        $routes .= "});\n\n";

        $routes .= "Router::post('/admin/{$route}/:id', function(\$id) {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->update(\$id);\n";
        $routes .= "});\n\n";

        $routes .= "Router::post('/admin/{$route}/:id/delete', function(\$id) {\n";
        $routes .= "    \$controller = new {$controller}();\n";
        $routes .= "    \$controller->destroy(\$id);\n";
        $routes .= "});\n";

        // Append ao arquivo
        $adminRoutesPath = ROOT_PATH . 'routes/admin.php';
        file_put_contents($adminRoutesPath, $routes, FILE_APPEND);
    }

    /**
     * Gerar frontend (se solicitado)
     */
    private function generateFrontend() {
        $paths = [];

        // 1. Frontend Controller
        $frontendControllerPath = $this->generateFrontendController();
        $paths[] = $frontendControllerPath;

        // 2. Partial reutilizável baseado no formato
        $format = $this->config['frontend_format'] ?? 'grid';
        $partialPath = $this->generateFrontendPartialByFormat($format);
        $paths[] = $partialPath;

        // 3. SASS baseado no formato
        $sassPath = $this->generateFrontendSassByFormat($format);
        $paths[] = $sassPath;

        return $paths;
    }

    /**
     * Gerar frontend controller
     */
    private function generateFrontendController() {
        $className = 'Frontend' . $this->names['class'] . 'Controller';
        $tableName = $this->names['table'];

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * {$className}\n";
        $content .= " * Controller frontend para {$this->names['human']}\n";
        $content .= " * Gerado automaticamente\n";
        $content .= " */\n\n";
        $content .= "class {$className} {\n\n";
        $content .= "    /**\n";
        $content .= "     * Buscar registros ativos\n";
        $content .= "     */\n";
        $content .= "    public function getActive() {\n";
        $content .= "        \$db = DB::connect();\n\n";

        // SELECT apenas campos necessários
        $selectFields = ['id'];
        foreach ($this->config['fields'] as $field) {
            $selectFields[] = $field['name'];
        }
        if ($this->config['has_slug']) $selectFields[] = 'slug';

        $orderBy = $this->config['has_ordering'] ? '`order` ASC' : 'created_at DESC';

        $content .= "        \$query = \"SELECT " . implode(', ', $selectFields) . "\n";
        $content .= "                 FROM {$tableName}\n";
        if ($this->config['has_status']) {
            $content .= "                 WHERE ativo = 1\n";
        }
        $content .= "                 ORDER BY {$orderBy}\n";
        $content .= "                 LIMIT 10\";\n\n";
        $content .= "        return \$db->query(\$query);\n";
        $content .= "    }\n";
        $content .= "}\n";

        $path = ROOT_PATH . "frontend/controllers/{$className}.php";
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Gerar partial frontend
     */
    private function generateFrontendPartial() {
        $route = $this->names['route'];
        $className = 'Frontend' . $this->names['class'] . 'Controller';
        $humanName = $this->names['human'];

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * Partial: {$humanName}\n";
        $content .= " * Autocontido - pode ser incluído em qualquer página com apenas:\n";
        $content .= " * <?php require_once ROOT_PATH . 'frontend/views/partials/{$route}.php'; ?>\n";
        $content .= " * Gerado automaticamente\n";
        $content .= " */\n\n";
        $content .= "// Carregar controller\n";
        $content .= "require_once ROOT_PATH . 'frontend/controllers/{$className}.php';\n\n";
        $content .= "// Buscar registros ativos\n";
        $content .= "\$controller = new {$className}();\n";
        $content .= "\$items = \$controller->getActive();\n\n";
        $content .= "// Se não houver registros, não renderizar\n";
        $content .= "if (empty(\$items)) {\n";
        $content .= "    return;\n";
        $content .= "}\n";
        $content .= "?>\n\n";
        $content .= "<section class=\"c-{$route}\">\n";
        $content .= "    <div class=\"c-{$route}__container\">\n";
        $content .= "        <h2>{$humanName}s</h2>\n\n";
        $content .= "        <div class=\"c-{$route}__grid\">\n";
        $content .= "            <?php foreach (\$items as \$item): ?>\n";
        $content .= "                <div class=\"c-{$route}__item\">\n";

        // Adicionar campos dinamicamente
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'upload') {
                $content .= "                    <?php if (!empty(\$item['{$field['name']}'])): ?>\n";
                $content .= "                        <img src=\"<?= url(\$item['{$field['name']}']) ?>\" alt=\"<?= htmlspecialchars(\$item['{$this->config['fields'][0]['name']}'] ?? '') ?>\" class=\"c-{$route}__image\" />\n";
                $content .= "                    <?php endif; ?>\n";
            } elseif ($field['type'] === 'string' && strpos($field['name'], 'titulo') !== false) {
                $content .= "                    <h3><?= htmlspecialchars(\$item['{$field['name']}']) ?></h3>\n";
            } elseif ($field['type'] === 'text') {
                $content .= "                    <p><?= htmlspecialchars(\$item['{$field['name']}'] ?? '') ?></p>\n";
            }
        }

        $content .= "                </div>\n";
        $content .= "            <?php endforeach; ?>\n";
        $content .= "        </div>\n";
        $content .= "    </div>\n";
        $content .= "</section>\n\n";
        $content .= "<!-- CSS: /assets/sass/frontend/components/_{$route}.sass -->\n";

        $path = ROOT_PATH . "frontend/views/partials/{$route}.php";
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Gerar SASS frontend
     */
    private function generateFrontendSass() {
        $route = $this->names['route'];

        $content = "// {$this->names['human']} - Frontend\n";
        $content .= "// Gerado automaticamente\n\n";
        $content .= ".c-{$route}\n";
        $content .= "  padding: 60px 0\n\n";
        $content .= "  &__container\n";
        $content .= "    max-width: 1200px\n";
        $content .= "    margin: 0 auto\n";
        $content .= "    padding: 0 20px\n\n";
        $content .= "  &__grid\n";
        $content .= "    display: grid\n";
        $content .= "    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))\n";
        $content .= "    gap: 30px\n";
        $content .= "    margin-top: 40px\n\n";
        $content .= "  &__item\n";
        $content .= "    background: #fff\n";
        $content .= "    border-radius: 8px\n";
        $content .= "    overflow: hidden\n";
        $content .= "    box-shadow: 0 2px 10px rgba(0,0,0,0.1)\n";
        $content .= "    transition: transform 0.3s\n\n";
        $content .= "    &:hover\n";
        $content .= "      transform: translateY(-5px)\n\n";
        $content .= "  &__image\n";
        $content .= "    width: 100%\n";
        $content .= "    height: 200px\n";
        $content .= "    object-fit: cover\n\n";
        $content .= "  h3\n";
        $content .= "    padding: 20px\n";
        $content .= "    margin: 0\n";
        $content .= "    font-size: 1.2rem\n\n";
        $content .= "  p\n";
        $content .= "    padding: 0 20px 20px\n";
        $content .= "    color: #666\n";

        $sassDir = ROOT_PATH . "assets/sass/frontend/components";
        if (!is_dir($sassDir)) {
            mkdir($sassDir, 0755, true);
        }

        $path = $sassDir . "/_{$route}.sass";
        file_put_contents($path, $content);

        // Adicionar @use no _components.sass
        $componentsFile = $sassDir . "/_components.sass";
        if (file_exists($componentsFile)) {
            $componentsContent = file_get_contents($componentsFile);
            $useStatement = "@use '{$route}'";

            // Verificar se já existe
            if (strpos($componentsContent, $useStatement) === false) {
                // Adicionar no final
                $componentsContent .= "\n{$useStatement}";
                file_put_contents($componentsFile, $componentsContent);
            }
        }

        return $path;
    }

    /**
     * Gerar partial baseado no formato escolhido
     */
    private function generateFrontendPartialByFormat($format) {
        switch ($format) {
            case 'carousel':
                return $this->generateFrontendPartialCarousel();
            case 'list':
                return $this->generateFrontendPartialList();
            case 'table':
                return $this->generateFrontendPartialTable();
            case 'grid':
            default:
                return $this->generateFrontendPartial(); // Grid original
        }
    }

    /**
     * Gerar SASS baseado no formato escolhido
     */
    private function generateFrontendSassByFormat($format) {
        switch ($format) {
            case 'carousel':
                return $this->generateFrontendSassCarousel();
            case 'list':
                return $this->generateFrontendSassList();
            case 'table':
                return $this->generateFrontendSassTable();
            case 'grid':
            default:
                return $this->generateFrontendSass(); // Grid original
        }
    }

    /**
     * Gerar partial formato CARROSSEL
     */
    private function generateFrontendPartialCarousel() {
        $route = $this->names['route'];
        $className = 'Frontend' . $this->names['class'] . 'Controller';
        $humanName = $this->names['human'];
        $carouselId = '<?= uniqid(\'' . $route . '-\') ?>';

        $content = "<?php\n/**\n * Partial: {$humanName} - Carrossel\n * Autocontido\n */\n\n";
        $content .= "require_once ROOT_PATH . 'frontend/controllers/{$className}.php';\n";
        $content .= "\$controller = new {$className}();\n";
        $content .= "\$items = \$controller->getActive();\n";
        $content .= "if (empty(\$items)) return;\n";
        $content .= "\$carouselId = uniqid('{$route}-');\n";
        $content .= "?>\n\n";
        $content .= "<section class=\"c-{$route}\" id=\"<?= \$carouselId ?>\">\n";
        $content .= "    <div class=\"c-{$route}__carousel\">\n";
        $content .= "        <div class=\"c-{$route}__slides\">\n";
        $content .= "            <?php foreach (\$items as \$index => \$item): ?>\n";
        $content .= "                <div class=\"c-{$route}__slide <?= \$index === 0 ? 'active' : '' ?>\">\n";

        // Adicionar campos dinamicamente
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'upload') {
                $content .= "                    <?php if (!empty(\$item['{$field['name']}'])): ?>\n";
                $content .= "                        <img src=\"<?= url(\$item['{$field['name']}']) ?>\" class=\"c-{$route}__image\" />\n";
                $content .= "                    <?php endif; ?>\n";
            }
        }

        $content .= "                    <div class=\"c-{$route}__content\">\n";
        foreach ($this->config['fields'] as $field) {
            if ($field['type'] === 'string' && (strpos($field['name'], 'titulo') !== false || strpos($field['name'], 'title') !== false)) {
                $content .= "                        <h1 class=\"c-{$route}__title\"><?= htmlspecialchars(\$item['{$field['name']}'] ?? '') ?></h1>\n";
            } elseif ($field['type'] === 'string' && (strpos($field['name'], 'subtitle') !== false || strpos($field['name'], 'subtitulo') !== false)) {
                $content .= "                        <p class=\"c-{$route}__subtitle\"><?= htmlspecialchars(\$item['{$field['name']}'] ?? '') ?></p>\n";
            } elseif ($field['type'] === 'text') {
                $content .= "                        <p class=\"c-{$route}__description\"><?= htmlspecialchars(\$item['{$field['name']}'] ?? '') ?></p>\n";
            } elseif (strpos($field['name'], 'cta') !== false && $field['type'] === 'string') {
                $ctaLinkField = null;
                foreach ($this->config['fields'] as $f) {
                    if (strpos($f['name'], 'ctalink') !== false || strpos($f['name'], 'link') !== false) {
                        $ctaLinkField = $f['name'];
                        break;
                    }
                }
                if ($ctaLinkField) {
                    $content .= "                        <?php if (!empty(\$item['{$field['name']}']) && !empty(\$item['{$ctaLinkField}'])): ?>\n";
                    $content .= "                            <?php \n";
                    $content .= "                                \$isExternal = strpos(\$item['{$ctaLinkField}'], 'http') === 0 || strpos(\$item['{$ctaLinkField}'], '//') === 0;\n";
                    $content .= "                                \$target = \$isExternal ? ' target=\"_blank\" rel=\"noopener noreferrer\"' : '';\n";
                    $content .= "                            ?>\n";
                    $content .= "                            <a href=\"<?= htmlspecialchars(\$item['{$ctaLinkField}']) ?>\" class=\"c-{$route}__cta\"<?= \$target ?>><?= htmlspecialchars(\$item['{$field['name']}']) ?></a>\n";
                    $content .= "                        <?php endif; ?>\n";
                }
            }
        }
        $content .= "                    </div>\n";
        $content .= "                </div>\n";
        $content .= "            <?php endforeach; ?>\n";
        $content .= "        </div>\n\n";

        // Navegação e indicadores
        $content .= "        <?php if (count(\$items) > 1): ?>\n";
        $content .= "            <button class=\"c-{$route}__nav c-{$route}__nav--prev\"><i data-lucide=\"chevron-left\"></i></button>\n";
        $content .= "            <button class=\"c-{$route}__nav c-{$route}__nav--next\"><i data-lucide=\"chevron-right\"></i></button>\n";
        $content .= "            <div class=\"c-{$route}__indicators\">\n";
        $content .= "                <?php foreach (\$items as \$index => \$item): ?>\n";
        $content .= "                    <button class=\"c-{$route}__indicator <?= \$index === 0 ? 'active' : '' ?>\"></button>\n";
        $content .= "                <?php endforeach; ?>\n";
        $content .= "            </div>\n";
        $content .= "        <?php endif; ?>\n";
        $content .= "    </div>\n";
        $content .= "</section>\n\n";

        // JavaScript do carrossel (inline)
        $content .= $this->generateCarouselJS($route);

        $path = ROOT_PATH . "frontend/views/partials/{$route}.php";
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Gerar JavaScript do carrossel
     */
    private function generateCarouselJS($route) {
        return "<script>\n(function() {\n    const carousel = document.querySelector('.c-{$route}');\n    if (!carousel) return;\n\n    const slides = carousel.querySelectorAll('.c-{$route}__slide');\n    const indicators = carousel.querySelectorAll('.c-{$route}__indicator');\n    const prevBtn = carousel.querySelector('.c-{$route}__nav--prev');\n    const nextBtn = carousel.querySelector('.c-{$route}__nav--next');\n    let currentSlide = 0;\n    const totalSlides = slides.length;\n    let autoplayInterval;\n\n    function showSlide(index) {\n        if (index >= totalSlides) index = 0;\n        if (index < 0) index = totalSlides - 1;\n        currentSlide = index;\n        slides.forEach((s, i) => s.classList.toggle('active', i === currentSlide));\n        indicators.forEach((ind, i) => ind.classList.toggle('active', i === currentSlide));\n    }\n\n    function nextSlide() { showSlide(currentSlide + 1); }\n    function prevSlide() { showSlide(currentSlide - 1); }\n\n    function startAutoplay() {\n        stopAutoplay();\n        autoplayInterval = setInterval(nextSlide, 5000);\n    }\n\n    function stopAutoplay() {\n        if (autoplayInterval) clearInterval(autoplayInterval);\n    }\n\n    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoplay(); startAutoplay(); });\n    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoplay(); startAutoplay(); });\n    indicators.forEach((ind, i) => ind.addEventListener('click', () => { showSlide(i); stopAutoplay(); startAutoplay(); }));\n\n    carousel.addEventListener('mouseenter', stopAutoplay);\n    carousel.addEventListener('mouseleave', startAutoplay);\n\n    let touchStartX = 0, touchEndX = 0;\n    carousel.addEventListener('touchstart', e => touchStartX = e.changedTouches[0].screenX);\n    carousel.addEventListener('touchend', e => {\n        touchEndX = e.changedTouches[0].screenX;\n        if (touchEndX < touchStartX - 50) nextSlide();\n        if (touchEndX > touchStartX + 50) prevSlide();\n    });\n\n    if (totalSlides > 1) startAutoplay();\n})();\n</script>\n";
    }

    /**
     * Gerar SASS para CARROSSEL
     */
    private function generateFrontendSassCarousel() {
        $route = $this->names['route'];

        $content = "// {$this->names['human']} - Carrossel\n\n";
        $content .= ".c-{$route}\n";
        $content .= "  position: relative\n";
        $content .= "  width: 100%\n";
        $content .= "  height: 100vh\n";
        $content .= "  overflow: hidden\n\n";
        $content .= "  &__carousel\n";
        $content .= "    position: relative\n";
        $content .= "    width: 100%\n";
        $content .= "    height: 100%\n\n";
        $content .= "  &__slides\n";
        $content .= "    position: relative\n";
        $content .= "    width: 100%\n";
        $content .= "    height: 100%\n\n";
        $content .= "  &__slide\n";
        $content .= "    position: absolute\n";
        $content .= "    top: 0\n";
        $content .= "    left: 0\n";
        $content .= "    width: 100%\n";
        $content .= "    height: 100%\n";
        $content .= "    opacity: 0\n";
        $content .= "    transition: opacity 0.6s ease-in-out\n";
        $content .= "    pointer-events: none\n\n";
        $content .= "    &.active\n";
        $content .= "      opacity: 1\n";
        $content .= "      pointer-events: auto\n\n";
        $content .= "  &__image\n";
        $content .= "    width: 100%\n";
        $content .= "    height: 100%\n";
        $content .= "    object-fit: cover\n\n";
        $content .= "  &__content\n";
        $content .= "    position: absolute\n";
        $content .= "    bottom: 0\n";
        $content .= "    left: 0\n";
        $content .= "    right: 0\n";
        $content .= "    padding: 60px 40px\n";
        $content .= "    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent)\n";
        $content .= "    color: #fff\n";
        $content .= "    text-align: center\n\n";
        $content .= "  &__title\n";
        $content .= "    font-size: 3rem\n";
        $content .= "    font-weight: 700\n";
        $content .= "    margin: 0 0 15px 0\n";
        $content .= "    text-shadow: 2px 2px 4px rgba(0,0,0,0.5)\n\n";
        $content .= "  &__subtitle\n";
        $content .= "    font-size: 1.5rem\n";
        $content .= "    margin: 0 0 25px 0\n";
        $content .= "    opacity: 0.9\n\n";
        $content .= "  &__cta\n";
        $content .= "    display: inline-block\n";
        $content .= "    padding: 15px 40px\n";
        $content .= "    background: #fff\n";
        $content .= "    color: #333\n";
        $content .= "    text-decoration: none\n";
        $content .= "    font-weight: 600\n";
        $content .= "    border-radius: 50px\n";
        $content .= "    transition: all 0.3s\n\n";
        $content .= "    &:hover\n";
        $content .= "      transform: translateY(-2px)\n\n";
        $content .= "  &__nav\n";
        $content .= "    position: absolute\n";
        $content .= "    top: 50%\n";
        $content .= "    transform: translateY(-50%)\n";
        $content .= "    background: rgba(255,255,255,0.9)\n";
        $content .= "    border: none\n";
        $content .= "    width: 50px\n";
        $content .= "    height: 50px\n";
        $content .= "    border-radius: 50%\n";
        $content .= "    cursor: pointer\n";
        $content .= "    z-index: 10\n\n";
        $content .= "    &--prev\n";
        $content .= "      left: 20px\n\n";
        $content .= "    &--next\n";
        $content .= "      right: 20px\n\n";
        $content .= "  &__indicators\n";
        $content .= "    position: absolute\n";
        $content .= "    bottom: 30px\n";
        $content .= "    left: 50%\n";
        $content .= "    transform: translateX(-50%)\n";
        $content .= "    display: flex\n";
        $content .= "    gap: 10px\n";
        $content .= "    z-index: 10\n\n";
        $content .= "  &__indicator\n";
        $content .= "    width: 12px\n";
        $content .= "    height: 12px\n";
        $content .= "    border-radius: 50%\n";
        $content .= "    border: 2px solid #fff\n";
        $content .= "    background: transparent\n";
        $content .= "    cursor: pointer\n";
        $content .= "    padding: 0\n\n";
        $content .= "    &.active\n";
        $content .= "      background: #fff\n";
        $content .= "      width: 30px\n";
        $content .= "      border-radius: 6px\n";

        $sassDir = ROOT_PATH . "assets/sass/frontend/components";
        $path = $sassDir . "/_{$route}.sass";
        file_put_contents($path, $content);

        // Auto-import
        $componentsFile = $sassDir . "/_components.sass";
        if (file_exists($componentsFile)) {
            $componentsContent = file_get_contents($componentsFile);
            $useStatement = "@use '{$route}'";
            if (strpos($componentsContent, $useStatement) === false) {
                $componentsContent .= "\n{$useStatement}";
                file_put_contents($componentsFile, $componentsContent);
            }
        }

        return $path;
    }

    /**
     * Gerar partial formato LISTA
     */
    private function generateFrontendPartialList() {
        // Implementação similar mas mais simples
        return $this->generateFrontendPartial(); // Por enquanto usa o grid
    }

    /**
     * Gerar SASS para LISTA
     */
    private function generateFrontendSassList() {
        return $this->generateFrontendSass(); // Por enquanto usa o grid
    }

    /**
     * Gerar partial formato TABELA
     */
    private function generateFrontendPartialTable() {
        // Implementação com <table>
        return $this->generateFrontendPartial(); // Por enquanto usa o grid
    }

    /**
     * Gerar SASS para TABELA
     */
    private function generateFrontendSassTable() {
        return $this->generateFrontendSass(); // Por enquanto usa o grid
    }

    /**
     * Normalizar MIME types
     * Aceita extensões (jpg, png) e converte para MIME types (image/jpeg, image/png)
     */
    private function normalizeMimeTypes($mimes) {
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'json' => 'application/json',
            'xml' => 'application/xml'
        ];

        $normalized = [];
        foreach ($mimes as $mime) {
            $mime = trim($mime);

            // Se já é MIME type (contém /), mantém
            if (strpos($mime, '/') !== false) {
                $normalized[] = $mime;
            } else {
                // Converte extensão para MIME type
                $ext = strtolower($mime);
                $normalized[] = $map[$ext] ?? 'application/octet-stream';
            }
        }

        return array_unique($normalized);
    }
}
