<?php
/**
 * AEGIS Framework - Blog Module
 * Admin Posts Controller
 * Version: 1.0.0
 */

class AdminPostsController {

    /**
     * Listagem de posts
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
        $filterCategoria = isset($_GET['categoria']) ? Security::sanitize($_GET['categoria']) : '';

        // Ordenação
        $allowedSortColumns = ['titulo', 'categoria_nome', 'ativo', 'visualizacoes', 'created_at'];
        $sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns) ? $_GET['sort'] : 'created_at';
        $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Construir WHERE clauses
        $whereConditions = ['1=1'];
        $params = [];

        // Filtro de busca por título
        if (!empty($search)) {
            $whereConditions[] = "p.titulo LIKE ?";
            $params[] = "%{$search}%";
        }

        // Filtro por categoria
        if (!empty($filterCategoria)) {
            $whereConditions[] = "p.categoria_id = ?";
            $params[] = $filterCategoria;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Contar total de posts
        $countQuery = "SELECT COUNT(*) as total FROM tbl_blog_posts p WHERE {$whereClause}";
        $countResult = $db->query($countQuery, $params);
        $totalPosts = $countResult[0]['total'] ?? 0;

        // Calcular total de páginas
        $totalPagesCount = ceil($totalPosts / $perPage);

        // Buscar posts com paginação e ordenação
        // Ajustar coluna para query (algumas colunas vêm de joins)
        $sortColumnQuery = $sortColumn;
        if ($sortColumn === 'titulo' || $sortColumn === 'ativo' || $sortColumn === 'visualizacoes' || $sortColumn === 'created_at') {
            $sortColumnQuery = "p.{$sortColumn}";
        } elseif ($sortColumn === 'categoria_nome') {
            $sortColumnQuery = "c.nome";
        }

        $selectQuery = "
            SELECT
                p.*,
                c.nome AS categoria_nome,
                c.slug AS categoria_slug,
                a.name AS autor_nome
            FROM tbl_blog_posts p
            LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
            LEFT JOIN users a ON a.id = p.autor_id
            WHERE {$whereClause}
            ORDER BY {$sortColumnQuery} {$sortOrder}
            LIMIT ? OFFSET ?
        ";
        $selectParams = array_merge($params, [$perPage, $offset]);
        $posts = $db->query($selectQuery, $selectParams);

        // Buscar categorias para filtro
        $categorias = $db->query("SELECT id, nome FROM tbl_blog_categorias WHERE ativo = 1 ORDER BY nome");

        // Dados de paginação
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPagesCount,
            'perPage' => $perPage,
            'totalItems' => $totalPosts
        ];

        require __DIR__ . '/../views/admin/posts/index.php';
    }

    /**
     * Formulário de criação
     */
    public function create() {
        Auth::require();

        $db = DB::connect();

        // Buscar categorias ativas para o select
        $categorias = $db->query("
            SELECT id, nome
            FROM tbl_blog_categorias
            WHERE ativo = ?
            ORDER BY ordem ASC, nome ASC
        ", [1]);

        require __DIR__ . '/../views/admin/posts/create.php';
    }

    /**
     * Salvar novo post
     */
    public function store() {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Validações
        $errors = [];

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $introducao = Security::sanitize($_POST['introducao'] ?? '');
        $conteudo = $_POST['conteudo'] ?? ''; // HTML permitido
        $categoria_id = Security::sanitize($_POST['categoria_id'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validar campos obrigatórios
        if (empty($titulo)) $errors[] = 'Título é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';
        if (empty($introducao)) $errors[] = 'Introdução é obrigatória';
        if (empty($conteudo)) $errors[] = 'Conteúdo é obrigatório';
        if (empty($categoria_id)) $errors[] = 'Categoria é obrigatória';

        // Validar tamanhos
        if (strlen($titulo) > 255) $errors[] = 'Título muito longo (máx 255 caracteres)';
        if (strlen($slug) > 255) $errors[] = 'Slug muito longo (máx 255 caracteres)';
        if (strlen($introducao) > 350) $errors[] = 'Introdução muito longa (máx 350 caracteres)';
        if (strlen($conteudo) > 100000) $errors[] = 'Conteúdo muito longo (máx 100.000 caracteres)';

        // Validar slug único
        $slugExists = $db->query("SELECT id FROM tbl_blog_posts WHERE slug = ?", [$slug]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe, escolha outro';
        }

        // Upload de imagem (opcional)
        $imagemPath = null;
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], 'blog');

            if ($upload['success']) {
                $imagemPath = $upload['path'];
            } else {
                $errors[] = $upload['message'];
            }
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/blog/posts/create'));
            exit;
        }

        // Inserir post
        $postId = Security::generateUUID();
        $autorId = Auth::user()['id'];

        try {
            $result = $db->insert('tbl_blog_posts', [
                'id' => $postId,
                'titulo' => $titulo,
                'slug' => $slug,
                'introducao' => $introducao,
                'conteudo' => $conteudo,
                'imagem' => $imagemPath,
                'categoria_id' => $categoria_id,
                'autor_id' => $autorId,
                'visualizacoes' => 0,
                'ativo' => $ativo
            ]);

            // Log de sucesso para debug
            error_log("Blog post criado com sucesso: ID={$postId}, Titulo={$titulo}");

            // Limpar cache (todas as páginas possíveis)
            self::clearBlogCache();

            $_SESSION['success'] = 'Post criado com sucesso!';
            header('Location: ' . url('/admin/blog/posts'));
            exit;

        } catch (Exception $e) {
            // Log detalhado do erro
            error_log("ERRO ao criar post no blog: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Dados do post: ID={$postId}, Titulo={$titulo}, Slug={$slug}");

            $_SESSION['error'] = 'Erro ao salvar post: ' . $e->getMessage();
            header('Location: ' . url('/admin/blog/posts/create'));
            exit;
        }
    }

