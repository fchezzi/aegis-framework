<?php
/**
 * Cache
 * Sistema de cache unificado com múltiplos drivers
 *
 * Drivers disponíveis:
 * - apcu: APCu (mais rápido, memória compartilhada)
 * - file: Arquivos no disco (persistente) [padrão]
 * - session: Sessão PHP (por usuário)
 * - memory: Array em memória (apenas request atual)
 *
 * @example
 * // Configurar driver
 * Cache::setDriver('apcu');
 *
 * // Uso básico
 * Cache::set('user:123', $userData, 3600);
 * $user = Cache::get('user:123');
 *
 * // Remember pattern
 * $posts = Cache::remember('posts:recent', 300, function() {
 *     return $db->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT 10");
 * });
 *
 * // Tags (agrupamento)
 * Cache::tags(['users'])->set('user:123', $data);
 * Cache::flushTag('users'); // Limpa apenas cache de users
 */

class Cache {

    /**
     * Driver atual
     */
    private static $driver = null;

    /**
     * Cache em memória (L1 - request level)
     */
    private static $memory = [];

    /**
     * Configurações
     */
    private static $config = [
        'driver' => 'auto',
        'prefix' => 'aegis:',
        'default_ttl' => 3600
    ];

    /**
     * Tags para próxima operação
     */
    private static $currentTags = [];

    /**
     * Configurar cache
     */
    public static function configure($config) {
        self::$config = array_merge(self::$config, $config);
        self::$driver = null;
    }

    /**
     * Definir driver
     */
    public static function setDriver($driver) {
        self::$config['driver'] = $driver;
        self::$driver = $driver;
    }

    /**
     * Obter driver atual
     */
    public static function getDriver() {
        if (self::$driver === null) {
            self::$driver = self::detectDriver();
        }
        return self::$driver;
    }

    /**
     * Detectar melhor driver disponível
     */
    private static function detectDriver() {
        if (self::$config['driver'] !== 'auto') {
            return self::$config['driver'];
        }

        // Prioridade: apcu > file > session > memory
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            return 'apcu';
        }

        if (defined('CACHE_PATH') && is_writable(CACHE_PATH)) {
            return 'file';
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return 'session';
        }

