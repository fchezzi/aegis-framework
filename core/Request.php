<?php
/**
 * Request
 * Encapsula dados da requisição HTTP
 *
 * Fornece acesso seguro e conveniente a:
 * - Dados GET, POST, FILES
 * - Headers HTTP
 * - Informações do servidor
 * - Cookies
 * - JSON body
 *
 * @example
 * // Obter input
 * $email = Request::input('email');
 * $page = Request::get('page', 1);
 * $data = Request::post('data');
 *
 * // Verificar método
 * if (Request::isPost()) { ... }
 * if (Request::isAjax()) { ... }
 *
 * // Headers
 * $token = Request::header('Authorization');
 *
 * // JSON body
 * $json = Request::json();
 *
 * // Arquivos
 * $file = Request::file('avatar');
 * if (Request::hasFile('avatar')) { ... }
 */

class Request {

    /**
     * Instância singleton
     */
    private static $instance = null;

    /**
     * Cache do JSON body
     */
    private static $jsonBody = null;

    /**
     * Cache de headers
     */
    private static $headers = null;

    /**
     * Obter instância singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ===================
    // INPUT METHODS
    // ===================

    /**
     * Obter valor de GET, POST ou JSON
     *
     * @param string|null $key Chave ou null para todos
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public static function input($key = null, $default = null) {
        // Se não passou key, retorna tudo merged
        if ($key === null) {
            return array_merge($_GET, $_POST, self::json() ?? []);
        }

        // Prioridade: POST > JSON > GET
        if (isset($_POST[$key])) {
            return self::sanitize($_POST[$key]);
        }

        $json = self::json();
        if ($json !== null && isset($json[$key])) {
            return $json[$key];
        }

        if (isset($_GET[$key])) {
            return self::sanitize($_GET[$key]);
        }

        return $default;
    }

    /**
     * Obter valor apenas do GET
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key = null, $default = null) {
        if ($key === null) {
            return array_map([self::class, 'sanitize'], $_GET);
        }

        return isset($_GET[$key]) ? self::sanitize($_GET[$key]) : $default;
    }

    /**
     * Obter valor apenas do POST
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function post($key = null, $default = null) {
        if ($key === null) {
            return array_map([self::class, 'sanitize'], $_POST);
        }

        return isset($_POST[$key]) ? self::sanitize($_POST[$key]) : $default;
    }

    /**
     * Obter corpo JSON da requisição
     *
     * @param string|null $key Chave específica ou null para tudo
     * @param mixed $default
     * @return mixed
     */
    public static function json($key = null, $default = null) {
        if (self::$jsonBody === null) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                self::$jsonBody = json_decode($raw, true) ?? [];
            } else {
                self::$jsonBody = [];
            }
        }

        if ($key === null) {
            return self::$jsonBody;
        }

        return self::$jsonBody[$key] ?? $default;
    }

    /**
     * Verificar se input existe
     *
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return self::input($key) !== null;
    }

    /**
     * Verificar se input existe e não está vazio
     *
     * @param string $key
     * @return bool
     */
    public static function filled($key) {
        $value = self::input($key);
        return $value !== null && $value !== '';
    }

    /**
     * Obter apenas chaves específicas
     *
     * @param array $keys
     * @return array
     */
    public static function only($keys) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::input($key);
        }
        return $result;
    }

    /**
     * Obter todos exceto chaves específicas
     *
     * @param array $keys
     * @return array
     */
    public static function except($keys) {
        $all = self::input();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Sanitizar valor
     */
    private static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // ===================
    // FILES
    // ===================

    /**
     * Obter arquivo uploaded
     *
     * @param string $key
     * @return array|null
     */
    public static function file($key) {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $_FILES[$key];
    }

    /**
     * Verificar se arquivo foi enviado
     *
     * @param string $key
     * @return bool
     */
    public static function hasFile($key) {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obter todos os arquivos
     *
     * @return array
     */
    public static function files() {
        return $_FILES;
    }

    // ===================
    // HEADERS
    // ===================

    /**
     * Obter header HTTP
     *
     * @param string|null $key Nome do header (case-insensitive)
     * @param mixed $default
     * @return mixed
     */
    public static function header($key = null, $default = null) {
        if (self::$headers === null) {
            self::$headers = self::parseHeaders();
        }

        if ($key === null) {
            return self::$headers;
        }

        // Normalizar key
        $key = strtolower(str_replace('_', '-', $key));

        return self::$headers[$key] ?? $default;
    }

    /**
     * Parse headers do servidor
     */
    private static function parseHeaders() {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        // Headers especiais
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Obter bearer token do header Authorization
     *
     * @return string|null
     */
    public static function bearerToken() {
        $auth = self::header('authorization');

        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // ===================
    // COOKIES
    // ===================

    /**
     * Obter cookie
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function cookie($key = null, $default = null) {
        if ($key === null) {
            return $_COOKIE;
        }

        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Verificar se cookie existe
     *
     * @param string $key
     * @return bool
     */
    public static function hasCookie($key) {
        return isset($_COOKIE[$key]);
    }

    // ===================
    // REQUEST INFO
    // ===================

    /**
     * Obter método HTTP
     *
     * @return string GET, POST, PUT, DELETE, etc
     */
    public static function method() {
        // Suporte a method override via _method ou X-HTTP-Method-Override
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST') {
            $override = $_POST['_method'] ?? self::header('X-HTTP-Method-Override');
            if ($override) {
                $method = strtoupper($override);
            }
        }

        return $method;
    }

    /**
     * Verificar se é GET
     */
    public static function isGet() {
        return self::method() === 'GET';
    }

    /**
     * Verificar se é POST
     */
    public static function isPost() {
        return self::method() === 'POST';
    }

    /**
     * Verificar se é PUT
     */
    public static function isPut() {
        return self::method() === 'PUT';
    }

    /**
     * Verificar se é DELETE
     */
    public static function isDelete() {
        return self::method() === 'DELETE';
    }

    /**
     * Verificar se é PATCH
     */
    public static function isPatch() {
        return self::method() === 'PATCH';
    }

    /**
     * Verificar se é requisição AJAX
     *
     * @return bool
     */
    public static function isAjax() {
        return self::header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Verificar se espera resposta JSON
     *
     * @return bool
     */
    public static function wantsJson() {
        $accept = self::header('accept', '');
        return stripos($accept, 'application/json') !== false;
    }

    /**
     * Verificar se é HTTPS
     *
     * @return bool
     */
    public static function isSecure() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }

    /**
     * Obter URI da requisição
     *
     * @return string
     */
    public static function uri() {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    /**
     * Obter URL completa
     *
     * @return string
     */
    public static function url() {
        $scheme = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Obter URL base (sem path)
     *
     * @return string
     */
    public static function baseUrl() {
        $scheme = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return "{$scheme}://{$host}";
    }

    /**
     * Obter query string
     *
     * @return string
     */
    public static function queryString() {
        return $_SERVER['QUERY_STRING'] ?? '';
    }

    /**
     * Obter IP do cliente
     *
     * @return string
     */
    public static function ip() {
        // Considerar proxies
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For pode ter múltiplos IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Obter User Agent
     *
     * @return string
     */
    public static function userAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Obter Referer
     *
     * @return string|null
     */
    public static function referer() {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Verificar se vem de URL específica
     *
     * @param string $url
     * @return bool
     */
    public static function isRefererFrom($url) {
        $referer = self::referer();
        return $referer && strpos($referer, $url) !== false;
    }

    // ===================
    // SEGMENTS
    // ===================

    /**
     * Obter segmentos da URI
     *
     * @return array
     */
    public static function segments() {
        $uri = trim(self::uri(), '/');
        return $uri ? explode('/', $uri) : [];
    }

    /**
     * Obter segmento específico
     *
     * @param int $index (1-based)
     * @param mixed $default
     * @return mixed
     */
    public static function segment($index, $default = null) {
        $segments = self::segments();
        return $segments[$index - 1] ?? $default;
    }
}
