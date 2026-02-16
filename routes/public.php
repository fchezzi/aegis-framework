<?php
/**
 * Public Routes
 * Rotas acessíveis publicamente (login, páginas públicas, etc.)
 */

// ================================================
// HOME
// ================================================

Router::get('/', function() {
    // 1. SEMPRE tentar carregar home pública primeiro (se existir)
    $pageFile = ROOT_PATH . 'frontend/pages/home.php';
    if (file_exists($pageFile)) {
        require $pageFile;
        return;
    }

    // 2. Sem home pública definida

    // Sistema ESTÁTICO ou SEM MEMBERS → página pública com link admin
    if (!defined('DB_TYPE') || DB_TYPE === 'none' || !Core::membersEnabled()) {
        echo "<h1>Bem-vindo ao AEGIS Framework</h1>";
        echo "<p>Crie o arquivo <code>/frontend/pages/home.php</code> para personalizar esta página.</p>";

        // Se tem banco (não é estático) → mostrar link admin
        if (defined('DB_TYPE') && DB_TYPE !== 'none') {
            echo "<p>Acesse <a href='/admin'>/admin</a> para área administrativa.</p>";
        }
        return;
    }

    // 3. Sistema COM MEMBERS e SEM home pública → área de membros
    if (MemberAuth::check()) {
        Core::redirect('/home');
    } else {
        Core::redirect('/login');
    }
});

// ================================================
// MEMBER AUTHENTICATION
// ================================================

Router::get('/login', function() {
    $controller = new MemberAuthController();
    $controller->login();
});

Router::post('/login', function() {
    $controller = new MemberAuthController();
    $controller->doLogin();
});

Router::get('/logout', function() {
    $controller = new MemberAuthController();
    $controller->logout();
});

// ================================================
// MEMBER HOME
// ================================================

Router::get('/home', function() {
    $controller = new PageController();
    $controller->home();
});

// ================================================
// PÁGINAS DE EXEMPLO
// ================================================

Router::get('/exemplo-filtros', function() {
    require ROOT_PATH . 'frontend/pages/exemplo-filtros.php';
});

Router::get('/exemplo-filtros-completo', function() {
    require ROOT_PATH . 'frontend/pages/exemplo-filtros-completo.php';
});

Router::get('/exemplo-integracao', function() {
    require ROOT_PATH . 'frontend/pages/exemplo-integracao.php';
});

Router::get('/exemplo-multiplos-grupos', function() {
    require ROOT_PATH . 'frontend/pages/exemplo-multiplos-grupos.php';
});

Router::get('/exemplo-tabelas', function() {
    require ROOT_PATH . 'frontend/pages/exemplo-tabelas.php';
});

// ================================================
// DOWNLOADS - Relatórios
// ================================================

// Página de listagem
Router::get('/downloads', function() {
    $controller = new DownloadController();
    $controller->index();
});

// Gerar e baixar relatório
Router::get('/downloads/generate/:id', function($id) {
    $controller = new DownloadController();
    $controller->generate($id);
});

// ================================================
// PROFILE - Perfil do usuário
// ================================================

// Página de perfil
Router::get('/profile', function() {
    $controller = new ProfileController();
    $controller->index();
});

// Atualizar avatar
Router::post('/profile/avatar', function() {
    $controller = new ProfileController();
    $controller->updateAvatar();
});

// Atualizar senha
Router::post('/profile/password', function() {
    $controller = new ProfileController();
    $controller->updatePassword();
});