    /**
     * Formulário de edição
     */
    public function edit($id) {
        Auth::require();

        $db = DB::connect();

        // Buscar post
        $post = $db->query("SELECT * FROM tbl_blog_posts WHERE id = ?", [$id]);
        if (empty($post)) {
            $_SESSION['error'] = 'Post não encontrado';
            header('Location: ' . url('/admin/blog/posts'));
            exit;
        }
        $post = $post[0];

        // Buscar categorias ativas
        $categorias = $db->query("
            SELECT id, nome
            FROM tbl_blog_categorias
            WHERE ativo = ?
            ORDER BY ordem ASC, nome ASC
        ", [1]);

        // Buscar posts relacionados (manual)
        $relacionadosManuais = $db->query("
            SELECT
                r.id,
                p.id AS post_id,
                p.titulo,
                p.slug,
                r.ordem
            FROM tbl_blog_relacionados r
            INNER JOIN tbl_blog_posts p ON p.id = r.post_relacionado_id
            WHERE r.post_id = ?
            ORDER BY r.ordem ASC
        ", [$id]);

        require __DIR__ . '/../views/admin/posts/edit.php';
    }

    /**
     * Atualizar post
     */
    public function update($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Verificar se post existe
        $post = $db->query("SELECT * FROM tbl_blog_posts WHERE id = ?", [$id]);
        if (empty($post)) {
            $_SESSION['error'] = 'Post não encontrado';
            header('Location: ' . url('/admin/blog/posts'));
            exit;
        }
        $post = $post[0];

        // Validações
        $errors = [];

        $titulo = Security::sanitize($_POST['titulo'] ?? '');
        $slug = Security::sanitize($_POST['slug'] ?? '');
        $introducao = Security::sanitize($_POST['introducao'] ?? '');
        $conteudo = $_POST['conteudo'] ?? ''; // HTML permitido
        $categoria_id = Security::sanitize($_POST['categoria_id'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Validar campos obrigatórios
        if (empty($titulo)) $errors[] = 'Título é obrigatório';
        if (empty($slug)) $errors[] = 'Slug é obrigatório';
        if (empty($introducao)) $errors[] = 'Introdução é obrigatória';
        if (empty($conteudo)) $errors[] = 'Conteúdo é obrigatório';
        if (empty($categoria_id)) $errors[] = 'Categoria é obrigatória';

        // Validar tamanhos
        if (strlen($titulo) > 255) $errors[] = 'Título muito longo (máx 255 caracteres)';
        if (strlen($slug) > 255) $errors[] = 'Slug muito longo (máx 255 caracteres)';
        if (strlen($introducao) > 350) $errors[] = 'Introdução muito longa (máx 350 caracteres)';
        if (strlen($conteudo) > 100000) $errors[] = 'Conteúdo muito longo (máx 100.000 caracteres)';

        // Validar slug único (exceto o próprio)
        $slugExists = $db->query("SELECT id FROM tbl_blog_posts WHERE slug = ? AND id != ?", [$slug, $id]);
        if (!empty($slugExists)) {
            $errors[] = 'Slug já existe, escolha outro';
        }

        // Upload de imagem (opcional)
        $imagemPath = $post['imagem'];
        if (!empty($_FILES['imagem']['name'])) {
            $upload = Upload::image($_FILES['imagem'], 'blog');

            if ($upload['success']) {
                // Deletar imagem antiga se existir
                if (!empty($imagemPath)) {
                    Upload::delete($imagemPath);
                }
                $imagemPath = $upload['path'];
            } else {
                $errors[] = $upload['message'];
            }
        }

        // Se tem erros, voltar ao formulário
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . url('/admin/blog/posts/' . $id . '/edit'));
            exit;
        }

        // Atualizar post
        try {
            $result = $db->update('tbl_blog_posts', [
                'titulo' => $titulo,
                'slug' => $slug,
                'introducao' => $introducao,
                'conteudo' => $conteudo,
                'imagem' => $imagemPath,
                'categoria_id' => $categoria_id,
                'ativo' => $ativo
            ], ['id' => $id]);

            // Log de sucesso
            error_log("Blog post atualizado com sucesso: ID={$id}, Titulo={$titulo}");

            // Limpar cache (todas as páginas possíveis)
            self::clearBlogCache($slug);

            $_SESSION['success'] = 'Post atualizado com sucesso!';
            header('Location: ' . url('/admin/blog/posts'));
            exit;

        } catch (Exception $e) {
            // Log detalhado do erro
            error_log("ERRO ao atualizar post no blog: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Dados do post: ID={$id}, Titulo={$titulo}, Slug={$slug}");

            $_SESSION['error'] = 'Erro ao atualizar post: ' . $e->getMessage();
            header('Location: ' . url('/admin/blog/posts/' . $id . '/edit'));
            exit;
        }
    }

    /**
     * Deletar post
     */
    public function delete($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        // Buscar post para deletar imagem
        $post = $db->query("SELECT imagem, slug FROM tbl_blog_posts WHERE id = ?", [$id]);
        if (!empty($post)) {
            $post = $post[0];

            // Deletar imagem se existir
            if (!empty($post['imagem'])) {
                Upload::delete($post['imagem']);
            }

            // Deletar post (CASCADE vai deletar relacionados também)
            $db->delete('tbl_blog_posts', ['id' => $id]);

            // Limpar cache (todas as páginas possíveis)
            self::clearBlogCache($post['slug']);

            $_SESSION['success'] = 'Post deletado com sucesso!';
        } else {
            $_SESSION['error'] = 'Post não encontrado';
        }

        header('Location: ' . url('/admin/blog/posts'));
        exit;
    }

    /**
     * Adicionar post relacionado (manual)
     */
    public function addRelacionado($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        $postRelacionadoId = Security::sanitize($_POST['post_relacionado_id'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);

        if (empty($postRelacionadoId)) {
            echo json_encode(['success' => false, 'error' => 'Post relacionado não informado']);
            exit;
        }

        // Verificar se já existe
        $exists = $db->query("
            SELECT id FROM tbl_blog_relacionados
            WHERE post_id = ? AND post_relacionado_id = ?
        ", [$id, $postRelacionadoId]);

        if (!empty($exists)) {
            echo json_encode(['success' => false, 'error' => 'Relacionamento já existe']);
            exit;
        }

        // Inserir relacionamento
        $db->insert('tbl_blog_relacionados', [
            'id' => Security::generateUUID(),
            'post_id' => $id,
            'post_relacionado_id' => $postRelacionadoId,
            'ordem' => $ordem
        ]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Remover post relacionado (manual)
     */
    public function removeRelacionado($id) {
        Auth::require();
        Security::validateCSRF($_POST["csrf_token"]);

        $db = DB::connect();

        $relacionadoId = Security::sanitize($_POST['relacionado_id'] ?? '');

        if (empty($relacionadoId)) {
            echo json_encode(['success' => false, 'error' => 'ID não informado']);
            exit;
        }

        $db->delete('tbl_blog_relacionados', ['id' => $relacionadoId]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Buscar posts para adicionar como relacionados (AJAX)
     */
    public function searchRelacionados($id) {
        Auth::require();

        $db = DB::connect();

        $search = Security::sanitize($_GET['q'] ?? '');

        // Buscar posts ativos (excluindo o próprio post)
        $posts = $db->query("
            SELECT
                p.id,
                p.titulo,
                p.slug,
                c.nome AS categoria_nome
            FROM tbl_blog_posts p
            LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
            WHERE p.id != ?
                AND p.ativo = ?
                AND (p.titulo LIKE ? OR p.slug LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT 10
        ", [$id, 1, "%$search%", "%$search%"]);

        echo json_encode(['success' => true, 'posts' => $posts]);
        exit;
    }

    /**
     * Upload de imagem inline (TinyMCE)
     */
    public function uploadImage() {
        Auth::require();
        header('Content-Type: application/json');

        if (empty($_FILES['file'])) {
            echo json_encode(['error' => 'Nenhum arquivo enviado']);
            exit;
        }

        // Upload da imagem
        $upload = Upload::image($_FILES['file'], 'blog');

        if ($upload['success']) {
            // Retornar URL completa para o TinyMCE
            echo json_encode([
                'location' => url('/storage/uploads/' . $upload['path'])
            ]);
        } else {
            echo json_encode(['error' => $upload['message']]);
        }
        exit;
    }

    /**
     * Limpar todo o cache do blog
     * Remove cache de listagens paginadas, posts individuais e categorias
     *
     * @param string|null $slug Slug do post específico a limpar
     */
    private static function clearBlogCache($slug = null) {
        // Limpar cache de páginas (assumindo até 50 páginas)
        for ($i = 1; $i <= 50; $i++) {
            SimpleCache::delete("blog_posts_page_{$i}");
            SimpleCache::delete("blog_posts_categoria_*_page_{$i}");
        }

        // Limpar cache de categorias
        SimpleCache::delete('blog_categorias');
        SimpleCache::delete('blog_categorias_public');

        // Limpar post específico se informado
        if ($slug !== null) {
            SimpleCache::delete("blog_post_{$slug}");
        }

        // Limpar cache genérico (compatibilidade)
        SimpleCache::delete('blog_posts');
    }
}