        return 'memory';
    }

    /**
     * Obter caminho do cache
     */
    private static function getCachePath() {
        if (defined('CACHE_PATH')) {
            return CACHE_PATH;
        }
        return (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/') . 'storage/cache/';
    }

    /**
     * Gerar chave com prefixo
     */
    private static function key($key) {
        return self::$config['prefix'] . $key;
    }

    // ===================
    // API PRINCIPAL
    // ===================

    /**
     * Obter valor do cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        $fullKey = self::key($key);

        // L1: memória do request
        if (isset(self::$memory[$fullKey])) {
            $item = self::$memory[$fullKey];
            if ($item['expires'] === 0 || $item['expires'] > time()) {
                return $item['value'];
            }
            unset(self::$memory[$fullKey]);
        }

        // L2: driver
        $value = self::driverGet($fullKey);

        if ($value === null) {
            return $default;
        }

        // Guardar em L1
        self::$memory[$fullKey] = ['value' => $value, 'expires' => 0];

        return $value;
    }

    /**
     * Definir valor no cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl Segundos (padrão: 3600)
     * @return bool
     */
    public static function set($key, $value, $ttl = null) {
        $fullKey = self::key($key);
        $ttl = $ttl ?? self::$config['default_ttl'];

        // L1
        self::$memory[$fullKey] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0
        ];

        // Tags
        if (!empty(self::$currentTags)) {
            self::saveTaggedKey($fullKey, self::$currentTags);
            self::$currentTags = [];
        }

        return self::driverSet($fullKey, $value, $ttl);
    }

    /**
     * Verificar se existe
     */
    public static function has($key) {
        return self::get($key) !== null;
    }

    /**
     * Remover do cache
     */
    public static function delete($key) {
        $fullKey = self::key($key);
        unset(self::$memory[$fullKey]);
        return self::driverDelete($fullKey);
    }

    /**
     * Alias para delete
     */
    public static function forget($key) {
        return self::delete($key);
    }

    /**
     * Limpar todo o cache (alias: flush)
     */
    public static function clear() {
        self::$memory = [];
        return self::driverFlush();
    }

    /**
     * Alias para clear
     */
    public static function flush() {
        return self::clear();
    }

    /**
     * Remember: obter ou calcular e cachear
     *
     * @param string $key
     * @param int|callable $ttl
     * @param callable|null $callback
     * @return mixed
     */
    public static function remember($key, $ttl, $callback = null) {
        if (is_callable($ttl)) {
            $callback = $ttl;
            $ttl = self::$config['default_ttl'];
        }

        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Remember forever
     */
    public static function rememberForever($key, $callback) {
        return self::remember($key, 0, $callback);
    }

    /**
     * Incrementar valor
     */
    public static function increment($key, $amount = 1) {
        $value = (int) self::get($key, 0);
        $value += $amount;
        self::set($key, $value);
        return $value;
    }

    /**
     * Decrementar valor
     */
    public static function decrement($key, $amount = 1) {
        return self::increment($key, -$amount);
    }

    // ===================
    // TAGS
    // ===================

    /**
     * Definir tags para próxima operação
     */
    public static function tags($tags) {
        self::$currentTags = (array) $tags;
        return new static();
    }

    /**
     * Salvar key com tags
     */
    private static function saveTaggedKey($key, $tags) {
        foreach ($tags as $tag) {
            $tagKey = self::key("_tag:{$tag}");
            $keys = self::driverGet($tagKey) ?? [];
            $keys[$key] = true;
            self::driverSet($tagKey, $keys, 0);
        }
    }

    /**
     * Limpar cache por tag
     */
    public static function flushTag($tag) {
        $tagKey = self::key("_tag:{$tag}");
        $keys = self::driverGet($tagKey) ?? [];

        foreach (array_keys($keys) as $key) {
            unset(self::$memory[$key]);
            self::driverDelete($key);
        }

        self::driverDelete($tagKey);
        return true;
    }

    // ===================
    // DRIVER METHODS
    // ===================

    private static function driverGet($key) {
        switch (self::getDriver()) {
            case 'apcu':
                $value = apcu_fetch($key, $success);
                return $success ? $value : null;

            case 'file':
                return self::fileGet($key);

            case 'session':
                return self::sessionGet($key);

            case 'memory':
            default:
                return self::$memory[$key]['value'] ?? null;
        }
    }

    private static function driverSet($key, $value, $ttl) {
        switch (self::getDriver()) {
            case 'apcu':
                return apcu_store($key, $value, $ttl);

            case 'file':
                return self::fileSet($key, $value, $ttl);

            case 'session':
                return self::sessionSet($key, $value, $ttl);

            case 'memory':
            default:
                self::$memory[$key] = ['value' => $value, 'expires' => $ttl > 0 ? time() + $ttl : 0];
                return true;
        }
    }

    private static function driverDelete($key) {
        switch (self::getDriver()) {
            case 'apcu':
                return apcu_delete($key);

            case 'file':
                return self::fileDelete($key);

            case 'session':
                unset($_SESSION['_cache'][$key]);
                return true;

            case 'memory':
            default:
                unset(self::$memory[$key]);
                return true;
        }
    }

    private static function driverFlush() {
        switch (self::getDriver()) {
            case 'apcu':
                return apcu_clear_cache();

            case 'file':
                return self::fileClear();

            case 'session':
                $_SESSION['_cache'] = [];
                return true;

            case 'memory':
            default:
                self::$memory = [];
                return true;
        }
    }

    // ===================
    // FILE DRIVER
    // ===================

    private static function getFilename($key) {
        return self::getCachePath() . md5($key) . '.cache';
    }

    private static function fileGet($key) {
        $filename = self::getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $content = @json_decode(file_get_contents($filename), true);

        if (!$content || ($content['expires'] > 0 && $content['expires'] < time())) {
            @unlink($filename);
            return null;
        }

        return $content['data'];
    }

    private static function fileSet($key, $value, $ttl) {
        $cacheDir = self::getCachePath();

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $filename = self::getFilename($key);
        $content = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'data' => $value
        ];

        return @file_put_contents($filename, json_encode($content), LOCK_EX) !== false;
    }

    private static function fileDelete($key) {
        $filename = self::getFilename($key);
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        return true;
    }

    private static function fileClear() {
        $files = glob(self::getCachePath() . '*.cache');
        $count = 0;
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Limpar apenas cache expirado (garbage collection)
     */
    public static function clearExpired() {
        if (self::getDriver() !== 'file') {
            return 0;
        }

        $files = glob(self::getCachePath() . '*.cache');
        $count = 0;
        $now = time();

        foreach ($files as $file) {
            $content = @json_decode(file_get_contents($file), true);
            if ($content && $content['expires'] > 0 && $content['expires'] < $now) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    // ===================
    // SESSION DRIVER
    // ===================

    private static function sessionGet($key) {
        if (!isset($_SESSION['_cache'][$key])) {
            return null;
        }

        $item = $_SESSION['_cache'][$key];

        if ($item['expires'] > 0 && $item['expires'] < time()) {
            unset($_SESSION['_cache'][$key]);
            return null;
        }

        return $item['value'];
    }

    private static function sessionSet($key, $value, $ttl) {
        if (!isset($_SESSION['_cache'])) {
            $_SESSION['_cache'] = [];
        }

        $_SESSION['_cache'][$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0
        ];

        return true;
    }

    // ===================
    // UTILS
    // ===================

    /**
     * Obter estatísticas
     */
    public static function stats() {
        $stats = [
            'driver' => self::getDriver(),
            'memory_items' => count(self::$memory)
        ];

        switch (self::getDriver()) {
            case 'apcu':
                if (function_exists('apcu_cache_info')) {
                    $info = @apcu_cache_info(true);
                    $stats['hits'] = $info['num_hits'] ?? 0;
                    $stats['misses'] = $info['num_misses'] ?? 0;
                    $stats['entries'] = $info['num_entries'] ?? 0;
                }
                break;

            case 'file':
                $files = glob(self::getCachePath() . '*.cache');
                $stats['files'] = count($files);
                $stats['size'] = array_sum(array_map('filesize', $files ?: []));
                break;

            case 'session':
                $stats['session_items'] = count($_SESSION['_cache'] ?? []);
                break;
        }

        return $stats;
    }
}
