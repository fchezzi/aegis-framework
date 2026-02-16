<?php
/**
 * RateLimiter
 * Proteção contra brute force e abuso de API
 *
 * Funcionalidades:
 * - Limite por IP
 * - Limite por usuário
 * - Limite por ação específica
 * - Sliding window algorithm
 * - Suporte a múltiplos backends (cache, database, file)
 *
 * @example
 * // No controller de login
 * $limiter = new RateLimiter('login');
 *
 * if ($limiter->tooManyAttempts($ip, 5, 60)) {
 *     // Bloqueado: 5 tentativas em 60 segundos
 *     $retryAfter = $limiter->availableIn($ip);
 *     return response()->json(['error' => "Aguarde {$retryAfter}s"], 429);
 * }
 *
 * $limiter->hit($ip); // Registrar tentativa
 *
 * // Após login bem sucedido
 * $limiter->clear($ip);
 *
 * @example
 * // Middleware global
 * Router::middleware('throttle', function($next) {
 *     if (RateLimiter::check('api', $_SERVER['REMOTE_ADDR'], 60, 60)) {
 *         return $next();
 *     }
 *     http_response_code(429);
 *     header('Retry-After: ' . RateLimiter::retryAfter('api', $_SERVER['REMOTE_ADDR']));
 *     echo json_encode(['error' => 'Too Many Requests']);
 * });
 */

class RateLimiter {

    /**
     * Nome/identificador do limiter
     */
    private $name;

    /**
     * Backend de storage
     */
    private static $storage = null;

    /**
     * Prefixo para chaves
     */
    private const PREFIX = 'ratelimit:';

    /**
     * Construtor
     *
     * @param string $name Identificador único (ex: 'login', 'api', 'password_reset')
     */
    public function __construct($name = 'default') {
        $this->name = $name;
        self::initStorage();
    }

