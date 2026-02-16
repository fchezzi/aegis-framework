<?php
/**
 * Middleware
 * Registra middlewares padrão do sistema
 *
 * Middlewares disponíveis:
 * - auth: Requer autenticação de admin
 * - member: Requer autenticação de member
 * - guest: Apenas visitantes (não logados)
 * - csrf: Valida token CSRF
 * - permission: Verifica permissão de página/módulo
 *
 * @example
 * // No bootstrap (index.php)
 * Middleware::register();
 *
 * // Nas rotas
 * Router::get('/admin/users', 'UserController@index')->middleware('auth');
 */

class Middleware {

    /**
     * Registrar todos os middlewares padrão
     */
    public static function register() {
        self::registerAuthMiddleware();
        self::registerMemberMiddleware();
        self::registerGuestMiddleware();
        self::registerCsrfMiddleware();
        self::registerPermissionMiddleware();
        self::registerApiAuthMiddleware();
        self::registerThrottleMiddleware();
    }

    /**
     * Middleware de autenticação de admin
     */
    private static function registerAuthMiddleware() {
        Router::middleware('auth', function($next) {
            if (!Auth::check()) {
                $_SESSION['error'] = 'Você precisa fazer login para acessar esta página';
                Core::redirect('/admin/login');
                return;
            }
            return $next();
        });
    }

    /**
     * Middleware de autenticação de member
     */
    private static function registerMemberMiddleware() {
        Router::middleware('member', function($next) {
            if (!Core::membersEnabled()) {
                Core::redirect('/');
                return;
            }

            if (!MemberAuth::check()) {
                $_SESSION['error'] = 'Você precisa fazer login para acessar esta página';
                Core::redirect('/login');
                return;
            }

            // Inicializar PermissionManager para o member
            $member = MemberAuth::member();
            PermissionManager::initialize($member['id']);

            return $next();
        });
    }

    /**
     * Middleware para visitantes (não logados)
     */
    private static function registerGuestMiddleware() {
        Router::middleware('guest', function($next) {
            if (Auth::check()) {
                Core::redirect('/admin/dashboard');
                return;
            }

            if (MemberAuth::check()) {
                Core::redirect('/home');
                return;
            }

            return $next();
        });
    }

