<?php
/**
 * Admin Routes
 * Rotas do painel administrativo
 * Todas requerem autenticação de admin
 */

// ================================================
// ADMIN AUTH
// ================================================

Router::get('/admin/login', function() {
    require_once ROOT_PATH . 'admin/views/login.php';
});

Router::post('/admin/login', function() {
    $controller = new AuthController();
    $controller->login();
});

Router::get('/admin/logout', function() {
    Auth::logout();
    $_SESSION['success'] = 'Logout realizado com sucesso';
    Core::redirect('/admin/login');
});

// ================================================
// ADMIN DASHBOARD
// ================================================

Router::get('/admin', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/views/dashboard.php';
});

Router::get('/admin/dashboard', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/views/dashboard.php';
});

// ================================================
// CRUD SYSTEM
// ================================================

Router::get('/admin/cruds', function() {
    Auth::require();
    $controller = new CrudManagerController();
    $controller->index();
});

Router::get('/admin/cruds/create', function() {
    Auth::require();
    $controller = new CrudManagerController();
    $controller->create();
});

Router::post('/admin/cruds', function() {
    Auth::require();
    $controller = new CrudManagerController();
    $controller->store();
});

Router::post('/admin/cruds/:id/delete', function($id) {
    Auth::require();
    $controller = new CrudManagerController();
    $controller->delete($id);
});

// ================================================
// ADMIN TOOLS (Deploy, Cache, Health, Version)
// ================================================

Router::get('/admin/deploy', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/deploy.php';
});

Router::post('/admin/deploy', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/deploy.php';
});

Router::get('/admin/cache', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/cache.php';
});

Router::post('/admin/cache', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/cache.php';
});

Router::get('/admin/health', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/health.php';
});

Router::get('/admin/version', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/version.php';
});

Router::post('/admin/version', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/version.php';
});

// ================================================
// ================================================
// ADMIN USERS (Super Admins)
// ================================================

Router::get('/admin/admins', function() {
    Auth::require();
    $controller = new AdminController();
    $controller->index();
});

Router::get('/admin/admins/create', function() {
    Auth::require();
    $controller = new AdminController();
    $controller->create();
});

Router::post('/admin/admins', function() {
    Auth::require();
    $controller = new AdminController();
    $controller->store();
});

Router::get('/admin/admins/:id/edit', function($id) {
    Auth::require();
    $controller = new AdminController();
    $controller->edit($id);
});

Router::post('/admin/admins/:id', function($id) {
    Auth::require();
    $controller = new AdminController();
    $controller->update($id);
});

Router::post('/admin/admins/:id/delete', function($id) {
    Auth::require();
    $controller = new AdminController();
    $controller->destroy($id);
});

// ================================================
// ADMIN MEMBERS
// ================================================

Router::get('/admin/members', function() {
    Auth::require();
    $controller = new MemberController();
    $controller->index();
});

Router::get('/admin/members/create', function() {
    Auth::require();
    $controller = new MemberController();
    $controller->create();
});

Router::post('/admin/members', function() {
    Auth::require();
    $controller = new MemberController();
    $controller->store();
});

Router::get('/admin/members/:id/edit', function($id) {
    Auth::require();
    $controller = new MemberController();
    $controller->edit($id);
});

Router::post('/admin/members/:id', function($id) {
    Auth::require();
    $controller = new MemberController();
    $controller->update($id);
});

Router::post('/admin/members/:id/delete', function($id) {
    Auth::require();
    $controller = new MemberController();
    $controller->destroy($id);
});

Router::get('/admin/members/:id/permissions', function($id) {
    Auth::require();
    $controller = new MemberController();
    $controller->permissions($id);
});

Router::post('/admin/members/:id/permissions', function($id) {
    Auth::require();
    $controller = new MemberController();
    $controller->updatePermissions($id);
});

// ================================================
// ADMIN GROUPS
// ================================================

Router::get('/admin/groups', function() {
    Auth::require();
    $controller = new GroupController();
    $controller->index();
});

Router::get('/admin/groups/create', function() {
    Auth::require();
    $controller = new GroupController();
    $controller->create();
});

