<?php
/**
 * Response
 * Padroniza respostas HTTP
 *
 * Funcionalidades:
 * - Respostas JSON
 * - Redirects
 * - Downloads
 * - Headers customizados
 * - Status codes
 * - Views
 *
 * @example
 * // JSON
 * return Response::json(['success' => true, 'data' => $users]);
 *
 * // JSON com status
 * return Response::json(['error' => 'Not found'], 404);
 *
 * // Redirect
 * return Response::redirect('/login');
 *
 * // Download
 * return Response::download('/path/to/file.pdf', 'documento.pdf');
 *
 * // View
 * return Response::view('users/index', ['users' => $users]);
 */

class Response {

    /**
     * Status code atual
     */
    private $statusCode = 200;

    /**
     * Headers a enviar
     */
    private $headers = [];

    /**
     * Conteúdo da resposta
     */
    private $content = '';

    /**
     * Criar nova instância
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     */
    public function __construct($content = '', $status = 200, $headers = []) {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    // ===================
    // STATIC FACTORIES
    // ===================

    /**
     * Criar resposta JSON
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return self
     */
    public static function json($data, $status = 200, $headers = []) {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        $response = new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            $headers
        );

        return $response->send();
    }

    /**
     * Resposta JSON de sucesso
     *
     * @param mixed $data
     * @param string|null $message
     * @return self
     */
    public static function success($data = null, $message = null) {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return self::json($response);
    }

    /**
     * Resposta JSON de erro
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return self
     */
    public static function error($message, $status = 400, $errors = null) {
        $response = [
            'success' => false,
            'error' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return self::json($response, $status);
    }

    /**
     * Criar redirect
     *
     * @param string $url
     * @param int $status
     * @return void
     */
    public static function redirect($url, $status = 302) {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect para URL anterior (referer)
     *
     * @param string $fallback URL se não houver referer
     * @return void
     */
    public static function back($fallback = '/') {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        self::redirect($referer);
    }

    /**
     * Redirect com flash message
     *
     * @param string $url
     * @param string $message
     * @param string $type 'success' ou 'error'
     * @return void
     */
    public static function redirectWith($url, $message, $type = 'success') {
        $_SESSION[$type] = $message;
        self::redirect($url);
    }

    /**
     * Download de arquivo
     *
     * @param string $path Caminho do arquivo
     * @param string|null $name Nome para download
     * @param array $headers Headers adicionais
     * @return void
     */
    public static function download($path, $name = null, $headers = []) {
        if (!file_exists($path)) {
            self::error('Arquivo não encontrado', 404);
            return;
        }

        $name = $name ?? basename($path);
        $size = filesize($path);
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        // Headers padrão
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Headers customizados
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Enviar arquivo
        readfile($path);
        exit;
    }

    /**
     * Streaming de arquivo (inline, não download)
     *
     * @param string $path
     * @return void
     */
    public static function file($path) {
        if (!file_exists($path)) {
            self::error('Arquivo não encontrado', 404);
            return;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));

        readfile($path);
        exit;
    }

    /**
     * Renderizar view
     *
     * @param string $view Nome da view (sem extensão)
     * @param array $data Dados para a view
     * @param string|null $layout Layout a usar
     * @return void
     */
    public static function view($view, $data = [], $layout = null) {
        extract($data);

        $viewPath = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new Exception("View not found: {$view}");
        }

        if ($layout) {
            $layoutPath = (defined('ROOT_PATH') ? ROOT_PATH : '') . 'views/layouts/' . $layout . '.php';
            if (!file_exists($layoutPath)) {
                throw new Exception("Layout not found: {$layout}");
            }

            ob_start();
            require $viewPath;
            $content = ob_get_clean();

            require $layoutPath;
        } else {
            require $viewPath;
        }

        exit;
    }

    /**
     * Resposta vazia (204 No Content)
     *
     * @return void
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }

    /**
     * Resposta de texto plano
     *
     * @param string $text
     * @param int $status
     * @return self
     */
    public static function text($text, $status = 200) {
        $response = new self($text, $status, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);

        return $response->send();
    }

    /**
     * Resposta HTML
     *
     * @param string $html
     * @param int $status
     * @return self
     */
    public static function html($html, $status = 200) {
        $response = new self($html, $status, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);

        return $response->send();
    }

