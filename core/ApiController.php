<?php
/**
 * ApiController
 * Controller base para APIs REST
 *
 * Funcionalidades:
 * - Respostas JSON padronizadas
 * - Autenticação JWT
 * - Rate limiting
 * - Validação de input
 * - CORS handling
 *
 * @example
 * class UsersApiController extends ApiController {
 *
 *     protected $middleware = ['auth:api'];
 *
 *     public function index() {
 *         $users = DB::table('users')->get();
 *         return $this->success($users);
 *     }
 *
 *     public function store() {
 *         $this->validate([
 *             'name' => 'required|min:3',
 *             'email' => 'required|email|unique:users'
 *         ]);
 *
 *         $user = DB::table('users')->insert($this->only(['name', 'email']));
 *         return $this->created($user);
 *     }
 * }
 */

class ApiController {

    /**
     * Dados do usuário autenticado via JWT
     */
    protected $user = null;

    /**
     * Dados validados
     */
    protected $validated = [];

    /**
     * Construtor
     */
    public function __construct() {
        // Headers CORS
        $this->setCorsHeaders();

        // Handle preflight
        if (Request::method() === 'OPTIONS') {
            Response::corsOptions();
        }
    }

    /**
     * Definir headers CORS
     */
    protected function setCorsHeaders() {
        $allowedOrigins = defined('API_CORS_ORIGINS') ? API_CORS_ORIGINS : '*';

        header('Access-Control-Allow-Origin: ' . $allowedOrigins);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json; charset=utf-8');
    }

    // ===================
    // AUTHENTICATION
    // ===================

    /**
     * Autenticar via JWT
     *
     * @return bool
     */
    protected function authenticate() {
        $token = Request::bearerToken();

        if (!$token) {
            return false;
        }

        try {
            $payload = JWT::decode($token);
            $this->user = $payload;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Requerer autenticação
     */
    protected function requireAuth() {
        if (!$this->authenticate()) {
            $this->unauthorized('Token inválido ou expirado');
        }
    }

    /**
     * Obter usuário autenticado
     *
     * @return array|null
     */
    protected function user() {
        return $this->user;
    }

    /**
     * Obter ID do usuário autenticado
     *
     * @return string|null
     */
    protected function userId() {
        return $this->user['sub'] ?? $this->user['id'] ?? null;
    }

    // ===================
    // INPUT
    // ===================

    /**
     * Obter input da requisição
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function input($key = null, $default = null) {
        return Request::input($key, $default);
    }

    /**
     * Obter apenas campos específicos
     *
     * @param array $keys
     * @return array
     */
    protected function only($keys) {
        return Request::only($keys);
    }

    /**
     * Obter todos exceto campos específicos
     *
     * @param array $keys
     * @return array
     */
    protected function except($keys) {
        return Request::except($keys);
    }

    /**
     * Validar input
     *
     * @param array $rules
     * @param array $messages
     * @return array Dados validados
     */
    protected function validate($rules, $messages = []) {
        $validator = Validator::make(Request::input(), $rules, $messages);

        if ($validator->fails()) {
            $this->validationError($validator->errors());
        }

        $this->validated = $validator->validated();
        return $this->validated;
    }

    /**
     * Obter dados validados
     *
     * @return array
     */
    protected function validated() {
        return $this->validated;
    }

    // ===================
    // RESPONSES - SUCCESS
    // ===================

    /**
     * Resposta de sucesso genérica
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     */
    protected function success($data = null, $message = null, $status = 200) {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $status);
    }

    /**
     * Resposta 200 OK
     *
     * @param mixed $data
     * @param string|null $message
     */
    protected function ok($data = null, $message = null) {
        $this->success($data, $message, 200);
    }

    /**
     * Resposta 201 Created
     *
     * @param mixed $data
     * @param string|null $message
     */
    protected function created($data = null, $message = 'Recurso criado com sucesso') {
        $this->success($data, $message, 201);
    }

    /**
     * Resposta 204 No Content
     */
    protected function noContent() {
        http_response_code(204);
        exit;
    }

    // ===================
    // RESPONSES - ERROR
    // ===================

    /**
     * Resposta de erro genérica
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     */
    protected function error($message, $status = 400, $errors = null) {
        $response = [
            'success' => false,
            'error' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->json($response, $status);
    }

    /**
     * 400 Bad Request
     *
     * @param string $message
     */
    protected function badRequest($message = 'Requisição inválida') {
        $this->error($message, 400);
    }

    /**
     * 401 Unauthorized
     *
     * @param string $message
     */
    protected function unauthorized($message = 'Não autorizado') {
        $this->error($message, 401);
    }

    /**
     * 403 Forbidden
     *
     * @param string $message
     */
    protected function forbidden($message = 'Acesso negado') {
        $this->error($message, 403);
    }

    /**
     * 404 Not Found
     *
     * @param string $message
     */
    protected function notFound($message = 'Recurso não encontrado') {
        $this->error($message, 404);
    }

    /**
     * 422 Validation Error
     *
     * @param array $errors
     * @param string $message
     */
    protected function validationError($errors, $message = 'Erro de validação') {
        $this->error($message, 422, $errors);
    }

    /**
     * 429 Too Many Requests
     *
     * @param int $retryAfter
     */
    protected function tooManyRequests($retryAfter = 60) {
        header("Retry-After: {$retryAfter}");
        $this->error('Muitas requisições. Tente novamente em ' . $retryAfter . ' segundos.', 429);
    }

    /**
     * 500 Internal Server Error
     *
     * @param string $message
     */
    protected function serverError($message = 'Erro interno do servidor') {
        $this->error($message, 500);
    }

    // ===================
    // PAGINATION
    // ===================

    /**
     * Resposta paginada
     *
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $perPage
     */
    protected function paginated($data, $total, $page = 1, $perPage = 15) {
        $lastPage = ceil($total / $perPage);

        $response = [
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'last_page' => (int) $lastPage,
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total)
            ],
            'links' => [
                'first' => $this->paginationUrl(1, $perPage),
                'last' => $this->paginationUrl($lastPage, $perPage),
                'prev' => $page > 1 ? $this->paginationUrl($page - 1, $perPage) : null,
                'next' => $page < $lastPage ? $this->paginationUrl($page + 1, $perPage) : null
            ]
        ];

        $this->json($response);
    }

