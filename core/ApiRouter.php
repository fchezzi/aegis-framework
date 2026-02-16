<?php
/**
 * ApiRouter
 * Router especializado para APIs com versionamento
 *
 * Funcionalidades:
 * - Versionamento de API (/api/v1/, /api/v2/)
 * - Prefixo automático
 * - Middlewares padrão para todas as rotas
 * - Resource routes (CRUD completo)
 * - Rate limiting automático
 *
 * @example
 * // Definir rotas da API v1
 * ApiRouter::version('v1', function() {
 *     ApiRouter::get('/users', 'UsersApiController@index');
 *     ApiRouter::post('/users', 'UsersApiController@store');
 *     ApiRouter::get('/users/{id}', 'UsersApiController@show');
 *     ApiRouter::put('/users/{id}', 'UsersApiController@update');
 *     ApiRouter::delete('/users/{id}', 'UsersApiController@destroy');
 *
 *     // Ou usar resource para criar todas as rotas CRUD
 *     ApiRouter::resource('/posts', 'PostsApiController');
 * });
 *
 * // API v2 com diferentes controladores
 * ApiRouter::version('v2', function() {
 *     ApiRouter::resource('/users', 'V2\UsersApiController');
 * });
 */

class ApiRouter {

    /**
     * Versão atual sendo registrada
     */
    private static $currentVersion = null;

    /**
     * Prefixo base da API
     */
    private static $prefix = '/api';

    /**
     * Middlewares padrão para todas as rotas de API
     */
    private static $defaultMiddleware = ['throttle'];

    /**
     * Namespace base para controllers de API
     */
    private static $namespace = 'api/controllers';

    /**
     * Versões registradas
     */
    private static $versions = [];

    /**
     * Definir rotas para uma versão específica
     *
     * @param string $version Versão (v1, v2, etc.)
     * @param callable $callback Função com definições de rotas
     * @param array $options Opções adicionais
     */
    public static function version($version, callable $callback, array $options = []) {
        self::$currentVersion = $version;

        // Registrar versão
        self::$versions[$version] = array_merge([
            'deprecated' => false,
            'sunset' => null, // Data de desativação
            'middleware' => []
        ], $options);

        // Executar callback para registrar rotas
        $callback();

        self::$currentVersion = null;
    }

    /**
     * Obter prefixo completo com versão
     *
     * @return string
     */
    private static function getPrefix() {
        $prefix = self::$prefix;

        if (self::$currentVersion) {
            $prefix .= '/' . self::$currentVersion;
        }

        return $prefix;
    }

    /**
     * Obter middlewares para a versão atual
     *
     * @return array
     */
    private static function getMiddleware() {
        $middleware = self::$defaultMiddleware;

        if (self::$currentVersion && isset(self::$versions[self::$currentVersion])) {
            $versionMiddleware = self::$versions[self::$currentVersion]['middleware'];
            $middleware = array_merge($middleware, $versionMiddleware);
        }

        return $middleware;
    }

    // ===================
    // HTTP METHODS
    // ===================

    /**
     * Registrar rota GET
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function get($uri, $action) {
        return self::addRoute('GET', $uri, $action);
    }

    /**
     * Registrar rota POST
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function post($uri, $action) {
        return self::addRoute('POST', $uri, $action);
    }

    /**
     * Registrar rota PUT
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function put($uri, $action) {
        return self::addRoute('PUT', $uri, $action);
    }

    /**
     * Registrar rota PATCH
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function patch($uri, $action) {
        return self::addRoute('PATCH', $uri, $action);
    }

    /**
     * Registrar rota DELETE
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function delete($uri, $action) {
        return self::addRoute('DELETE', $uri, $action);
    }

    /**
     * Registrar rota OPTIONS (para CORS preflight)
     *
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    public static function options($uri, $action) {
        return self::addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Adicionar rota ao Router principal
     *
     * @param string $method
     * @param string $uri
     * @param string $action
     * @return mixed
     */
    private static function addRoute($method, $uri, $action) {
        $fullUri = self::getPrefix() . $uri;
        $middleware = self::getMiddleware();

        // Adicionar headers de versão deprecated se necessário
        if (self::$currentVersion && self::isDeprecated(self::$currentVersion)) {
            $middleware[] = self::deprecationMiddleware(self::$currentVersion);
        }

        // Registrar no Router principal
        $route = Router::$method($fullUri, $action);

        // Aplicar middlewares
        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        return $route;
    }

    // ===================
    // RESOURCE ROUTES
    // ===================

