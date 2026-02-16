<?php
/**
 * AEGIS Framework - Artigos Module Routes
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

// Artigos - Listagem
Router::get('/admin/artigos', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->index();
});

// Artigos - Novo (formulário)
Router::get('/admin/artigos/novo', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->novo();
});

// Artigos - Criar (processar formulário)
Router::post('/admin/artigos/criar', function() {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->criar();
});

// Artigos - Editar (formulário)
Router::get('/admin/artigos/editar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->editar($id);
});

// Artigos - Atualizar (processar formulário)
Router::post('/admin/artigos/atualizar/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->atualizar($id);
});

// Artigos - Excluir
Router::post('/admin/artigos/excluir/:id', function($id) {
    Auth::require();
    require_once __DIR__ . '/controllers/AdminArtigosController.php';
    $controller = new AdminArtigosController();
    $controller->excluir($id);
});

// =====================================================
// PUBLIC ROUTES (Open to everyone)
// =====================================================

// Listagem de artigos (home)
Router::get('/artigos', function() {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->index();
});

// Listagem de artigos - Paginação
Router::get('/artigos/pagina/:page', function($page) {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->index($page);
});

// Busca AJAX de artigos
Router::post('/artigos/buscar', function() {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->buscar();
});

// Processar formulário de solicitação (captura de lead)
Router::post('/artigos/:slug/solicitar', function($slug) {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->solicitar($slug);
});

// Página individual do artigo (DEVE vir por último para não capturar rotas acima)
Router::get('/artigos/:slug', function($slug) {
    checkModuleAccess('artigos');
    require_once __DIR__ . '/controllers/PublicArtigosController.php';
    $controller = new PublicArtigosController();
    $controller->artigo($slug);
});