    /**
     * Middleware de validação CSRF
     * Aceita token via $_POST, JSON body ou headers
     */
    private static function registerCsrfMiddleware() {
        Router::middleware('csrf', function($next) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $token = $_POST['csrf_token'] ?? '';
                try {
                    Security::validateCSRF($token);
                } catch (Exception $e) {
                    http_response_code(403);
                    echo "CSRF token inválido";
                    return;
                }
            }
            return $next();
        });
    }

    /**
     * Middleware de permissão dinâmica
     * Uso: ->middleware('permission:page:slug') ou ->middleware('permission:module:name')
     */
    private static function registerPermissionMiddleware() {
        Router::middleware('permission', function($next) {
            // Este middleware precisa de parâmetros adicionais
            // Por ora, apenas verifica se member está autenticado
            if (!MemberAuth::check()) {
                http_response_code(403);
                echo "Acesso negado";
                return;
            }
            return $next();
        });
    }

    /**
     * Criar middleware customizado para verificar permissão específica
     *
     * @param string $type 'page' ou 'module'
     * @param string $identifier ID da página ou nome do módulo
     * @return callable
     */
    public static function checkPermission($type, $identifier) {
        return function($next) use ($type, $identifier) {
            if (!MemberAuth::check()) {
                http_response_code(403);
                echo "Acesso negado";
                return;
            }

            $member = MemberAuth::member();
            PermissionManager::initialize($member['id']);

            $hasPermission = false;

            if ($type === 'page') {
                $hasPermission = PermissionManager::canAccessPage($member['id'], $identifier);
            } elseif ($type === 'module') {
                $hasPermission = PermissionManager::canAccessModule($member['id'], $identifier);
            }

            if (!$hasPermission) {
                http_response_code(403);
                echo "Você não tem permissão para acessar este recurso";
                return;
            }

            return $next();
        };
    }

    // ===================
    // API MIDDLEWARES
    // ===================

    /**
     * Middleware de autenticação JWT para APIs
     *
     * Uso: ->middleware('api.auth')
     *
     * Verifica token Bearer no header Authorization
     * Armazena payload em $_REQUEST['jwt_user'] para uso posterior
     */
    private static function registerApiAuthMiddleware() {
        Router::middleware('api.auth', function($next) {
            // Obter token do header
            $token = Request::bearerToken();

            if (!$token) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de autenticação não fornecido'
                ]);
                return;
            }

            try {
                $payload = JWT::decode($token);
                // Armazenar usuário no request para uso posterior
                $_REQUEST['jwt_user'] = $payload;
                $_REQUEST['jwt_token'] = $token;
                return $next();
            } catch (Exception $e) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                return;
            }
        });
    }

    /**
     * Middleware de Rate Limiting (Throttle) para APIs
     *
     * Uso: ->middleware('throttle') para 60 req/min
     * Ou criar custom: Middleware::throttle(100, 60) para 100 req/min
     *
     * Headers retornados:
     * - X-RateLimit-Limit: Limite total
     * - X-RateLimit-Remaining: Requisições restantes
     * - Retry-After: Segundos para retry (quando bloqueado)
     */
    private static function registerThrottleMiddleware() {
        Router::middleware('throttle', function($next) {
            $maxRequests = 60;
            $perSeconds = 60;

            // Identificar cliente (JWT user ID ou IP)
            $key = $_REQUEST['jwt_user']['sub']
                ?? $_REQUEST['jwt_user']['id']
                ?? Request::ip();

            $limiter = new RateLimiter('api_throttle');

            // Verificar limite
            if (!$limiter->check($key, $maxRequests, $perSeconds)) {
                $retryAfter = $limiter->retryAfter($key);

                http_response_code(429);
                header('Content-Type: application/json');
                header("X-RateLimit-Limit: {$maxRequests}");
                header("X-RateLimit-Remaining: 0");
                header("Retry-After: {$retryAfter}");

                echo json_encode([
                    'success' => false,
                    'error' => "Muitas requisições. Tente novamente em {$retryAfter} segundos."
                ]);
                return;
            }

            // Incrementar contador
            $limiter->increment($key, $perSeconds);

            // Calcular restantes
            $remaining = $limiter->remaining($key, $maxRequests, $perSeconds);

            // Headers de rate limit
            header("X-RateLimit-Limit: {$maxRequests}");
            header("X-RateLimit-Remaining: {$remaining}");

            return $next();
        });
    }

    /**
     * Criar middleware de throttle customizado
     *
     * @param int $maxRequests Requisições máximas
     * @param int $perSeconds Janela de tempo em segundos
     * @return callable
     *
     * @example
     * Router::get('/api/search', 'SearchApi@index')
     *     ->middleware(Middleware::throttle(30, 60)); // 30 req/min
     */
    public static function throttle($maxRequests = 60, $perSeconds = 60) {
        return function($next) use ($maxRequests, $perSeconds) {
            $key = $_REQUEST['jwt_user']['sub']
                ?? $_REQUEST['jwt_user']['id']
                ?? Request::ip();

            $limiter = new RateLimiter('api_throttle');

            if (!$limiter->check($key, $maxRequests, $perSeconds)) {
                $retryAfter = $limiter->retryAfter($key);

                http_response_code(429);
                header('Content-Type: application/json');
                header("X-RateLimit-Limit: {$maxRequests}");
                header("X-RateLimit-Remaining: 0");
                header("Retry-After: {$retryAfter}");

                echo json_encode([
                    'success' => false,
                    'error' => "Muitas requisições. Tente novamente em {$retryAfter} segundos."
                ]);
                return;
            }

            $limiter->increment($key, $perSeconds);
            $remaining = $limiter->remaining($key, $maxRequests, $perSeconds);

            header("X-RateLimit-Limit: {$maxRequests}");
            header("X-RateLimit-Remaining: {$remaining}");

            return $next();
        };
    }

    /**
     * Middleware para permitir apenas determinados roles JWT
     *
     * @param array|string $roles Roles permitidos
     * @return callable
     *
     * @example
     * Router::delete('/api/users/{id}', 'UsersApi@destroy')
     *     ->middleware(['api.auth', Middleware::role('admin')]);
     */
    public static function role($roles) {
        $roles = (array) $roles;

        return function($next) use ($roles) {
            $user = $_REQUEST['jwt_user'] ?? null;

            if (!$user) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Autenticação necessária'
                ]);
                return;
            }

            $userRole = $user['role'] ?? $user['tipo'] ?? 'user';

            if (!in_array($userRole, $roles)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Acesso negado. Role insuficiente.'
                ]);
                return;
            }

            return $next();
        };
    }

    /**
     * Middleware para verificar scopes JWT
     *
     * @param array|string $scopes Scopes necessários
     * @return callable
     *
     * @example
     * Router::post('/api/posts', 'PostsApi@store')
     *     ->middleware(['api.auth', Middleware::scope('posts:write')]);
     */
    public static function scope($scopes) {
        $scopes = (array) $scopes;

        return function($next) use ($scopes) {
            $user = $_REQUEST['jwt_user'] ?? null;

            if (!$user) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Autenticação necessária'
                ]);
                return;
            }

            $userScopes = $user['scopes'] ?? [];

            foreach ($scopes as $scope) {
                if (!in_array($scope, $userScopes)) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => "Scope necessário: {$scope}"
                    ]);
                    return;
                }
            }

            return $next();
        };
    }
}
