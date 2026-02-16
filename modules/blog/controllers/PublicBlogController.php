<?php
/**
 * AEGIS Framework - Blog Module
 * Public Blog Controller
 * Version: 1.0.0
 */

class PublicBlogController {

    /**
     * Listagem de posts (paginada)
     */
    public function index($page = 1) {
        $db = DB::connect();

        $page = max(1, (int)$page);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Cache key
        $cacheKey = "blog_posts_page_{$page}";

        // Tentar buscar do cache
        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            extract($cached);
        } else {
            // Buscar posts ativos com informações de categoria
            $posts = $db->query("
                SELECT
                    p.*,
                    c.nome AS categoria_nome,
                    c.slug AS categoria_slug
                FROM tbl_blog_posts p
                LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
                WHERE p.ativo = ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ", [1, $perPage, $offset]);

            // Contar total de posts
            $totalResult = $db->query("
                SELECT COUNT(*) as total
                FROM tbl_blog_posts
                WHERE ativo = ?
            ", [1]);
            $total = $totalResult[0]['total'] ?? 0;

            $totalPages = ceil($total / $perPage);

            // Cachear por 5 minutos
            SimpleCache::set($cacheKey, compact('posts', 'total', 'totalPages'), 300);
        }

        // Buscar categorias para sidebar
        $categorias = $this->getCategorias();

        require __DIR__ . '/../views/public/index.php';
    }

    /**
     * Listagem de posts por categoria (paginada)
     */
    public function categoria($slug, $page = 1) {
        $db = DB::connect();

        $page = max(1, (int)$page);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Buscar categoria
        $categoria = $db->query("
            SELECT * FROM tbl_blog_categorias
            WHERE slug = ? AND ativo = ?
        ", [$slug, 1]);

        if (empty($categoria)) {
            http_response_code(404);
            echo "Categoria não encontrada";
            exit;
        }
        $categoria = $categoria[0];

        // Cache key
        $cacheKey = "blog_categoria_{$slug}_page_{$page}";

        // Tentar buscar do cache
        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            extract($cached);
        } else {
            // Buscar posts desta categoria
            $posts = $db->query("
                SELECT
                    p.*,
                    c.nome AS categoria_nome,
                    c.slug AS categoria_slug
                FROM tbl_blog_posts p
                LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
                WHERE p.categoria_id = ? AND p.ativo = ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ", [$categoria['id'], 1, $perPage, $offset]);

            // Contar total de posts nesta categoria
            $totalResult = $db->query("
                SELECT COUNT(*) as total
                FROM tbl_blog_posts
                WHERE categoria_id = ? AND ativo = ?
            ", [$categoria['id'], 1]);
            $total = $totalResult[0]['total'] ?? 0;

            $totalPages = ceil($total / $perPage);

            // Cachear por 5 minutos
            SimpleCache::set($cacheKey, compact('posts', 'total', 'totalPages'), 300);
        }

        // Buscar categorias para sidebar
        $categorias = $this->getCategorias();

        require __DIR__ . '/../views/public/categoria.php';
    }

    /**
     * Post individual (método legado - compatibilidade)
     */
    public function post($slug) {
        $db = DB::connect();

        // Cache key
        $cacheKey = "blog_post_{$slug}";

        // Tentar buscar do cache
        $post = SimpleCache::get($cacheKey);

        if ($post === null) {
            // Buscar post com informações de categoria e autor
            $post = $db->query("
                SELECT
                    p.*,
                    c.nome AS categoria_nome,
                    c.slug AS categoria_slug,
                    a.name AS autor_nome
                FROM tbl_blog_posts p
                LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
                LEFT JOIN users a ON a.id = p.autor_id
                WHERE p.slug = ? AND p.ativo = ?
            ", [$slug, 1]);

            if (empty($post)) {
                http_response_code(404);
                echo "Post não encontrado";
                exit;
            }
            $post = $post[0];

            // Cachear por 10 minutos
            SimpleCache::set($cacheKey, $post, 600);
        }

        // Incrementar visualizações (não cacheado)
        $db->query("
            UPDATE tbl_blog_posts
            SET visualizacoes = visualizacoes + 1
            WHERE id = ?
        ", [$post['id']]);

        // Buscar posts relacionados HÍBRIDO
        $postsRelacionados = $this->getPostsRelacionados($post['id'], $post['categoria_id']);

        // Buscar categorias para sidebar
        $categorias = $this->getCategorias();

        require __DIR__ . '/../views/public/post.php';
    }

    /**
     * Post individual por categoria (SEO friendly)
     * URL: /categoria-slug/post-slug
     */
    public function postByCategory($categoriaSlug, $postSlug) {
        $db = DB::connect();

        // Cache key
        $cacheKey = "blog_post_{$categoriaSlug}_{$postSlug}";

        // Tentar buscar do cache
        $post = SimpleCache::get($cacheKey);

        if ($post === null) {
            // Buscar post com validação de categoria
            $post = $db->query("
                SELECT
                    p.*,
                    c.nome AS categoria_nome,
                    c.slug AS categoria_slug,
                    a.name AS autor_nome
                FROM tbl_blog_posts p
                LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
                LEFT JOIN users a ON a.id = p.autor_id
                WHERE p.slug = ? AND c.slug = ? AND p.ativo = ?
            ", [$postSlug, $categoriaSlug, 1]);

            if (empty($post)) {
                http_response_code(404);
                echo "Post não encontrado";
                exit;
            }
            $post = $post[0];

            // Cachear por 10 minutos
            SimpleCache::set($cacheKey, $post, 600);
        }

        // Incrementar visualizações (não cacheado)
        $db->query("
            UPDATE tbl_blog_posts
            SET visualizacoes = visualizacoes + 1
            WHERE id = ?
        ", [$post['id']]);

        // Buscar posts relacionados HÍBRIDO
        $postsRelacionados = $this->getPostsRelacionados($post['id'], $post['categoria_id']);

        // Buscar categorias para sidebar
        $categorias = $this->getCategorias();

        require __DIR__ . '/../views/public/post.php';
    }

    /**
     * Buscar posts relacionados (HÍBRIDO: manual OU automático)
     *
     * Lógica:
     * 1. Se tem posts relacionados manuais, usar eles
     * 2. Senão, buscar automaticamente da mesma categoria
     */
    private function getPostsRelacionados($postId, $categoriaId) {
        $db = DB::connect();

        // Tentar buscar relacionados manuais
        $manuais = $db->query("
            SELECT
                p.id, p.titulo, p.slug, p.introducao, p.imagem, p.created_at,
                c.slug AS categoria_slug
            FROM tbl_blog_relacionados r
            INNER JOIN tbl_blog_posts p ON p.id = r.post_relacionado_id
            LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
            WHERE r.post_id = ? AND p.ativo = ?
            ORDER BY r.ordem ASC
            LIMIT 3
        ", [$postId, 1]);

        // Se tem relacionados manuais, retornar eles
        if (!empty($manuais)) {
            return $manuais;
        }

        // Senão, buscar automaticamente da mesma categoria
        $automaticos = $db->query("
            SELECT
                p.id, p.titulo, p.slug, p.introducao, p.imagem, p.created_at,
                c.slug AS categoria_slug
            FROM tbl_blog_posts p
            LEFT JOIN tbl_blog_categorias c ON c.id = p.categoria_id
            WHERE p.categoria_id = ?
                AND p.id != ?
                AND p.ativo = ?
            ORDER BY p.created_at DESC
            LIMIT 3
        ", [$categoriaId, $postId, 1]);

        return $automaticos;
    }

    /**
     * Buscar categorias ativas (para sidebar)
     */
    private function getCategorias() {
        $db = DB::connect();

        // Cache de categorias
        $categorias = SimpleCache::get('blog_categorias_public');

        if ($categorias === null) {
            $categorias = $db->query("
                SELECT
                    c.*,
                    COUNT(p.id) AS total_posts
                FROM tbl_blog_categorias c
                LEFT JOIN tbl_blog_posts p ON p.categoria_id = c.id AND p.ativo = ?
                WHERE c.ativo = ?
                GROUP BY c.id
                HAVING total_posts > 0
                ORDER BY c.ordem ASC, c.nome ASC
            ", [1, 1]);

            // Cachear por 10 minutos
            SimpleCache::set('blog_categorias_public', $categorias, 600);
        }

        return $categorias;
    }
}