Router::post('/admin/groups', function() {
    Auth::require();
    $controller = new GroupController();
    $controller->store();
});

Router::get('/admin/groups/:id/edit', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->edit($id);
});

Router::post('/admin/groups/:id', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->update($id);
});

Router::post('/admin/groups/:id/delete', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->destroy($id);
});

Router::get('/admin/groups/:id/permissions', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->permissions($id);
});

Router::post('/admin/groups/:id/permissions', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->updatePermissions($id);
});

Router::get('/admin/groups/:id/members', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->members($id);
});

Router::post('/admin/groups/:id/members', function($id) {
    Auth::require();
    $controller = new GroupController();
    $controller->updateMembers($id);
});

// ================================================
// ADMIN PAGES
// ================================================

Router::get('/admin/pages', function() {
    Auth::require();
    $controller = new PagesController();
    $controller->index();
});

Router::get('/admin/pages/create', function() {
    Auth::require();
    $controller = new PagesController();
    $controller->create();
});

Router::post('/admin/pages', function() {
    Auth::require();
    $controller = new PagesController();
    $controller->store();
});

Router::get('/admin/pages/:slug/edit', function($slug) {
    Auth::require();
    $controller = new PagesController();
    $controller->edit($slug);
});

Router::post('/admin/pages/:slug', function($slug) {
    Auth::require();
    $controller = new PagesController();
    $controller->update($slug);
});

Router::post('/admin/pages/:slug/delete', function($slug) {
    Auth::require();
    $controller = new PagesController();
    $controller->destroy($slug);
});

// ================================================
// ADMIN PAGE BUILDER
// ================================================

Router::get('/admin/pages/:slug/builder', function($slug) {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->edit($slug);
});

Router::post('/admin/page-builder/save-layout', function() {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->saveLayout();
});

Router::post('/admin/page-builder/add-block', function() {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->addBlock();
});

Router::post('/admin/page-builder/delete-block/:id', function($id) {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->deleteBlock($id);
});

Router::post('/admin/page-builder/add-card', function() {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->addCard();
});

Router::post('/admin/page-builder/delete-card/:id', function($id) {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->deleteCard($id);
});

// ================================================
// ADMIN COMPONENTS
// ================================================

Router::get('/admin/components', function() {
    Auth::require();
    $controller = new ComponentsController();
    $controller->index();
});

Router::get('/admin/components/metadata', function() {
    Auth::require();
    $controller = new ComponentsController();
    $controller->getMetadata();
});

Router::post('/admin/components/validate', function() {
    Auth::require();
    $controller = new ComponentsController();
    $controller->validate();
});

Router::post('/admin/components/preview', function() {
    Auth::require();
    $controller = new ComponentsController();
    $controller->preview();
});

Router::get('/admin/components/tables', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/components/tables.php';
});


Router::post('/admin/page-builder/update-card-size', function() {
    Auth::require();
    $controller = new PageBuilderController();
    $controller->updateCardSize();
});

// ================================================
// ADMIN SETTINGS
// ================================================

Router::get('/admin/settings', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->index();
});

Router::post('/admin/settings', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->update();
});

Router::post('/admin/settings/test-alert-smtp', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->testAlertSmtp();
});

Router::post('/admin/settings/test-ftp', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->testFtp();
});

Router::post('/admin/settings/test-client-smtp', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->testClientSmtp();
});

Router::post('/admin/settings/fonts/upload', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->uploadFont();
});

Router::post('/admin/settings/fonts/:id/delete', function($id) {
    Auth::require();
    $controller = new SettingsController();
    $controller->deleteFont($id);
});

Router::post('/admin/settings/remove-gsc-credentials', function() {
    Auth::require();
    $controller = new SettingsController();
    $controller->removeGscCredentials();
});

// ================================================
// ADMIN PAGESPEED INSIGHTS
// ================================================

Router::get('/admin/pagespeed', function() {
    Auth::require();
    $controller = new PageSpeedController();
    $controller->index();
});

Router::get('/admin/pagespeed/report/:id', function($id) {
    Auth::require();
    $controller = new PageSpeedController();
    $controller->report($id);
});

