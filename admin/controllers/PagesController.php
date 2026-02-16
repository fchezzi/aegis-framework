<?php
/**
 * Pages Controller
 * Gerenciamento unificado de páginas (arquivos + banco de dados + permissões)
 */

class PagesController {

    /**
     * Listar todas as páginas
     */
    public function index() {
        Auth::require();
        $user = Auth::user();

        $db = DB::connect();

        // Paginação
        $perPage = 15;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $currentPage = max(1, $currentPage); // Garantir página mínima = 1
        $offset = ($currentPage - 1) * $perPage;

        // Filtros
        $search = isset($_GET['search']) ? Security::sanitize($_GET['search']) : '';
        $filterScope = isset($_GET['scope']) ? Security::sanitize($_GET['scope']) : '';
        $filterType = isset($_GET['type']) ? Security::sanitize($_GET['type']) : '';

        // Ordenação
        $allowedSortColumns = ['title', 'type', 'slug', 'scope', 'ativo', 'created_at'];
        $sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns) ? $_GET['sort'] : 'title';
        $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

        // Construir WHERE clauses
        $whereConditions = [];
        $params = [];

        // Condição base: excluir virtuais
        if (DB_TYPE === 'supabase') {
            $whereConditions[] = "(is_virtual IS NULL OR is_virtual = false)";
        } else {
            $whereConditions[] = "is_virtual = 0";
        }

        // Filtro de busca por nome
        if (!empty($search)) {
            $whereConditions[] = "title LIKE ?";
            $params[] = "%{$search}%";
        }

        // Filtro por scope
        if (!empty($filterScope) && in_array($filterScope, ['admin', 'members', 'frontend'])) {
            $whereConditions[] = "scope = ?";
            $params[] = $filterScope;
        }

        // Filtro por type
        if (!empty($filterType) && in_array($filterType, ['core', 'custom'])) {
            $whereConditions[] = "type = ?";
            $params[] = $filterType;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Contar total de páginas com filtros
        $countQuery = "SELECT COUNT(*) as total FROM pages WHERE {$whereClause}";
        $countResult = $db->query($countQuery, $params);
        $totalPages = $countResult[0]['total'] ?? 0;

        // Calcular total de páginas
        $totalPagesCount = ceil($totalPages / $perPage);

        // Buscar páginas do banco de dados com LIMIT, OFFSET, filtros e ordenação
        $selectQuery = "SELECT * FROM pages WHERE {$whereClause} ORDER BY {$sortColumn} {$sortOrder} LIMIT ? OFFSET ?";
        $selectParams = array_merge($params, [$perPage, $offset]);
        $pages = $db->query($selectQuery, $selectParams);

        // Verificar se arquivos existem
        $pagesDir = ROOT_PATH . 'frontend/pages/';
        foreach ($pages as &$page) {
            $filePath = $pagesDir . $page['slug'] . '.php';
            $page['file_exists'] = file_exists($filePath);
        }
        unset($page); // Limpar referência

        // Dados do usuário para o header
        $user = Auth::user();

        // Dados de paginação
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPagesCount,
            'perPage' => $perPage,
            'totalItems' => $totalPages
        ];

