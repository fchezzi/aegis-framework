<?php
/**
 * Catch-All Routes
 * Rotas genéricas que devem vir por ÚLTIMO
 * Inclui: páginas dinâmicas, redirects de blog, etc.
 */

// ================================================
// GENERIC PAGE ROUTE (Protected by permissions)
// ================================================
// IMPORTANTE: Esta rota DEVE estar no final
// para não interceptar rotas específicas (/admin, /login, etc)

Router::get('/:slug', function($slug) {
    $controller = new PageController();
    $controller->show($slug);
});

// =========================================
// 301 REDIRECTS - URLs antigas do blog (migração)
// =========================================
// Para migrar blogs de outros frameworks, redireciona URLs antigas
// Formato antigo: /:categoria/:post → Novo: /blog/:categoria/:post

Router::get('/:categoria_slug/:post_slug', function($categoriaSlug, $postSlug) {
    // Verificar se blog está instalado
    $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');

    if (!in_array('blog', $installedModules)) {
        // Blog não instalado → 404 normal
        http_response_code(404);
        echo "404 - Not Found";
        return;
    }

    // Blog instalado → 301 redirect permanente para nova URL
    $newUrl = url('/blog/' . $categoriaSlug . '/' . $postSlug);
    header('Location: ' . $newUrl, true, 301);
    exit;
});

// Redirect para categoria antiga → nova
Router::get('/:categoria_slug/pagina/:page', function($categoriaSlug, $page) {
    $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');

    if (!in_array('blog', $installedModules)) {
        http_response_code(404);
        echo "404 - Not Found";
        return;
    }

    $newUrl = url('/blog/' . $categoriaSlug . '/pagina/' . $page);
    header('Location: ' . $newUrl, true, 301);
    exit;
});

// =========================================
// ROTA GENÉRICA DO BLOG (ÚLTIMA PRIORIDADE)
// =========================================
// Esta rota trata URLs no formato /blog/:categoria/:post
// Prefixo /blog/ garante zero conflito com outros módulos

Router::get('/blog/:categoria_slug/:post_slug', function($categoriaSlug, $postSlug) {
    // Verificar se blog está instalado
    $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');

    if (!in_array('blog', $installedModules)) {
        http_response_code(404);
        echo "404 - Not Found";
        return;
    }

    // Verificar acesso ao módulo blog
    $blogRoutesPath = ROOT_PATH . 'modules/blog/routes.php';
    if (!file_exists($blogRoutesPath)) {
        http_response_code(500);
        error_log("Blog routes file not found: {$blogRoutesPath}");
        echo "500 - Erro ao carregar módulo de blog";
        return;
    }
    require_once $blogRoutesPath;
    checkModuleAccess('blog');

    // Carregar controller do blog
    $blogControllerPath = ROOT_PATH . 'modules/blog/controllers/PublicBlogController.php';
    if (!file_exists($blogControllerPath)) {
        http_response_code(500);
        error_log("Blog controller not found: {$blogControllerPath}");
        echo "500 - Erro ao carregar controller de blog";
        return;
    }
    require_once $blogControllerPath;
    $controller = new PublicBlogController();
    $controller->postByCategory($categoriaSlug, $postSlug);
});