    /**
     * Registrar rotas de recurso completo (CRUD)
     *
     * Cria automaticamente:
     * - GET    /resource         -> index
     * - POST   /resource         -> store
     * - GET    /resource/{id}    -> show
     * - PUT    /resource/{id}    -> update
     * - PATCH  /resource/{id}    -> update
     * - DELETE /resource/{id}    -> destroy
     *
     * @param string $uri
     * @param string $controller
     * @param array $options
     */
    public static function resource($uri, $controller, array $options = []) {
        $only = $options['only'] ?? ['index', 'store', 'show', 'update', 'destroy'];
        $except = $options['except'] ?? [];
        $parameter = $options['parameter'] ?? 'id';

        // Filtrar ações
        $actions = array_diff($only, $except);

        foreach ($actions as $action) {
            switch ($action) {
                case 'index':
                    self::get($uri, "{$controller}@index");
                    break;

                case 'store':
                    self::post($uri, "{$controller}@store");
                    break;

                case 'show':
                    self::get("{$uri}/{{$parameter}}", "{$controller}@show");
                    break;

                case 'update':
                    self::put("{$uri}/{{$parameter}}", "{$controller}@update");
                    self::patch("{$uri}/{{$parameter}}", "{$controller}@update");
                    break;

                case 'destroy':
                    self::delete("{$uri}/{{$parameter}}", "{$controller}@destroy");
                    break;
            }
        }
    }

    /**
     * Registrar apenas rotas de leitura (index, show)
     *
     * @param string $uri
     * @param string $controller
     * @param array $options
     */
    public static function apiResource($uri, $controller, array $options = []) {
        $options['only'] = $options['only'] ?? ['index', 'show'];
        self::resource($uri, $controller, $options);
    }

    // ===================
    // VERSIONING
    // ===================

    /**
     * Verificar se versão está deprecated
     *
     * @param string $version
     * @return bool
     */
    public static function isDeprecated($version) {
        return self::$versions[$version]['deprecated'] ?? false;
    }

    /**
     * Obter data de sunset de uma versão
     *
     * @param string $version
     * @return string|null
     */
    public static function getSunsetDate($version) {
        return self::$versions[$version]['sunset'] ?? null;
    }

    /**
     * Middleware para adicionar headers de deprecation
     *
     * @param string $version
     * @return callable
     */
    private static function deprecationMiddleware($version) {
        return function($next) use ($version) {
            // Header de deprecação
            header('Deprecation: true');

            // Header de sunset se definido
            $sunset = self::getSunsetDate($version);
            if ($sunset) {
                header("Sunset: {$sunset}");
            }

            // Header informativo
            header("X-API-Deprecated: This API version ({$version}) is deprecated. Please upgrade.");

            return $next();
        };
    }

    /**
     * Listar versões disponíveis
     *
     * @return array
     */
    public static function getVersions() {
        return self::$versions;
    }

    /**
     * Obter versão mais recente não-deprecated
     *
     * @return string|null
     */
    public static function getLatestVersion() {
        $versions = array_keys(self::$versions);
        rsort($versions, SORT_NATURAL);

        foreach ($versions as $version) {
            if (!self::isDeprecated($version)) {
                return $version;
            }
        }

        return $versions[0] ?? null;
    }

    // ===================
    // CONFIGURATION
    // ===================

    /**
     * Definir prefixo base
     *
     * @param string $prefix
     */
    public static function setPrefix($prefix) {
        self::$prefix = '/' . trim($prefix, '/');
    }

    /**
     * Definir middlewares padrão
     *
     * @param array $middleware
     */
    public static function setDefaultMiddleware(array $middleware) {
        self::$defaultMiddleware = $middleware;
    }

    /**
     * Adicionar middleware padrão
     *
     * @param string $middleware
     */
    public static function addDefaultMiddleware($middleware) {
        self::$defaultMiddleware[] = $middleware;
    }

    /**
     * Definir namespace de controllers
     *
     * @param string $namespace
     */
    public static function setNamespace($namespace) {
        self::$namespace = $namespace;
    }

    // ===================
    // GROUPS
    // ===================

    /**
     * Agrupar rotas com prefixo adicional
     *
     * @param string $prefix
     * @param callable $callback
     */
    public static function group($prefix, callable $callback) {
        $oldPrefix = self::$prefix;
        self::$prefix = self::getPrefix() . '/' . trim($prefix, '/');

        $callback();

        self::$prefix = $oldPrefix;
    }

    /**
     * Agrupar rotas que requerem autenticação
     *
     * @param callable $callback
     */
    public static function auth(callable $callback) {
        $oldMiddleware = self::$defaultMiddleware;
        self::$defaultMiddleware = array_merge(self::$defaultMiddleware, ['api.auth']);

        $callback();

        self::$defaultMiddleware = $oldMiddleware;
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Resposta JSON padrão para listagem de versões
     * Útil para endpoint /api/versions
     */
    public static function versionsEndpoint() {
        $versions = [];

        foreach (self::$versions as $version => $info) {
            $versions[] = [
                'version' => $version,
                'url' => self::$prefix . '/' . $version,
                'deprecated' => $info['deprecated'],
                'sunset' => $info['sunset'],
                'current' => $version === self::getLatestVersion()
            ];
        }

        return [
            'success' => true,
            'data' => [
                'versions' => $versions,
                'current' => self::getLatestVersion(),
                'base_url' => self::$prefix
            ]
        ];
    }
}