        require_once ROOT_PATH . 'admin/views/pages/index.php';
    }

    /**
     * Formulário criar nova página
     */
    public function create() {
        Auth::require();
        $user = Auth::user();

        $db = DB::connect();

        // Buscar templates disponíveis
        $templates = $this->getAvailableTemplates();

        // Buscar grupos (para permissões)
        $groups = [];
        if (Core::membersEnabled()) {
            $groups = $db->select('groups', [], 'name ASC');
        }

        require_once ROOT_PATH . 'admin/views/pages/create.php';
    }

    /**
     * Salvar nova página
     */
    public function store() {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token']);

            $title = Security::sanitize($_POST['title'] ?? '');
            $description = Security::sanitize($_POST['description'] ?? '');
            $template = Security::sanitize($_POST['template'] ?? 'basic');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            // Se sistema SEM members → sempre público
            // Se sistema COM members → respeita checkbox do admin
            $isPublic = Core::membersEnabled()
                ? (isset($_POST['is_public']) ? 1 : 0)
                : 1;

            // Tipo da página: core ou custom (default: custom)
            $type = Security::sanitize($_POST['type'] ?? 'custom');
            if (!in_array($type, ['core', 'custom'])) {
                $type = 'custom';
            }

            // Scope da página: admin, members ou frontend (default: frontend)
            $scope = Security::sanitize($_POST['scope'] ?? 'frontend');
            if (!in_array($scope, ['admin', 'members', 'frontend'])) {
                $scope = 'frontend';
            }

            if (empty($title)) {
                throw new Exception('Título da página é obrigatório');
            }

            // Gerar slug
            $slug = $this->generateSlug($title);

            // Validar se slug não é reservado pelo sistema
            $this->validateReservedSlugs($slug);

            // Verificar se slug já existe no banco
            $db = DB::connect();
            $existing = $db->select('pages', ['slug' => $slug]);
            if (!empty($existing)) {
                throw new Exception('Já existe uma página com esse slug: ' . $slug);
            }

            // Verificar se slug conflita com categorias do blog (se blog instalado)
            $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');
            if (in_array('blog', $installedModules)) {
                $blogCategories = $db->query("SELECT id FROM tbl_blog_categorias WHERE slug = ?", [$slug]);
                if (!empty($blogCategories)) {
                    throw new Exception('Já existe uma categoria do blog com esse slug: ' . $slug);
                }
            }

            // Verificar se arquivo já existe
            $filePath = ROOT_PATH . 'frontend/pages/' . $slug . '.php';
            if (file_exists($filePath)) {
                throw new Exception('Já existe um arquivo com esse slug');
            }

            // Upload de imagem OG (se houver)
            $ogImagePath = '';
            if (isset($_FILES['seo_og_image']) && $_FILES['seo_og_image']['error'] === UPLOAD_ERR_OK) {
                $ogImagePath = $this->uploadOGImage($_FILES['seo_og_image']);
            }

            // 1. Criar registro no banco (PRIMEIRO)
            $pageData = [
                'title' => $title,
                'slug' => $slug,
                'type' => $type,
                'scope' => $scope,
                'description' => $description,
                'ativo' => $ativo,
                'is_public' => $isPublic,

                // Campos SEO
                'seo_title' => Security::sanitize($_POST['seo_title'] ?? ''),
                'seo_description' => Security::sanitize($_POST['seo_description'] ?? ''),
                'seo_robots' => Security::sanitize($_POST['seo_robots'] ?? 'index,follow'),
                'seo_canonical_url' => Security::sanitize($_POST['seo_canonical_url'] ?? ''),
                'seo_og_type' => Security::sanitize($_POST['seo_og_type'] ?? 'website'),
                'seo_og_title' => Security::sanitize($_POST['seo_og_title'] ?? ''),
                'seo_og_description' => Security::sanitize($_POST['seo_og_description'] ?? ''),
                'seo_og_image' => $ogImagePath,
                'seo_twitter_card' => Security::sanitize($_POST['seo_twitter_card'] ?? 'summary'),
                'seo_twitter_title' => Security::sanitize($_POST['seo_twitter_title'] ?? ''),
                'seo_twitter_description' => Security::sanitize($_POST['seo_twitter_description'] ?? '')
            ];

            // MySQL precisa de id + created_at explícitos
            if (DB_TYPE === 'mysql') {
                $pageData['id'] = Security::generateUUID();
                $pageData['created_at'] = date('Y-m-d H:i:s');
            }

            $pageId = $db->insert('pages', $pageData);

            // 2. Criar arquivo da página
            $this->createPageFile($slug, $title, $template);

            // 3. Atribuir permissões (se sistema tem members)
            if (Core::membersEnabled()) {
                $groupIds = $_POST['group_ids'] ?? [];

                foreach ($groupIds as $groupId) {
                    if (!empty($groupId)) {
                        Permission::grantGroup($groupId, DB_TYPE === 'mysql' ? $pageData['id'] : $pageId);
                    }
                }
            }

            $_SESSION['success'] = 'Página criada com sucesso!';
            Core::redirect(url('/admin/pages'));

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect(url('/admin/pages/create'));
        }
    }

    /**
     * Formulário editar página
     */
    public function edit($id) {
        Auth::require();
        $user = Auth::user();

        $db = DB::connect();
        $pages = $db->select('pages', ['id' => $id]);

        if (empty($pages)) {
            $_SESSION['error'] = 'Página não encontrada';
            Core::redirect(url('/admin/pages'));
        }

        $page = $pages[0];

        // Buscar templates
        $templates = $this->getAvailableTemplates();

        // Buscar grupos
        $groups = [];
        if (Core::membersEnabled()) {
            $groups = $db->select('groups', [], 'name ASC');
        }

        // Buscar permissões atuais
        $pageGroups = [];
        if (Core::membersEnabled()) {
            $perms = $db->select('page_permissions', ['page_id' => $id]);
            $pageGroups = array_column($perms, 'group_id');
        }

        require_once ROOT_PATH . 'admin/views/pages/edit.php';
    }

    /**
     * Atualizar página
     */
    public function update($id) {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token']);

            $title = Security::sanitize($_POST['title'] ?? '');
            $description = Security::sanitize($_POST['description'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            // Se sistema SEM members → sempre público
            // Se sistema COM members → respeita checkbox do admin
            $isPublic = Core::membersEnabled()
                ? (isset($_POST['is_public']) ? 1 : 0)
                : 1;

            // Tipo da página: core ou custom (default: custom)
            $type = Security::sanitize($_POST['type'] ?? 'custom');
            if (!in_array($type, ['core', 'custom'])) {
                $type = 'custom';
            }

            // Scope da página: admin, members ou frontend (default: frontend)
            $scope = Security::sanitize($_POST['scope'] ?? 'frontend');
            if (!in_array($scope, ['admin', 'members', 'frontend'])) {
                $scope = 'frontend';
            }

            if (empty($title)) {
                throw new Exception('Título da página é obrigatório');
            }

            $db = DB::connect();

            // Buscar página atual
            $pages = $db->select('pages', ['id' => $id]);
            if (empty($pages)) {
                throw new Exception('Página não encontrada');
            }

            $page = $pages[0];
            $oldSlug = $page['slug'];

            // Gerar novo slug
            $newSlug = $this->generateSlug($title);

            // Se slug mudou, verificar se novo slug já existe
            if ($oldSlug !== $newSlug) {
                // Validar se slug não é reservado pelo sistema
                $this->validateReservedSlugs($newSlug);

                $existing = $db->select('pages', ['slug' => $newSlug]);
                if (!empty($existing) && $existing[0]['id'] !== $id) {
                    throw new Exception('Já existe uma página com esse slug: ' . $newSlug);
                }

                // Verificar se slug conflita com categorias do blog (se blog instalado)
                $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');
                if (in_array('blog', $installedModules)) {
                    $blogCategories = $db->query("SELECT id FROM tbl_blog_categorias WHERE slug = ?", [$newSlug]);
                    if (!empty($blogCategories)) {
                        throw new Exception('Já existe uma categoria do blog com esse slug: ' . $newSlug);
                    }
                }

                // Renomear arquivo
                $oldFilePath = ROOT_PATH . 'frontend/pages/' . $oldSlug . '.php';
                $newFilePath = ROOT_PATH . 'frontend/pages/' . $newSlug . '.php';

                if (file_exists($oldFilePath)) {
                    if (file_exists($newFilePath)) {
                        throw new Exception('Já existe um arquivo com esse slug');
                    }
                    rename($oldFilePath, $newFilePath);
                }

                // Atualizar itens de menu
                $this->updateMenuItems($oldSlug, $newSlug);
            }

            // Upload de imagem OG (se houver novo upload)
            $ogImagePath = $page['seo_og_image'] ?? ''; // Manter imagem atual
            if (isset($_FILES['seo_og_image']) && $_FILES['seo_og_image']['error'] === UPLOAD_ERR_OK) {
                // Deletar imagem antiga se existir
                if (!empty($page['seo_og_image']) && file_exists(ROOT_PATH . $page['seo_og_image'])) {
                    unlink(ROOT_PATH . $page['seo_og_image']);
                }
                $ogImagePath = $this->uploadOGImage($_FILES['seo_og_image']);
            }

            // Atualizar registro no banco
            $updateData = [
                'title' => $title,
                'slug' => $newSlug,
                'type' => $type,
                'scope' => $scope,
                'description' => $description,
                'ativo' => $ativo,
                'is_public' => $isPublic,
                'updated_at' => date('Y-m-d H:i:s'),

                // Campos SEO
                'seo_title' => Security::sanitize($_POST['seo_title'] ?? ''),
                'seo_description' => Security::sanitize($_POST['seo_description'] ?? ''),
                'seo_robots' => Security::sanitize($_POST['seo_robots'] ?? 'index,follow'),
                'seo_canonical_url' => Security::sanitize($_POST['seo_canonical_url'] ?? ''),
                'seo_og_type' => Security::sanitize($_POST['seo_og_type'] ?? 'website'),
                'seo_og_title' => Security::sanitize($_POST['seo_og_title'] ?? ''),
                'seo_og_description' => Security::sanitize($_POST['seo_og_description'] ?? ''),
                'seo_og_image' => $ogImagePath,
                'seo_twitter_card' => Security::sanitize($_POST['seo_twitter_card'] ?? 'summary'),
                'seo_twitter_title' => Security::sanitize($_POST['seo_twitter_title'] ?? ''),
                'seo_twitter_description' => Security::sanitize($_POST['seo_twitter_description'] ?? '')
            ];

            $db->update('pages', $updateData, ['id' => $id]);

            // Atualizar título no arquivo
            $this->updatePageTitle($newSlug, $title);

            // Atualizar permissões (se sistema tem members)
            if (Core::membersEnabled()) {
                // Remover permissões antigas
                $db->delete('page_permissions', ['page_id' => $id]);

                // Adicionar novas permissões
                $groupIds = $_POST['group_ids'] ?? [];
                foreach ($groupIds as $groupId) {
                    if (!empty($groupId)) {
                        Permission::grantGroup($groupId, $id);
                    }
                }
            }

            $_SESSION['success'] = 'Página atualizada com sucesso!';
            Core::redirect(url('/admin/pages'));

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect(url('/admin/pages/' . $id . '/edit'));
        }
    }

    /**
     * Deletar página
     */
    public function destroy($id) {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token']);

            $db = DB::connect();

            // Buscar página
            $pages = $db->select('pages', ['id' => $id]);
            if (empty($pages)) {
                throw new Exception('Página não encontrada');
            }

            $page = $pages[0];
            $slug = $page['slug'];

            // 🛡️ PROTEÇÃO: Não permitir deleção de páginas core
            if (isset($page['type']) && $page['type'] === 'core') {
                throw new Exception('Páginas core do AEGIS não podem ser deletadas. Você pode apenas editá-las.');
            }

            // Deletar arquivo físico
            $filePath = ROOT_PATH . 'frontend/pages/' . $slug . '.php';
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Remover itens de menu associados
            $this->removeMenuItems($slug);

            // Deletar registro (CASCADE vai deletar permissões automaticamente)
            $db->delete('pages', ['id' => $id]);

            $_SESSION['success'] = 'Página deletada com sucesso!';
            Core::redirect(url('/admin/pages'));

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect(url('/admin/pages'));
        }
    }

    /**
     * Gerar slug a partir do título
     */
    private function generateSlug($title) {
        // Converter para minúsculas
        $slug = mb_strtolower($title, 'UTF-8');

        // Substituir acentos e caracteres especiais
        $acentos = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe'
        ];
        $slug = strtr($slug, $acentos);

        // Remover tudo que não for letra, número ou espaço
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

        // Substituir espaços e múltiplos hífens por um único hífen
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        // Remover hífens do início e fim
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Criar arquivo da página usando template
     */
    private function createPageFile($slug, $title, $templateName = 'basic') {
        $templateFile = ROOT_PATH . 'frontend/templates/' . $templateName . '.php';

        // Se template não existir, usar basic
        if (!file_exists($templateFile)) {
            $templateFile = ROOT_PATH . 'frontend/templates/basic.php';
        }

        // Ler template
        $template = file_get_contents($templateFile);

        // Substituir placeholders
        $content = str_replace(['{NAME}', '{SLUG}'], [$title, $slug], $template);

        // Salvar arquivo da página
        file_put_contents(ROOT_PATH . 'frontend/pages/' . $slug . '.php', $content);
    }

    /**
     * Obter título da página do arquivo
     */
    private function getPageTitle($filePath, $defaultSlug) {
        $content = file_get_contents($filePath);

        // Tentar pegar do $pageTitle
        if (preg_match('/\$pageTitle\s*=\s*[\'"](.+?)[\'"]/', $content, $matches)) {
            return $matches[1];
        }

        // Tentar pegar do comentário
        if (preg_match('/\*\s*Página:\s*(.+?)\n/', $content, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: capitalizar slug
        return ucwords(str_replace('-', ' ', $defaultSlug));
    }

    /**
     * Atualizar título no arquivo
     */
    private function updatePageTitle($slug, $newTitle) {
        $filePath = ROOT_PATH . 'frontend/pages/' . $slug . '.php';

        if (!file_exists($filePath)) {
            return; // Arquivo não existe
        }

        $content = file_get_contents($filePath);

        // Atualizar comentário
        $content = preg_replace(
            '/\*\s*Página:\s*.+?\n/',
            "* Página: {$newTitle}\n",
            $content
        );

        // Atualizar $pageTitle
        $content = preg_replace(
            '/\$pageTitle\s*=\s*[\'"].+?[\'"]/',
            "\$pageTitle = '{$newTitle}'",
            $content
        );

        // Atualizar <h1>
        $content = preg_replace(
            '/<h1>.+?<\/h1>/',
            "<h1>{$newTitle}</h1>",
            $content
        );

        file_put_contents($filePath, $content);
    }

    /**
     * Obter templates disponíveis
     */
    private function getAvailableTemplates() {
        $templatesDir = ROOT_PATH . 'frontend/templates/';
        $templates = [];

        if (is_dir($templatesDir)) {
            $files = scandir($templatesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;

                $name = pathinfo($file, PATHINFO_FILENAME);
                $label = ucfirst($name);

                // Ler descrição do template do comentário
                $content = file_get_contents($templatesDir . $file);
                if (preg_match('/\*\s*Template:\s*(.+?)\n/', $content, $matches)) {
                    $label = trim($matches[1]);
                }

                $templates[] = [
                    'name' => $name,
                    'label' => $label
                ];
            }
        }

        return $templates;
    }

    /**
     * Remover itens de menu associados a uma página deletada
     */
    private function removeMenuItems($slug) {
        try {
            $db = DB::connect();

            // Buscar itens de menu com este page_slug
            $menuItems = $db->select('menu_items', [
                'type' => 'page',
                'page_slug' => $slug
            ]);

            // Deletar cada item encontrado
            foreach ($menuItems as $item) {
                $db->delete('menu_items', ['id' => $item['id']]);
            }

        } catch (Exception $e) {
            // Erro silencioso - não bloquear deleção da página
            error_log("Erro ao remover itens de menu: " . $e->getMessage());
        }
    }

    /**
     * Atualizar itens de menu quando slug da página muda
     */
    private function updateMenuItems($oldSlug, $newSlug) {
        try {
            $db = DB::connect();

            // Buscar itens de menu com o slug antigo
            $menuItems = $db->select('menu_items', [
                'type' => 'page',
                'page_slug' => $oldSlug
            ]);

            // Atualizar cada item encontrado
            foreach ($menuItems as $item) {
                $db->update('menu_items', [
                    'page_slug' => $newSlug
                ], ['id' => $item['id']]);
            }

        } catch (Exception $e) {
            // Erro silencioso - não bloquear atualização da página
            error_log("Erro ao atualizar itens de menu: " . $e->getMessage());
        }
    }

    /**
     * Validar se slug não conflita com rotas reservadas do sistema
     *
     * @param string $slug Slug a ser validado
     * @throws Exception Se slug for reservado
     */
    private function validateReservedSlugs($slug) {
        // Slugs reservados do sistema core
        $reservedSlugs = [
            'admin',
            'login',
            'logout',
            'home',
            'api',
            'setup'
        ];

        // Adicionar módulos instalados à lista de reservados
        if (defined('INSTALLED_MODULES') && !empty(INSTALLED_MODULES)) {
            $installedModules = explode(',', INSTALLED_MODULES);
            $installedModules = array_map('trim', $installedModules);
            $reservedSlugs = array_merge($reservedSlugs, $installedModules);
        }

        // Verificar se slug está na lista de reservados
        if (in_array($slug, $reservedSlugs)) {
            throw new Exception("O slug '{$slug}' é reservado pelo sistema e não pode ser usado para páginas. Escolha outro nome.");
        }
    }

    /**
     * Upload de imagem Open Graph com validação
     *
     * @param array $file $_FILES['seo_og_image']
     * @return string Caminho relativo da imagem salva
     * @throws Exception
     */
    private function uploadOGImage($file) {
        // Validar se é imagem
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = $file['type'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Formato inválido. Use JPG, PNG ou WebP.');
        }

        // Validar tamanho do arquivo (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new Exception('Imagem muito grande. Máximo: 2MB.');
        }

        // Obter dimensões da imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Arquivo não é uma imagem válida.');
        }

        list($width, $height) = $imageInfo;

        // Validar proporção (ideal: 1200x630 = 1.9:1)
        $idealRatio = 1200 / 630; // ~1.9
        $actualRatio = $width / $height;
        $ratioDiff = abs($actualRatio - $idealRatio);

        // Aviso se proporção estiver muito diferente (tolerância: 0.3)
        if ($ratioDiff > 0.3) {
            // Não bloquear, apenas avisar via sessão
            $_SESSION['warning'] = "Imagem OG com proporção não ideal. Recomendado: 1200x630px (proporção 1.9:1). Sua imagem: {$width}x{$height}px.";
        }

        // Validar dimensões mínimas (pelo menos 600x315)
        if ($width < 600 || $height < 315) {
            throw new Exception("Imagem muito pequena. Mínimo: 600x315px. Sua imagem: {$width}x{$height}px.");
        }

        // Criar diretório se não existir
        $uploadDir = ROOT_PATH . 'storage/uploads/seo/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'og-' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Erro ao salvar imagem.');
        }

        // Retornar caminho relativo para storage
        return 'storage/uploads/seo/' . $filename;
    }
}
