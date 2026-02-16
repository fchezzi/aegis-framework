<?php
/**
 * PageController
 * Gerencia exibição de páginas públicas e de membros
 *
 * Responsabilidades:
 * - Renderizar páginas do sistema
 * - Verificar permissões de acesso
 * - Preparar dados para templates
 */

class PageController extends BaseController {

    /**
     * Exibir página home de membros
     */
    public function home() {
        if (!$this->membersEnabled()) {
            $this->redirect('/');
            return;
        }

        // Requer autenticação
        $this->requireMemberAuth();

        $member = $this->getMember();

        // Inicializar PermissionManager (pre-fetch)
        PermissionManager::initialize($member['id']);

        // Buscar menu filtrado
        $filteredMenu = $this->getFilteredMenu($member['id']);

        // Carregar página editável
        $pageFile = ROOT_PATH . 'frontend/pages/home.php';
        if (file_exists($pageFile)) {
            // Definir contexto para home (sempre members quando autenticado)
            $pageContext = 'members';
            $pageTitle = 'Home';
            $pageSlug = 'home';
            require $pageFile;
        } else {
            $this->renderDefaultHome($member);
        }
    }

    /**
     * Exibir página por slug
     *
     * @param string $slug
     */
    public function show($slug) {
        // 1. Verificar se é página do AEGIS
        $pages = $this->db()->select('pages', ['slug' => $slug, 'ativo' => 1]);

        if (empty($pages)) {
            // Não é página → verificar se é categoria do blog
            if ($this->tryBlogCategory($slug)) {
                return;
            }

            // 404
            $this->render404();
            return;
        }

        $page = $pages[0];

        // 2. Sistema SEM members → páginas são públicas
        if (!$this->membersEnabled()) {
            $this->renderPage($slug, $page, null);
            return;
        }

        // 3. Página pública → renderizar sem login
        if (($page['is_public'] ?? 0) == 1) {
            $this->renderPage($slug, $page, null);
            return;
        }

        // 4. Página privada → verificar login
        if (!$this->isMemberAuthenticated()) {
            $this->error("Você precisa fazer login para acessar esta página");
            $_SESSION['redirect_after_login'] = url('/' . $slug);
            $this->redirect('/login');
            return;
        }

        $member = $this->getMember();

        // Inicializar PermissionManager (pre-fetch)
        PermissionManager::initialize($member['id']);

        // 5. Verificar permissão usando PermissionManager (O(1), zero queries)
        if (!PermissionManager::canAccessPage($member['id'], $page['id'])) {
            $this->render403();
            return;
        }

        // 6. Tem permissão → renderizar
        $this->renderPage($slug, $page, $member);
    }

    /**
     * Obter menu filtrado por permissões
     *
     * @param string $memberId
     * @return array
     */
    private function getFilteredMenu($memberId) {
        $menuItems = $this->db()->select('menu_items', ['visible' => 1], 'ordem ASC');

        // Usar MenuPermissionChecker que já foi otimizado
        return MenuPermissionChecker::filter($menuItems, $memberId);
    }

    /**
     * Tentar renderizar como categoria do blog
     *
     * @param string $slug
     * @return bool True se era categoria do blog
     */
    private function tryBlogCategory($slug) {
        $installedModules = explode(',', defined('INSTALLED_MODULES') ? INSTALLED_MODULES : '');

        if (!in_array('blog', $installedModules)) {
            return false;
        }

        $categorias = $this->db()->query("SELECT * FROM tbl_blog_categorias WHERE slug = ? AND ativo = ?", [$slug, 1]);

        if (empty($categorias)) {
            return false;
        }

        // É categoria do blog
        require_once ROOT_PATH . 'modules/blog/controllers/PublicBlogController.php';
        $controller = new PublicBlogController();
        $controller->categoria($slug);

        return true;
    }

    /**
     * Renderizar página
     *
     * @param string $slug
     * @param array $page
     * @param array|null $member
     */
    private function renderPage($slug, $pageData, $member) {
        $pageFile = ROOT_PATH . 'frontend/pages/' . $slug . '.php';

        if (file_exists($pageFile)) {
            // Variáveis disponíveis para a página
            $pageSlug = $slug;
            $pageTitle = $pageData['title'];
            $pageContext = $pageData['context'] ?? 'public'; // public, members ou admin
            $page = $pageData; // Disponibilizar para SEO::render() no _head.php
            require_once $pageFile;
        } else {
            // Usar template dashboard
            $pageSlug = $slug;
            $pageTitle = $pageData['title'];
            $pageContext = $pageData['context'] ?? 'public'; // public, members ou admin
            $page = $pageData; // Disponibilizar para SEO::render() no _head.php
            require_once ROOT_PATH . 'frontend/templates/dashboard.php';
        }
    }

    /**
     * Renderizar home padrão (fallback)
     *
     * @param array $member
     */
    private function renderDefaultHome($member) {
        echo "<h1>Bem-vindo, " . htmlspecialchars($member['name']) . "</h1>";
        echo "<p>Crie o arquivo <code>/frontend/pages/home.php</code> para personalizar esta página.</p>";
        echo '<a href="' . url('/home') . '">Home</a> | ';
        echo '<a href="' . url('/logout') . '">Sair</a>';
    }

    /**
     * Renderizar página 404
     */
    private function render404() {
        $this->abort(404, 'Página não encontrada');
    }

    /**
     * Renderizar página 403
     */
    private function render403() {
        http_response_code(403);
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'><head><meta charset='UTF-8'>";
        echo "<title>Acesso Negado</title>";
        echo "<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;";
        echo "display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;";
        echo "background:#f5f5f5;text-align:center;}";
        echo "h1{font-size:48px;color:#ef4444;margin-bottom:10px;}";
        echo "p{color:#666;margin-bottom:20px;}";
        echo "a{color:#667eea;text-decoration:none;padding:10px 20px;border-radius:5px;";
        echo "background:#667eea;color:#fff;display:inline-block;margin:5px;}";
        echo "a:hover{background:#5568d3;}</style></head><body>";
        echo "<div><h1>Acesso Negado</h1>";
        echo "<p>Você não tem permissão para acessar esta página.</p>";
        echo "<a href='" . url('/logout') . "'>Fazer login com outro usuário</a>";
        echo "<a href='" . url('/home') . "' style='background:#6b7280;'>Voltar para Home</a></div>";
        echo "</body></html>";
        exit;
    }
}