Router::get('/admin/pagespeed/urls', function() {
    Auth::require();
    $controller = new PageSpeedUrlsController();
    $controller->index();
});

Router::post('/admin/pagespeed/urls/store', function() {
    Auth::require();
    $controller = new PageSpeedUrlsController();
    $controller->store();
});

Router::post('/admin/pagespeed/urls/:id/toggle', function($id) {
    Auth::require();
    $controller = new PageSpeedUrlsController();
    $controller->toggle($id);
});

Router::post('/admin/pagespeed/urls/:id/delete', function($id) {
    Auth::require();
    $controller = new PageSpeedUrlsController();
    $controller->delete($id);
});

// ================================================
// ADMIN INCLUDES
// ================================================

Router::get('/admin/includes', function() {
    Auth::require();
    $controller = new IncludesController();
    $controller->index();
});

Router::get('/admin/includes/create', function() {
    Auth::require();
    $controller = new IncludesController();
    $controller->create();
});

Router::post('/admin/includes', function() {
    Auth::require();
    $controller = new IncludesController();
    $controller->store();
});

Router::get('/admin/includes/:name/edit', function($name) {
    Auth::require();
    $controller = new IncludesController();
    $controller->edit($name);
});

Router::post('/admin/includes/:name', function($name) {
    Auth::require();
    $controller = new IncludesController();
    $controller->update($name);
});

Router::post('/admin/includes/:name/restore', function($name) {
    Auth::require();
    $controller = new IncludesController();
    $controller->restore($name);
});

Router::post('/admin/includes/:name/delete', function($name) {
    Auth::require();
    $controller = new IncludesController();
    $controller->destroy($name);
});

// ================================================
// ADMIN MENU
// ================================================

Router::get('/admin/menu', function() {
    Auth::require();
    $controller = new MenuController();
    $controller->index();
});

Router::get('/admin/menu/create', function() {
    Auth::require();
    $controller = new MenuController();
    $controller->create();
});

Router::post('/admin/menu', function() {
    Auth::require();
    $controller = new MenuController();
    $controller->store();
});

Router::get('/admin/menu/:id/edit', function($id) {
    Auth::require();
    $controller = new MenuController();
    $controller->edit($id);
});

// IMPORTANTE: Rota específica /order ANTES da genérica /:id
Router::post('/admin/menu/order', function() {
    Auth::require();
    $controller = new MenuController();
    $controller->updateOrder();
});

Router::post('/admin/menu/:id', function($id) {
    Auth::require();
    $controller = new MenuController();
    $controller->update($id);
});

Router::post('/admin/menu/:id/delete', function($id) {
    Auth::require();
    $controller = new MenuController();
    $controller->destroy($id);
});

// ================================================
// ADMIN MODULES
// ================================================

Router::get('/admin/modules', function() {
    Auth::require();
    $controller = new ModulesController();
    $controller->index();
});

Router::post('/admin/modules/update', function() {
    Auth::require();
    $controller = new ModulesController();
    $controller->update();
});

Router::post('/admin/modules/install', function() {
    Auth::require();
    $controller = new ModulesController();
    $controller->install();
});

Router::post('/admin/modules/uninstall', function() {
    Auth::require();
    $controller = new ModulesController();
    $controller->uninstall();
});

Router::get('/admin/modules/uninstall-step1', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/views/modules/uninstall-step1.php';
});

Router::post('/admin/modules/verify-uninstall', function() {
    Auth::require();
    $controller = new ModulesController();
    $controller->verifyUninstall();
});

// ================================================
// ADMIN SQL IMPORT
// ================================================

Router::get('/admin/import-sql', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/import-sql.php';
});

Router::post('/admin/import-sql', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/import-sql.php';
});

// ================================================
// ADMIN CSV IMPORT
// ================================================

Router::get('/admin/import-csv', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/import-csv.php';
});

Router::post('/admin/api/process-csv', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/api/process-csv.php';
});

Router::post('/admin/api/import-csv', function() {
    Auth::require();
    require_once ROOT_PATH . 'admin/api/import-csv.php';
});

