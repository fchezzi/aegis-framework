<?php
/**
 * AEGIS Framework - Blog Module Routes
 * Version: 1.0.0
 */

// =========================================
// HELPER: Verificar se módulo é público
// =========================================
if (!function_exists('checkModuleAccess')) {
    function checkModuleAccess($moduleName) {
        // Se MEMBERS desabilitado, libera acesso
        if (!ENABLE_MEMBERS) {
            return true;
        }

        // Ler module.json do módulo
        $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";

        if (!file_exists($moduleJsonPath)) {
            // Módulo sem metadata: bloqueado
            http_response_code(404);
            echo "<!DOCTYPE html>";
            echo "<html lang='pt-BR'><head><meta charset='UTF-8'>";
            echo "<title>Módulo Não Encontrado</title>";
            echo "<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;";
            echo "display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;";
            echo "background:#f5f5f5;text-align:center;}";
            echo "h1{font-size:48px;color:#64748b;margin-bottom:10px;}";
            echo "p{color:#666;margin-bottom:20px;}";
            echo "a{color:#667eea;text-decoration:none;}</style></head><body>";
            echo "<div><h1>404</h1>";
            echo "<p>Módulo não encontrado.</p>";
            echo "<a href='" . url('/home') . "'>← Voltar para Home</a></div>";
            echo "</body></html>";
            exit;
        }

        $json = file_get_contents($moduleJsonPath);
        $metadata = json_decode($json, true);

        if (!$metadata) {
            // JSON inválido: bloqueado
            http_response_code(500);
            exit('Erro ao ler configuração do módulo');
        }

        $isPublic = ($metadata['public'] ?? false);

        if ($isPublic) {
            // Módulo público: libera acesso sem login
            return true;
        }

        // Módulo privado: exige autenticação
        MemberAuth::require();
        return true;
    }
}

// =====================================================
// ADMIN ROUTES (Authenticated)
// =====================================================

// Blog - Rota raiz (redireciona para posts)
Router::get('/admin/blog', function() {
    Auth::require();
    header('Location: ' . url('/admin/blog/posts'));
    exit;
});

// Posts - Listagem
Router::get('/admin/blog/posts', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->index();
});

// Posts - Criar
Router::get('/admin/blog/posts/create', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->create();
});

// Posts - Salvar
Router::post('/admin/blog/posts/store', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->store();
});

// Posts - Editar
Router::get('/admin/blog/posts/:id/edit', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->edit($id);
});

// Posts - Atualizar
Router::post('/admin/blog/posts/:id/update', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->update($id);
});

// Posts - Deletar
Router::post('/admin/blog/posts/:id/delete', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->delete($id);
});

// Posts Relacionados - Adicionar (AJAX)
Router::post('/admin/blog/posts/:id/relacionados/add', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->addRelacionado($id);
});

// Posts Relacionados - Remover (AJAX)
Router::post('/admin/blog/posts/:id/relacionados/remove', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->removeRelacionado($id);
});

// Posts Relacionados - Buscar (AJAX)
Router::get('/admin/blog/posts/:id/relacionados/search', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->searchRelacionados($id);
});

// Upload de imagem inline (TinyMCE)
Router::post('/admin/blog/upload-image', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminPostsController.php';
    $controller = new AdminPostsController();
    $controller->uploadImage();
});

// Categorias - Listagem
Router::get('/admin/blog/categorias', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->index();
});

// Categorias - Criar
Router::get('/admin/blog/categorias/create', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->create();
});

// Categorias - Salvar
Router::post('/admin/blog/categorias/store', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->store();
});

// Categorias - Editar
Router::get('/admin/blog/categorias/:id/edit', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->edit($id);
});

// Categorias - Atualizar
Router::post('/admin/blog/categorias/:id/update', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->update($id);
});

// Categorias - Deletar
Router::post('/admin/blog/categorias/:id/delete', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminCategoriasController.php';
    $controller = new AdminCategoriasController();
    $controller->delete($id);
});

// =====================================================
// PUBLIC ROUTES (Open to everyone)
// =====================================================
// IMPORTANTE: Rotas mais específicas ANTES das genéricas!

// Listagem de posts (home do blog) - ESPECÍFICA
Router::get('/blog', function() {
    checkModuleAccess('blog');
    require_once __DIR__ . '/controllers/PublicBlogController.php';
    $controller = new PublicBlogController();
    $controller->index();
});

// Listagem de posts - Paginação - ESPECÍFICA (ANTES da rota genérica)
Router::get('/blog/pagina/:page', function($page) {
    checkModuleAccess('blog');
    require_once __DIR__ . '/controllers/PublicBlogController.php';
    $controller = new PublicBlogController();
    $controller->index($page);
});

// Listagem por categoria - Paginação - ESPECÍFICA (ANTES da rota genérica)
Router::get('/blog/:categoria_slug/pagina/:page', function($slug, $page) {
    checkModuleAccess('blog');
    require_once __DIR__ . '/controllers/PublicBlogController.php';
    $controller = new PublicBlogController();
    $controller->categoria($slug, $page);
});

// Listagem por categoria - Base (sem paginação) - GENÉRICA (DEPOIS das específicas)
// ATENÇÃO: Esta rota captura /blog/qualquer-coisa, então deve vir por último!
Router::get('/blog/:categoria_slug', function($slug) {
    checkModuleAccess('blog');
    require_once __DIR__ . '/controllers/PublicBlogController.php';
    $controller = new PublicBlogController();
    $controller->categoria($slug);
});

// NOTA: Rota de posts agora usa prefixo /blog/
// URL antiga: /:categoria/:post (conflitava com outros módulos)
// URL nova: /blog/:categoria/:post (zero conflito)
// Rota implementada em routes.php principal linha ~942
// 301 redirects implementados para migração (linha ~904)
