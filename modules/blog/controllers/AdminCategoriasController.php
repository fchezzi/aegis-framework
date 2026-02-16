<?php
/**
 * AEGIS Framework - Blog Module
 * Admin Categorias Controller
 * Version: 1.0.0
 */

class AdminCategoriasController {

    /**
     * Listagem de categorias
     */
    public function index() {
        Auth::require();

        $db = DB::connect();

        // Paginação
        $perPage = 15;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $currentPage = max(1, $currentPage);
        $offset = ($currentPage - 1) * $perPage;

        // Filtros
        $search = isset($_GET['search']) ? Security::sanitize($_GET['search']) : '';

        // Ordenação
        $allowedSortColumns = ['nome', 'slug', 'ativo', 'ordem'];
        $sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns) ? $_GET['sort'] : 'ordem';
        $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

        // Construir WHERE clauses
        $whereConditions = ['1=1'];
        $params = [];

        // Filtro de busca por nome
        if (!empty($search)) {
            $whereConditions[] = "c.nome LIKE ?";
            $params[] = "%{$search}%";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Contar total de categorias
        $countQuery = "SELECT COUNT(*) as total FROM tbl_blog_categorias c WHERE {$whereClause}";
        $countResult = $db->query($countQuery, $params);
        $totalCategorias = $countResult[0]['total'] ?? 0;

        // Calcular total de páginas
        $totalPagesCount = ceil($totalCategorias / $perPage);

        // Buscar categorias com paginação e ordenação
        $selectQuery = "
            SELECT
                c.*,
                COUNT(p.id) AS total_posts
            FROM tbl_blog_categorias c
            LEFT JOIN tbl_blog_posts p ON p.categoria_id = c.id
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.{$sortColumn} {$sortOrder}
            LIMIT ? OFFSET ?
        ";
        $selectParams = array_merge($params, [$perPage, $offset]);
        $categorias = $db->query($selectQuery, $selectParams);

        // Dados de paginação
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPagesCount,
            'perPage' => $perPage,
            'totalItems' => $totalCategorias
        ];

        require __DIR__ . '/../views/admin/categorias/index.php';
    }

    /**
     * Formulário de criação
     */
    public function create() {
        Auth::require();

        require __DIR__ . '/../views/admin/categorias/create.php';
    }

    /**
     * Salvar nova categoria
     */
    public function store() {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Validações
        $errors = [];

        $nome = Security::sanitize($_POST['nome'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $ordem = (int)($_POST['ordem'] ?? 0);

        // Validar campos obrigatórios
        if (empty($nome)) $errors[] = 'Nome é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';

        // Validar tamanhos
        if (strlen($nome) > 100) $errors[] = 'Nome muito longo (máx 100 caracteres)';
        if (strlen($slug) > 100) $errors[] = 'Slug muito longo (máx 100 caracteres)';

        // Validar slug único (categorias)
        $slugExists = $db->query("SELECT id FROM tbl_blog_categorias WHERE slug = ?", [$slug]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe em outra categoria';
        }

        // Validar slug não conflita com páginas do AEGIS
        $pageExists = $db->query("SELECT id FROM pages WHERE slug = ?", [$slug]);
        if (!empty($pageExists)) {
            $errors[] = 'Slug já existe como página do sistema';
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/blog/categorias/create'));
            exit;
        }

        // Inserir categoria
        $db->insert('tbl_blog_categorias', [
            'id' => Security::generateUUID(),
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'ordem' => $ordem
        ]);

        // Limpar cache
        SimpleCache::delete('blog_categorias');

        $_SESSION['success'] = 'Categoria criada com sucesso!';
        header('Location: ' . url('/admin/blog/categorias'));
        exit;
    }

    /**
     * Formulário de edição
     */
    public function edit($id) {
        Auth::require();

        $db = DB::connect();

        // Buscar categoria
        $categoria = $db->query("SELECT * FROM tbl_blog_categorias WHERE id = ?", [$id]);
        if (empty($categoria)) {
            $_SESSION['error'] = 'Categoria não encontrada';
            header('Location: ' . url('/admin/blog/categorias'));
            exit;
        }
        $categoria = $categoria[0];

        // Contar posts nesta categoria
        $totalPosts = $db->query("
            SELECT COUNT(*) as total
            FROM tbl_blog_posts
            WHERE categoria_id = ?
        ", [$id]);
        $totalPosts = $totalPosts[0]['total'] ?? 0;

        require __DIR__ . '/../views/admin/categorias/edit.php';
    }

    /**
     * Atualizar categoria
     */
    public function update($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Verificar se categoria existe
        $categoria = $db->query("SELECT * FROM tbl_blog_categorias WHERE id = ?", [$id]);
        if (empty($categoria)) {
            $_SESSION['error'] = 'Categoria não encontrada';
            header('Location: ' . url('/admin/blog/categorias'));
            exit;
        }

        // Validações
        $errors = [];

        $nome = Security::sanitize($_POST['nome'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $ordem = (int)($_POST['ordem'] ?? 0);

        // Validar campos obrigatórios
        if (empty($nome)) $errors[] = 'Nome é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';

        // Validar tamanhos
        if (strlen($nome) > 100) $errors[] = 'Nome muito longo (máx 100 caracteres)';
        if (strlen($slug) > 100) $errors[] = 'Slug muito longo (máx 100 caracteres)';

        // Validar slug único (exceto o próprio)
        $slugExists = $db->query("SELECT id FROM tbl_blog_categorias WHERE slug = ? AND id != ?", [$slug, $id]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe em outra categoria';
        }

        // Validar slug não conflita com páginas do AEGIS
        $pageExists = $db->query("SELECT id FROM pages WHERE slug = ?", [$slug]);
        if (!empty($pageExists)) {
            $errors[] = 'Slug já existe como página do sistema';
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/blog/categorias/' . $id . '/edit'));
            exit;
        }

        // Atualizar categoria
        $db->update('tbl_blog_categorias', [
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'ordem' => $ordem
        ], ['id' => $id]);

        // Limpar cache
        SimpleCache::delete('blog_categorias');
        SimpleCache::delete('blog_posts');

        $_SESSION['success'] = 'Categoria atualizada com sucesso!';
        header('Location: ' . url('/admin/blog/categorias'));
        exit;
    }

    /**
     * Deletar categoria
     */
    public function delete($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Verificar se tem posts nesta categoria
        $totalPosts = $db->query("
            SELECT COUNT(*) as total
            FROM tbl_blog_posts
            WHERE categoria_id = ?
        ", [$id]);
        $totalPosts = $totalPosts[0]['total'] ?? 0;

        if ($totalPosts > 0) {
            $_SESSION['error'] = "Não é possível deletar esta categoria pois existem {$totalPosts} post(s) vinculado(s). Mova os posts para outra categoria primeiro.";
            header('Location: ' . url('/admin/blog/categorias'));
            exit;
        }

        // Deletar categoria
        $db->delete('tbl_blog_categorias', ['id' => $id]);

        // Limpar cache
        SimpleCache::delete('blog_categorias');

        $_SESSION['success'] = 'Categoria deletada com sucesso!';
        header('Location: ' . url('/admin/blog/categorias'));
        exit;
    }
}