// ================================================
// ADMIN RELATÓRIOS
// ================================================

// Listar templates
Router::get('/admin/reports', function() {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->index();
});

// Criar template
Router::get('/admin/reports/create', function() {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->create();
});

Router::post('/admin/reports/store', function() {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->store();
});

// Editar template
Router::get('/admin/reports/:id/edit', function($id) {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->edit($id);
});

Router::post('/admin/reports/:id/update', function($id) {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->update($id);
});

// Deletar template
Router::post('/admin/reports/:id/delete', function($id) {
    Auth::require();
    $controller = new ReportTemplateController();
    $controller->destroy($id);
});

// ================================================
// ADMIN FONTS (Fontes Customizadas WOFF2)
// ================================================

Router::get('/admin/fonts', function() {
    Auth::require();
    $controller = new FontsController();
    $controller->index();
});

Router::post('/admin/fonts', function() {
    Auth::require();
    $controller = new FontsController();
    $controller->store();
});

Router::post('/admin/fonts/:id/delete', function($id) {
    Auth::require();
    $controller = new FontsController();
    $controller->destroy($id);
});

Router::get('/admin/fonts/:id/download', function($id) {
    Auth::require();
    $controller = new FontsController();
    $controller->download($id);
});

Router::get('/admin/fonts/preview', function() {
    Auth::require();
    $controller = new FontsController();
    $controller->preview();
});

// ================================================
// ADMIN BANNERS HERO
// ================================================

Router::get('/admin/banners', function() {
    Auth::require();
    $controller = new BannerController();
    $controller->index();
});

Router::get('/admin/banners/create', function() {
    Auth::require();
    $controller = new BannerController();
    $controller->create();
});

Router::post('/admin/banners', function() {
    Auth::require();
    $controller = new BannerController();
    $controller->store();
});

Router::get('/admin/banners/:id/edit', function($id) {
    Auth::require();
    $controller = new BannerController();
    $controller->edit($id);
});

Router::post('/admin/banners/:id', function($id) {
    Auth::require();
    $controller = new BannerController();
    $controller->update($id);
});

Router::post('/admin/banners/:id/delete', function($id) {
    Auth::require();
    $controller = new BannerController();
    $controller->destroy($id);
});

// ================================================
// ADMIN FONTES DE DADOS CUSTOMIZÁVEIS
// ================================================

// Listar fontes
Router::get('/admin/data-sources', function() {
    Auth::require();
    $controller = new DataSourceController();
    $controller->index();
});

// Criar fonte
Router::get('/admin/data-sources/create', function() {
    Auth::require();
    $controller = new DataSourceController();
    $controller->create();
});

Router::post('/admin/data-sources/store', function() {
    Auth::require();
    $controller = new DataSourceController();
    $controller->store();
});

// Editar fonte
Router::get('/admin/data-sources/edit/:id', function($id) {
    Auth::require();
    $controller = new DataSourceController();
    $controller->edit($id);
});

Router::post('/admin/data-sources/update/:id', function($id) {
    Auth::require();
    $controller = new DataSourceController();
    $controller->update($id);
});

// Deletar fonte
Router::post('/admin/data-sources/delete/:id', function($id) {
    Auth::require();
    $controller = new DataSourceController();
    $controller->destroy($id);
});

// Duplicar fonte
Router::get('/admin/data-sources/duplicate/:id', function($id) {
    Auth::require();
    $controller = new DataSourceController();
    $controller->duplicate($id);
});

// AJAX: Obter colunas de uma tabela
Router::get('/admin/data-sources/get-columns', function() {
    Auth::require();
    $controller = new DataSourceController();
    $controller->getColumns();
});

// AJAX: Preview de query
Router::post('/admin/data-sources/preview', function() {
    Auth::require();
    $controller = new DataSourceController();
    $controller->preview();
});

// ================================================
// BANNER HERO - Banners principais do site
// ================================================


// CRUD: banner_hero
// Gerado automaticamente em: 2026-02-14 17:53:03

Router::get('/admin/bannerhero', function() {
    $controller = new BannerheroController();
    $controller->index();
});