    /**
     * Inicializar storage
     */
    private static function initStorage() {
        if (self::$storage !== null) {
            return;
        }

        // Prioridade: APCu > Session > File
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            self::$storage = 'apcu';
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            self::$storage = 'session';
        } else {
            self::$storage = 'file';
        }
    }

    /**
     * Verificar se excedeu limite (API estática)
     *
     * @param string $name Nome do limiter
     * @param string $key Identificador (IP, user_id, etc)
     * @param int $maxAttempts Máximo de tentativas
     * @param int $decaySeconds Janela de tempo em segundos
     * @return bool True se dentro do limite
     */
    public static function check($name, $key, $maxAttempts, $decaySeconds) {
        $limiter = new self($name);
        return !$limiter->tooManyAttempts($key, $maxAttempts, $decaySeconds);
    }

    /**
     * Registrar tentativa (API estática)
     *
     * @param string $name
     * @param string $key
     * @param int $decaySeconds
     */
    public static function increment($name, $key, $decaySeconds = 60) {
        $limiter = new self($name);
        $limiter->hit($key, $decaySeconds);
    }

    /**
     * Obter tempo até retry (API estática)
     *
     * @param string $name
     * @param string $key
     * @return int Segundos
     */
    public static function retryAfter($name, $key) {
        $limiter = new self($name);
        return $limiter->availableIn($key);
    }

    /**
     * Verificar se há muitas tentativas
     *
     * @param string $key Identificador único
     * @param int $maxAttempts Máximo de tentativas permitidas
     * @param int $decaySeconds Janela de tempo
     * @return bool True se bloqueado
     */
    public function tooManyAttempts($key, $maxAttempts, $decaySeconds) {
        $attempts = $this->attempts($key, $decaySeconds);
        return $attempts >= $maxAttempts;
    }

    /**
     * Obter número de tentativas atuais
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int
     */
    public function attempts($key, $decaySeconds = 60) {
        $cacheKey = $this->getCacheKey($key);
        $data = $this->get($cacheKey);

        if ($data === null) {
            return 0;
        }

        // Limpar tentativas antigas (sliding window)
        $now = time();
        $windowStart = $now - $decaySeconds;

        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Atualizar storage com dados limpos
        $this->set($cacheKey, $data, $decaySeconds);

        return count($data);
    }

    /**
     * Registrar tentativa
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int Número total de tentativas
     */
    public function hit($key, $decaySeconds = 60) {
        $cacheKey = $this->getCacheKey($key);
        $data = $this->get($cacheKey) ?? [];

        // Adicionar timestamp atual
        $data[] = time();

        // Limpar tentativas antigas
        $windowStart = time() - $decaySeconds;
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        $this->set($cacheKey, array_values($data), $decaySeconds);

        return count($data);
    }

    /**
     * Limpar tentativas
     *
     * @param string $key
     */
    public function clear($key) {
        $cacheKey = $this->getCacheKey($key);
        $this->delete($cacheKey);
    }

    /**
     * Obter segundos até poder tentar novamente
     *
     * @param string $key
     * @return int
     */
    public function availableIn($key) {
        $cacheKey = $this->getCacheKey($key);
        $data = $this->get($cacheKey);

        if (empty($data)) {
            return 0;
        }

        // Pegar o timestamp mais antigo dentro da janela
        $oldest = min($data);
        $availableAt = $oldest + 60; // Assumir janela de 60s como padrão

        return max(0, $availableAt - time());
    }

    /**
     * Obter tentativas restantes
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return int
     */
    public function remaining($key, $maxAttempts, $decaySeconds = 60) {
        $attempts = $this->attempts($key, $decaySeconds);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Gerar chave de cache
     */
    private function getCacheKey($key) {
        return self::PREFIX . $this->name . ':' . md5($key);
    }

    // ===================
    // STORAGE METHODS
    // ===================

    private function get($key) {
        switch (self::$storage) {
            case 'apcu':
                $value = apcu_fetch($key, $success);
                return $success ? $value : null;

            case 'session':
                return $_SESSION[$key] ?? null;

            case 'file':
                return $this->fileGet($key);

            default:
                return null;
        }
    }

    private function set($key, $value, $ttl) {
        switch (self::$storage) {
            case 'apcu':
                apcu_store($key, $value, $ttl);
                break;

            case 'session':
                $_SESSION[$key] = $value;
                break;

            case 'file':
                $this->fileSet($key, $value, $ttl);
                break;
        }
    }

    private function delete($key) {
        switch (self::$storage) {
            case 'apcu':
                apcu_delete($key);
                break;

            case 'session':
                unset($_SESSION[$key]);
                break;

            case 'file':
                $this->fileDelete($key);
                break;
        }
    }

    // ===================
    // FILE STORAGE
    // ===================

    private function getFilePath($key) {
        $dir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/') . 'storage/cache/ratelimit/';

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . md5($key) . '.json';
    }

    private function fileGet($key) {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        // Verificar expiração
        if (isset($data['expires']) && $data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'] ?? null;
    }

    private function fileSet($key, $value, $ttl) {
        $file = $this->getFilePath($key);

        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private function fileDelete($key) {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    // ===================
    // MIDDLEWARE HELPER
    // ===================

    /**
     * Registrar middleware de throttle no Router
     *
     * @param int $maxAttempts
     * @param int $decaySeconds
     */
    public static function registerMiddleware($maxAttempts = 60, $decaySeconds = 60) {
        Router::middleware('throttle', function($next) use ($maxAttempts, $decaySeconds) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            if (!self::check('http', $ip, $maxAttempts, $decaySeconds)) {
                http_response_code(429);
                header('Retry-After: ' . self::retryAfter('http', $ip));

                if (self::isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Too Many Requests',
                        'retry_after' => self::retryAfter('http', $ip)
                    ]);
                } else {
                    echo '<h1>429 - Too Many Requests</h1>';
                    echo '<p>Você fez muitas requisições. Tente novamente em breve.</p>';
                }
                return;
            }

            self::increment('http', $ip, $decaySeconds);
            return $next();
        });
    }

    /**
     * Verificar se é AJAX
     */
    private static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    // ===================
    // LOGIN PROTECTION
    // ===================

    /**
     * Proteção específica para login
     *
     * @param string $identifier Email ou username
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return array ['allowed' => bool, 'attempts' => int, 'remaining' => int, 'retry_after' => int]
     */
    public static function loginAttempt($identifier, $maxAttempts = 5, $decaySeconds = 300) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Verificar por IP E por identifier separadamente
        $ipLimiter = new self('login_ip');
        $userLimiter = new self('login_user');

        $ipAttempts = $ipLimiter->attempts($ip, $decaySeconds);
        $userAttempts = $userLimiter->attempts($identifier, $decaySeconds);

        $maxAttemptsReached = $ipAttempts >= $maxAttempts || $userAttempts >= $maxAttempts;

        return [
            'allowed' => !$maxAttemptsReached,
            'ip_attempts' => $ipAttempts,
            'user_attempts' => $userAttempts,
            'remaining' => max(0, $maxAttempts - max($ipAttempts, $userAttempts)),
            'retry_after' => max($ipLimiter->availableIn($ip), $userLimiter->availableIn($identifier))
        ];
    }

    /**
     * Registrar tentativa de login falha
     */
    public static function loginFailed($identifier, $decaySeconds = 300) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $ipLimiter = new self('login_ip');
        $userLimiter = new self('login_user');

        $ipLimiter->hit($ip, $decaySeconds);
        $userLimiter->hit($identifier, $decaySeconds);
    }

    /**
     * Limpar tentativas após login bem sucedido
     */
    public static function loginSuccess($identifier) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $ipLimiter = new self('login_ip');
        $userLimiter = new self('login_user');

        $ipLimiter->clear($ip);
        $userLimiter->clear($identifier);
    }
}