    /**
     * Resposta XML
     *
     * @param string $xml
     * @param int $status
     * @return self
     */
    public static function xml($xml, $status = 200) {
        $response = new self($xml, $status, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);

        return $response->send();
    }

    // ===================
    // HTTP STATUS HELPERS
    // ===================

    /**
     * 200 OK
     */
    public static function ok($data = null) {
        return self::json($data ?? ['success' => true], 200);
    }

    /**
     * 201 Created
     */
    public static function created($data = null) {
        return self::json($data ?? ['success' => true], 201);
    }

    /**
     * 400 Bad Request
     */
    public static function badRequest($message = 'Bad Request') {
        return self::error($message, 400);
    }

    /**
     * 401 Unauthorized
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }

    /**
     * 403 Forbidden
     */
    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 403);
    }

    /**
     * 404 Not Found
     */
    public static function notFound($message = 'Not Found') {
        return self::error($message, 404);
    }

    /**
     * 405 Method Not Allowed
     */
    public static function methodNotAllowed($message = 'Method Not Allowed') {
        return self::error($message, 405);
    }

    /**
     * 422 Unprocessable Entity (validação)
     */
    public static function validationError($errors, $message = 'Validation failed') {
        return self::error($message, 422, $errors);
    }

    /**
     * 429 Too Many Requests
     */
    public static function tooManyRequests($retryAfter = 60) {
        header("Retry-After: {$retryAfter}");
        return self::error('Too Many Requests', 429);
    }

    /**
     * 500 Internal Server Error
     */
    public static function serverError($message = 'Internal Server Error') {
        return self::error($message, 500);
    }

    // ===================
    // INSTANCE METHODS
    // ===================

    /**
     * Definir header
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Definir múltiplos headers
     *
     * @param array $headers
     * @return self
     */
    public function withHeaders($headers) {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Definir status code
     *
     * @param int $code
     * @return self
     */
    public function status($code) {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Definir conteúdo
     *
     * @param string $content
     * @return self
     */
    public function content($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Enviar resposta
     *
     * @return self
     */
    public function send() {
        // Status code
        http_response_code($this->statusCode);

        // Headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Content
        echo $this->content;

        return $this;
    }

    /**
     * Definir cookie
     *
     * @param string $name
     * @param string $value
     * @param int $minutes
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return self
     */
    public function cookie($name, $value, $minutes = 60, $path = '/', $domain = '', $secure = false, $httpOnly = true) {
        setcookie($name, $value, [
            'expires' => time() + ($minutes * 60),
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax'
        ]);

        return $this;
    }

    /**
     * Remover cookie
     *
     * @param string $name
     * @return self
     */
    public function forgetCookie($name) {
        setcookie($name, '', time() - 3600, '/');
        return $this;
    }

    // ===================
    // CACHE HEADERS
    // ===================

    /**
     * Definir headers de cache
     *
     * @param int $seconds
     * @return self
     */
    public function cache($seconds) {
        $this->headers['Cache-Control'] = "public, max-age={$seconds}";
        $this->headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT';
        return $this;
    }

    /**
     * Desabilitar cache
     *
     * @return self
     */
    public function noCache() {
        $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $this->headers['Pragma'] = 'no-cache';
        $this->headers['Expires'] = '0';
        return $this;
    }

    // ===================
    // CORS
    // ===================

    /**
     * Adicionar headers CORS
     *
     * @param string|array $origins Origens permitidas
     * @param array $methods Métodos permitidos
     * @param array $headers Headers permitidos
     * @return self
     */
    public function cors($origins = '*', $methods = ['GET', 'POST', 'PUT', 'DELETE'], $headers = ['Content-Type', 'Authorization']) {
        $origin = is_array($origins) ? implode(', ', $origins) : $origins;

        $this->headers['Access-Control-Allow-Origin'] = $origin;
        $this->headers['Access-Control-Allow-Methods'] = implode(', ', $methods);
        $this->headers['Access-Control-Allow-Headers'] = implode(', ', $headers);
        $this->headers['Access-Control-Max-Age'] = '86400';

        return $this;
    }

    /**
     * Responder OPTIONS para preflight CORS
     */
    public static function corsOptions($origins = '*') {
        $response = new self('', 204);
        $response->cors($origins)->send();
        exit;
    }
}