Router::get('/admin/bannerhero/create', function() {
    $controller = new BannerheroController();
    $controller->create();
});

Router::post('/admin/bannerhero', function() {
    $controller = new BannerheroController();
    $controller->store();
});

Router::get('/admin/bannerhero/:id/edit', function($id) {
    $controller = new BannerheroController();
    $controller->edit($id);
});

Router::post('/admin/bannerhero/:id', function($id) {
    $controller = new BannerheroController();
    $controller->update($id);
});

Router::post('/admin/bannerhero/:id/delete', function($id) {
    $controller = new BannerheroController();
    $controller->destroy($id);
});


// CRUD: bigbanner
// Gerado automaticamente em: 2026-02-14 19:01:34

Router::get('/admin/bigbanner', function() {
    $controller = new BigbannerController();
    $controller->index();
});

Router::get('/admin/bigbanner/create', function() {
    $controller = new BigbannerController();
    $controller->create();
});

Router::post('/admin/bigbanner', function() {
    $controller = new BigbannerController();
    $controller->store();
});

Router::get('/admin/bigbanner/:id/edit', function($id) {
    $controller = new BigbannerController();
    $controller->edit($id);
});

Router::post('/admin/bigbanner/:id', function($id) {
    $controller = new BigbannerController();
    $controller->update($id);
});

Router::post('/admin/bigbanner/:id/delete', function($id) {
    $controller = new BigbannerController();
    $controller->destroy($id);
});


// CRUD: BigBanner
// Gerado automaticamente em: 2026-02-14 19:14:57

Router::get('/admin/bigbanner', function() {
    $controller = new BigbannerController();
    $controller->index();
});

Router::get('/admin/bigbanner/create', function() {
    $controller = new BigbannerController();
    $controller->create();
});

Router::post('/admin/bigbanner', function() {
    $controller = new BigbannerController();
    $controller->store();
});

Router::get('/admin/bigbanner/:id/edit', function($id) {
    $controller = new BigbannerController();
    $controller->edit($id);
});

Router::post('/admin/bigbanner/:id', function($id) {
    $controller = new BigbannerController();
    $controller->update($id);
});

Router::post('/admin/bigbanner/:id/delete', function($id) {
    $controller = new BigbannerController();
    $controller->destroy($id);
});


// ================================================
// SEO - ROBOTS.TXT
// ================================================

Router::get('/admin/robots', function() {
    Auth::require();
    $controller = new RobotsController();
    $controller->index();
});

Router::post('/admin/robots/save', function() {
    Auth::require();
    $controller = new RobotsController();
    $controller->save();
});


// ================================================
// SEO - SITEMAP.XML
// ================================================

Router::get('/admin/sitemap', function() {
    Auth::require();
    $controller = new SitemapController();
    $controller->index();
});

Router::post('/admin/sitemap/generate', function() {
    Auth::require();
    $controller = new SitemapController();
    $controller->generate();
});


// ================================================
// SEO - CHECKER
// ================================================

Router::get('/admin/checker', function() {
    Auth::require();
    $controller = new CheckerController();
    $controller->index();
});


// ================================================
// UPTIME ROBOT - TEST
// ================================================

Router::get('/admin/uptime-test', function() {
    Auth::require();
    $controller = new UptimeTestController();
    $controller->index();
});


// ================================================
// UPTIME ROBOT - DASHBOARD
// ================================================

Router::get('/admin/uptime-robot', function() {
    Auth::require();
    $controller = new UptimeRobotController();
    $controller->index();
});

Router::post('/admin/uptime-robot/sync', function() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);
    $controller = new UptimeRobotController();
    $controller->sync();
});

// Search Console
Router::get('/admin/search-console', function() {
    Auth::require();
    $controller = new SearchConsoleController();
    $controller->index();
});

Router::post('/admin/search-console/sync', function() {
    Auth::require();
    Security::validateCSRF($_POST['csrf_token']);
    $controller = new SearchConsoleController();
    $controller->sync();
});

// Improvements
Router::get('/admin/improvements', function() {
    Auth::require();
    $controller = new ImprovementsController();
    $controller->index();
});
