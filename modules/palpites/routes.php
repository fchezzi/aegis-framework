<?php
/**
 * Rotas do Módulo Palpites
 * Este arquivo só é carregado se o módulo estiver instalado
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

// =========================================
// ROTAS ADMIN
// =========================================

// Dashboard do módulo
Router::get('/admin/palpites', function() {
    Auth::require();
    require_once __DIR__ . '/views/admin/dashboard.php';
});

// Monitor de Performance
Router::get('/admin/palpites/performance', function() {
    Auth::require();
    require_once __DIR__ . '/views/admin/performance.php';
});

// Palpiteiros
Router::get('/admin/palpites/palpiteiros', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/palpiteiros/index.php';
});

Router::get('/admin/palpites/palpiteiros/create', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/palpiteiros/create.php';
});

Router::post('/admin/palpites/palpiteiros/store', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpiteirosController.php';
    $controller = new PalpiteirosController();
    $controller->store();
});

Router::get('/admin/palpites/palpiteiros/:id/edit', function($id) {
    Auth::require();
    
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/admin/palpiteiros/edit.php';
});

Router::post('/admin/palpites/palpiteiros/:id/update', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/PalpiteirosController.php';
    $controller = new PalpiteirosController();
    $controller->update();
});

Router::post('/admin/palpites/palpiteiros/:id/delete', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/PalpiteirosController.php';
    $controller = new PalpiteirosController();
    $controller->delete();
});

Router::post('/admin/palpites/palpiteiros/move-up', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpiteirosController.php';
    $controller = new PalpiteirosController();
    $controller->moveUp();
});

Router::post('/admin/palpites/palpiteiros/move-down', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpiteirosController.php';
    $controller = new PalpiteirosController();
    $controller->moveDown();
});

// Times
Router::get('/admin/palpites/times', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/times/index.php';
});

Router::get('/admin/palpites/times/create', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/times/create.php';
});

Router::post('/admin/palpites/times/store', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/TimesController.php';
    $controller = new TimesController();
    $controller->store();
});

Router::get('/admin/palpites/times/:id/edit', function($id) {
    Auth::require();
    
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/admin/times/edit.php';
});

Router::post('/admin/palpites/times/:id/update', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/TimesController.php';
    $controller = new TimesController();
    $controller->update();
});

Router::post('/admin/palpites/times/:id/delete', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/TimesController.php';
    $controller = new TimesController();
    $controller->delete();
});

// Jogos
Router::get('/admin/palpites/jogos', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/jogos/index.php';
});

Router::get('/admin/palpites/jogos/create', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/jogos/create.php';
});

Router::post('/admin/palpites/jogos/store', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/JogosController.php';
    $controller = new JogosController();
    $controller->store();
});

Router::get('/admin/palpites/jogos/:id/edit', function($id) {
    Auth::require();
    
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/admin/jogos/edit.php';
});

Router::post('/admin/palpites/jogos/:id/update', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/JogosController.php';
    $controller = new JogosController();
    $controller->update();
});

Router::post('/admin/palpites/jogos/:id/delete', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/JogosController.php';
    $controller = new JogosController();
    $controller->delete();
});

// Palpites
Router::get('/admin/palpites/palpites', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/palpites/index.php';
});

Router::get('/admin/palpites/palpites/ao-vivo', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/palpites/ao-vivo.php';
});

Router::post('/admin/palpites/palpites/ao-vivo/salvar', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->salvarAoVivo();
});

Router::post('/admin/palpites/palpites/ao-vivo/adicionar', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->adicionarAoVivo();
});

Router::post('/admin/palpites/palpites/ao-vivo/deletar', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->deletarAoVivo();
});

Router::get('/admin/palpites/palpites/create', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/palpites/create.php';
});

Router::post('/admin/palpites/palpites/store', function() {
    Auth::require();
    
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->store();
});

Router::get('/admin/palpites/palpites/:id/edit', function($id) {
    Auth::require();
    
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/admin/palpites/edit.php';
});

Router::post('/admin/palpites/palpites/:id/update', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->update();
});

Router::post('/admin/palpites/palpites/:id/delete', function($id) {
    Auth::require();
    
    $_POST['id'] = $id;
    require_once __DIR__ . '/controllers/PalpitesController.php';
    $controller = new PalpitesController();
    $controller->delete();
});

// Resultados
Router::get('/admin/palpites/resultados', function() {
    Auth::require();
    
    require_once __DIR__ . '/views/admin/resultados/index.php';
});

Router::get('/admin/palpites/resultados/:id', function($id) {
    Auth::require();
    
    $_GET['jogo_id'] = $id;
    require_once __DIR__ . '/views/admin/resultados/show.php';
});

Router::post('/admin/palpites/resultados/:id/cadastrar', function($id) {
    Auth::require();
    
    $_POST['jogo_id'] = $id;
    require_once __DIR__ . '/controllers/ResultadosController.php';
    $controller = new ResultadosController();
    $controller->cadastrar();
});

// =========================================
// API ENDPOINTS (sem autenticação para OBS)
// =========================================

// API de updates para tela de exibição
Router::get('/palpites/api/updates', function() {
    require_once __DIR__ . '/api/updates.php';
});

// =========================================
// ROTAS PÚBLICAS - TELAS DE EXIBIÇÃO
// =========================================

// TELA 1: Exibição de Palpites (jogos ativos)
Router::get('/palpites/exibicao-palpites', function() {
    checkModuleAccess('palpites');
    require_once __DIR__ . '/views/public/exibicao-palpites.php';
});

// TELA 2: Exibição de Resultados (jogo finalizado + quem acertou)
Router::get('/palpites/exibicao-resultados', function() {
    checkModuleAccess('palpites');
    require_once __DIR__ . '/views/public/exibicao-resultados.php';
});

// TELA 3: Exibição de Ranking (classificação geral)
Router::get('/palpites/exibicao-ranking', function() {
    checkModuleAccess('palpites');
    require_once __DIR__ . '/views/public/exibicao-ranking.php';
});

// Redirect antigo para nova tela de palpites
Router::get('/palpites/exibicao', function() {
    Core::redirect('/palpites/exibicao-palpites');
});
