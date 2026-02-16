<?php
/**
 * @doc Core
 * @title BaseController
 * @description
 * Classe base para todos os controllers.
 * Fornece funcionalidades comuns e elimina duplicação de código.
 *
 * Funcionalidades:
 * - Conexão com banco de dados
 * - Validação CSRF
 * - Autenticação (admin e member)
 * - Helpers para respostas JSON
 * - Flash messages
 *
 * @example
 * class MemberController extends BaseController {
 *     public function index() {
 *         $this->requireAuth();  // Requer login admin
 *         $members = $this->db->select('members');
 *         $this->render('members/index', ['members' => $members]);
 *     }
 * }
 */

abstract class BaseController {

    /**
     * Conexão com banco de dados
     * @var DatabaseInterface|null
     */
    protected $db = null;

    /**
     * Dados disponíveis para views
     * @var array
     */
    protected $viewData = [];

    /**
     * Construtor - inicializa conexão com DB se disponível
     */
    public function __construct() {
        // Conexão lazy - só conecta quando necessário
        // $this->db será null até chamar getDb()
    }

    /**
     * Obter conexão com banco de dados (lazy loading)
     *
     * @return DatabaseInterface
     */
    protected function getDb() {
        if ($this->db === null) {
            $this->db = DB::connect();
        }
        return $this->db;
    }

    /**
     * Alias para getDb() - compatibilidade
     *
     * @return DatabaseInterface
     */
    protected function db() {
        return $this->getDb();
    }

    // ================================================
    // AUTENTICAÇÃO
    // ================================================

    /**
     * Requer autenticação de admin
     * Redireciona para login se não autenticado
     *
     * @return void
     */
    protected function requireAuth() {
        Auth::require();
    }

    /**
     * Requer autenticação de member
     * Redireciona para login se não autenticado
     *
     * @return void
     */
    protected function requireMemberAuth() {
        MemberAuth::require();
    }

    /**
     * Verificar se admin está logado
     *
     * @return bool
     */
    protected function isAuthenticated() {
        return Auth::check();
    }

    /**
     * Verificar se member está logado
     *
     * @return bool
     */
    protected function isMemberAuthenticated() {
        return MemberAuth::check();
    }

    /**
     * Obter usuário admin logado
     *
     * @return array|null
     */
    protected function getUser() {
        return Auth::user();
    }

    /**
     * Obter member logado
     *
     * @return array|null
     */
    protected function getMember() {
        return MemberAuth::member();
    }

    // ================================================
    // VALIDAÇÃO
    // ================================================

    /**
     * Validar token CSRF
     * Lança exceção se inválido
     *
     * @param string|null $token Token a validar (default: $_POST['csrf_token'])
     * @throws Exception Se token inválido
     * @return void
     */
    protected function validateCSRF($token = null) {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        Security::validateCSRF($token);
    }

    /**
     * Sanitizar input
     *
     * @param string $input
     * @return string
     */
    protected function sanitize($input) {
        return Security::sanitize($input);
    }

    /**
     * Obter input POST sanitizado
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function input($key, $default = null) {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? $this->sanitize($value) : $value;
    }

    /**
     * Obter input GET sanitizado
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function query($key, $default = null) {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? $this->sanitize($value) : $value;
    }

    // ================================================
    // RESPOSTAS
    // ================================================

    /**
     * Redirecionar para URL
     *
     * @param string $url
     * @return void
     */
    protected function redirect($url) {
        Core::redirect($url);
    }

    /**
     * Resposta JSON
     *
     * @param mixed $data
     * @param int $status HTTP status code
     * @return void
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Resposta JSON de sucesso
     *
     * @param mixed $data
     * @param string $message
     * @return void
     */
    protected function jsonSuccess($data = null, $message = 'Success') {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Resposta JSON de erro
     *
     * @param string $message
     * @param int $status
     * @return void
     */
    protected function jsonError($message, $status = 400) {
        $this->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    // ================================================
    // FLASH MESSAGES
    // ================================================

    /**
     * Definir mensagem de sucesso
     *
     * @param string $message
     * @return void
     */
    protected function success($message) {
        $_SESSION['success'] = $message;
    }

    /**
     * Definir mensagem de erro
     *
     * @param string $message
     * @return void
     */
    protected function error($message) {
        $_SESSION['error'] = $message;
    }

    /**
     * Definir mensagem de aviso
     *
     * @param string $message
     * @return void
     */
    protected function warning($message) {
        $_SESSION['warning'] = $message;
    }

    // ================================================
    // VIEWS
    // ================================================

    /**
     * Renderizar view
     *
     * @param string $view Caminho da view (ex: 'members/index')
     * @param array $data Dados para a view
     * @return void
     */
    protected function render($view, $data = []) {
        // Mesclar dados globais com dados da view
        $data = array_merge($this->viewData, $data);

        // Extrair variáveis para o escopo da view
        extract($data);

        // Determinar caminho base
        $basePath = ROOT_PATH . 'admin/views/';

        // Tentar com extensão .php
        $viewPath = $basePath . $view . '.php';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            throw new Exception("View not found: {$view}");
        }
    }

    /**
     * Renderizar view pública
     *
     * @param string $view Caminho da view
     * @param array $data Dados para a view
     * @return void
     */
    protected function renderPublic($view, $data = []) {
        $data = array_merge($this->viewData, $data);
        extract($data);

        $viewPath = ROOT_PATH . 'frontend/' . $view . '.php';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            throw new Exception("Public view not found: {$view}");
        }
    }

    /**
     * Adicionar dados globais para views
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function share($key, $value) {
        $this->viewData[$key] = $value;
    }

    // ================================================
    // HELPERS
    // ================================================

    /**
     * Verificar se sistema de members está habilitado
     *
     * @return bool
     */
    protected function membersEnabled() {
        return Core::membersEnabled();
    }

    /**
     * Verificar se é requisição AJAX
     *
     * @return bool
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verificar se é requisição POST
     *
     * @return bool
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Verificar se é requisição GET
     *
     * @return bool
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Abortar com erro HTTP
     *
     * @param int $code HTTP status code
     * @param string $message
     * @return void
     */
    protected function abort($code, $message = '') {
        http_response_code($code);

        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        $message = $message ?: ($messages[$code] ?? 'Error');
        echo "<h1>{$code} - {$message}</h1>";
        exit;
    }
}