    /**
     * Gerar URL de paginação
     */
    protected function paginationUrl($page, $perPage) {
        $baseUrl = Request::url();
        $baseUrl = strtok($baseUrl, '?');
        return "{$baseUrl}?page={$page}&per_page={$perPage}";
    }

    /**
     * Obter parâmetros de paginação da request
     *
     * @return array [page, perPage]
     */
    protected function getPagination() {
        $page = max(1, (int) Request::get('page', 1));
        $perPage = min(100, max(1, (int) Request::get('per_page', 15)));

        return [$page, $perPage];
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Enviar resposta JSON
     *
     * @param mixed $data
     * @param int $status
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Rate limiting para API
     *
     * @param int $maxRequests
     * @param int $perSeconds
     * @param string|null $key
     */
    protected function rateLimit($maxRequests = 60, $perSeconds = 60, $key = null) {
        $key = $key ?? $this->userId() ?? Request::ip();

        if (!RateLimiter::check('api', $key, $maxRequests, $perSeconds)) {
            $retryAfter = RateLimiter::retryAfter('api', $key);
            $this->tooManyRequests($retryAfter);
        }

        RateLimiter::increment('api', $key, $perSeconds);

        // Headers de rate limit
        $remaining = (new RateLimiter('api'))->remaining($key, $maxRequests, $perSeconds);
        header("X-RateLimit-Limit: {$maxRequests}");
        header("X-RateLimit-Remaining: {$remaining}");
    }

    /**
     * Log de ação na API
     *
     * @param string $action
     * @param array $context
     */
    protected function logAction($action, $context = []) {
        $context = array_merge([
            'user_id' => $this->userId(),
            'ip' => Request::ip(),
            'method' => Request::method(),
            'uri' => Request::uri()
        ], $context);

        Logger::logInfo("API: {$action}", $context);
    }
}
