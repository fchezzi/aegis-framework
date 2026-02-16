<?php
/**
 * RateLimit
 * Proteção contra brute force e abuso
 */

class RateLimit {

    private static $storage = [];

    /**
     * Verificar rate limit
     *
     * @param string $key Identificador único (IP, email, etc)
     * @param int $maxAttempts Tentativas máximas
     * @param int $windowSeconds Janela de tempo em segundos
     * @return bool True se permitido, False se bloqueado
     */
    public static function check($key, $maxAttempts = 5, $windowSeconds = 60) {
        $storageKey = self::getStorageKey($key);

        // Limpar tentativas antigas
        self::cleanup($storageKey, $windowSeconds);

        // Contar tentativas na janela
        $attempts = self::getAttempts($storageKey);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        // Registrar tentativa
        self::addAttempt($storageKey);

        return true;
    }

    /**
     * Resetar contador
     */
    public static function reset($key) {
        $storageKey = self::getStorageKey($key);

        if (isset($_SESSION[$storageKey])) {
            unset($_SESSION[$storageKey]);
        }
    }

    /**
     * Bloquear temporariamente
     */
    public static function block($key, $seconds = 300) {
        $storageKey = self::getStorageKey($key) . '_blocked';
        $_SESSION[$storageKey] = time() + $seconds;
    }

    /**
     * Verificar se está bloqueado
     */
    public static function isBlocked($key) {
        $storageKey = self::getStorageKey($key) . '_blocked';

        if (isset($_SESSION[$storageKey])) {
            if (time() < $_SESSION[$storageKey]) {
                return true;
            }
            unset($_SESSION[$storageKey]);
        }

        return false;
    }

    /**
     * Tempo restante de bloqueio
     */
    public static function getBlockedTime($key) {
        $storageKey = self::getStorageKey($key) . '_blocked';

        if (isset($_SESSION[$storageKey])) {
            $remaining = $_SESSION[$storageKey] - time();
            return max(0, $remaining);
        }

        return 0;
    }

    /**
     * Limpar tentativas antigas
     */
    private static function cleanup($storageKey, $windowSeconds) {
        if (!isset($_SESSION[$storageKey])) {
            return;
        }

        $cutoff = time() - $windowSeconds;
        $_SESSION[$storageKey] = array_filter(
            $_SESSION[$storageKey],
            function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            }
        );
    }

    /**
     * Contar tentativas
     */
    private static function getAttempts($storageKey) {
        if (!isset($_SESSION[$storageKey])) {
            return 0;
        }

        return count($_SESSION[$storageKey]);
    }

    /**
     * Adicionar tentativa
     */
    private static function addAttempt($storageKey) {
        if (!isset($_SESSION[$storageKey])) {
            $_SESSION[$storageKey] = [];
        }

        $_SESSION[$storageKey][] = time();
    }

    /**
     * Gerar chave de storage
     */
    private static function getStorageKey($key) {
        return 'ratelimit_' . md5($key);
    }

    /**
     * Middleware para rotas
     */
    public static function middleware($key, $maxAttempts = 5, $windowSeconds = 60) {
        if (self::isBlocked($key)) {
            $remaining = self::getBlockedTime($key);
            http_response_code(429);
            die(json_encode([
                'error' => 'Too many requests',
                'retry_after' => $remaining
            ]));
        }

        if (!self::check($key, $maxAttempts, $windowSeconds)) {
            self::block($key, 300); // 5 minutos
            http_response_code(429);
            die(json_encode([
                'error' => 'Rate limit exceeded',
                'retry_after' => 300
            ]));
        }
    }
}
