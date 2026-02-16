<?php
/**
 * Router
 * Sistema de rotas dinâmicas com suporte a middlewares
 *
 * @example
 * // Registrar middleware
 * Router::middleware('auth', function($next) {
 *     Auth::require();
 *     return $next();
 * });
 *
 * // Usar em rota individual
 * Router::get('/admin/users', 'UserController@index')->middleware('auth');
 *
 * // Usar em grupo
 * Router::group(['middleware' => 'auth', 'prefix' => '/admin'], function() {
 *     Router::get('/users', 'UserController@index');
 *     Router::get('/posts', 'PostController@index');
 * });
 */

class Router {

    private static $routes = [];
    private static $middlewares = [];
    private static $basePath = null;
    private static $groupStack = [];
    private static $currentRoute = null;

    /**
     * Detectar base path (subpasta)
     */
    public static function getBasePath() {
        if (self::$basePath === null) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            self::$basePath = ($basePath !== '/') ? $basePath : '';
        }
        return self::$basePath;
    }

    /**
     * Registrar middleware global
     *
     * @param string $name Nome do middleware
     * @param callable $callback Função do middleware
     */
    public static function middleware($name, $callback) {
        self::$middlewares[$name] = $callback;
    }

    /**
     * Criar grupo de rotas
     *
     * @param array $attributes ['prefix' => '', 'middleware' => '']
     * @param callable $callback
     */
    public static function group($attributes, $callback) {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    /**
     * Registrar rota GET
     *
     * @return Route
     */
    public static function get($path, $handler) {
        return self::addRoute('GET', $path, $handler);
    }

    /**
     * Registrar rota POST
     *
     * @return Route
     */
    public static function post($path, $handler) {
        return self::addRoute('POST', $path, $handler);
    }

    /**
     * Registrar rota PUT
     *
     * @return Route
     */
    public static function put($path, $handler) {
        return self::addRoute('PUT', $path, $handler);
    }

    /**
     * Registrar rota DELETE
     *
     * @return Route
     */
    public static function delete($path, $handler) {
        return self::addRoute('DELETE', $path, $handler);
    }

    /**
     * Adicionar rota
     *
     * @return Route
     */
    private static function addRoute($method, $path, $handler) {
        // Aplicar prefixo do grupo
        $prefix = self::getGroupPrefix();
        $fullPath = $prefix . $path;

        // Aplicar middlewares do grupo
        $middlewares = self::getGroupMiddlewares();

        $route = new Route($method, $fullPath, $handler, $middlewares);
        self::$routes[] = $route;
        self::$currentRoute = $route;

        return $route;
    }

    /**
     * Obter prefixo acumulado dos grupos
     */
    private static function getGroupPrefix() {
        $prefix = '';
        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= $group['prefix'];
            }
        }
        return $prefix;
    }

    /**
     * Obter middlewares acumulados dos grupos
     */
    private static function getGroupMiddlewares() {
        $middlewares = [];
        foreach (self::$groupStack as $group) {
            if (isset($group['middleware'])) {
                $mw = $group['middleware'];
                if (is_string($mw)) {
                    $middlewares[] = $mw;
                } elseif (is_array($mw)) {
                    $middlewares = array_merge($middlewares, $mw);
                }
            }
        }
        return $middlewares;
    }

    /**
     * Executar router
     */
    public static function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remover base path se houver
        $basePath = self::getBasePath();
        if ($basePath !== '') {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        $requestUri = '/' . trim($requestUri, '/');

        // Buscar rota correspondente
        foreach (self::$routes as $route) {
            if ($route->getMethod() !== $requestMethod) {
                continue;
            }

            $pattern = self::convertToRegex($route->getPath());

            if (preg_match($pattern, $requestUri, $matches)) {
                // Remover match completo
                array_shift($matches);

                // Executar middlewares e handler
                return self::executeRoute($route, $matches);
            }
        }

        // 404
        http_response_code(404);
        echo "404 - Not Found";
    }

    /**
     * Executar rota com middlewares
     */
    private static function executeRoute(Route $route, $params = []) {
        $middlewares = $route->getMiddlewares();

        // Criar pipeline de middlewares
        $pipeline = self::createMiddlewarePipeline($middlewares, function() use ($route, $params) {
            return self::executeHandler($route->getHandler(), $params);
        });

        return $pipeline();
    }

    /**
     * Criar pipeline de middlewares
     */
    private static function createMiddlewarePipeline($middlewares, $finalHandler) {
        $pipeline = $finalHandler;

        // Processar middlewares em ordem reversa
        foreach (array_reverse($middlewares) as $middlewareName) {
            if (!isset(self::$middlewares[$middlewareName])) {
                continue; // Middleware não registrado, pular
            }

            $middleware = self::$middlewares[$middlewareName];
            $next = $pipeline;

            $pipeline = function() use ($middleware, $next) {
                return $middleware($next);
            };
        }

        return $pipeline;
    }

    /**
     * Converter path para regex
     */
    private static function convertToRegex($path) {
        // Converter :param para regex
        $pattern = preg_replace('/\/:([a-zA-Z0-9_]+)/', '/([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Executar handler
     */
    private static function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        // Controller@method
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);

            // Tentar carregar do autoloader primeiro
            if (!class_exists($controller)) {
                // Fallback: procurar em diretórios conhecidos
                $paths = [
                    ROOT_PATH . 'admin/controllers/' . $controller . '.php',
                    ROOT_PATH . 'public/controllers/' . $controller . '.php',
                    ROOT_PATH . 'core/' . $controller . '.php'
                ];

                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
            }

            if (!class_exists($controller)) {
                http_response_code(500);
                die("Controller {$controller} not found");
            }

            $instance = new $controller();

            if (!method_exists($instance, $method)) {
                http_response_code(500);
                die("Method {$method} not found in {$controller}");
            }

            return call_user_func_array([$instance, $method], $params);
        }
    }

    /**
     * Gerar URL com base path automático
     */
    public static function url($path = '') {
        return self::getBasePath() . $path;
    }

    /**
     * Obter middleware registrado
     */
    public static function getMiddleware($name) {
        return self::$middlewares[$name] ?? null;
    }

    /**
     * Listar todos os middlewares registrados
     */
    public static function getMiddlewares() {
        return self::$middlewares;
    }
}

/**
 * Route
 * Representa uma rota individual
 */
class Route {

    private $method;
    private $path;
    private $handler;
    private $middlewares = [];

    public function __construct($method, $path, $handler, $middlewares = []) {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    /**
     * Adicionar middleware à rota
     *
     * @param string|array $middleware
     * @return $this
     */
    public function middleware($middleware) {
        if (is_string($middleware)) {
            $this->middlewares[] = $middleware;
        } elseif (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        }
        return $this;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getPath() {
        return $this->path;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getMiddlewares() {
        return $this->middlewares;
    }
}
