<?php
/**
 * Simple Cache System
 * Cache em memória (sessão) para reduzir chamadas ao banco
 */

class SimpleCache {

    /**
     * Obter valor do cache
     *
     * @param string $key Chave do cache
     * @return mixed|null Valor ou null se não existir/expirou
     */
    public static function get($key) {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['cache'][$key])) {
            return null;
        }

        $item = $_SESSION['cache'][$key];

        // Verificar se expirou
        if (isset($item['expires_at']) && $item['expires_at'] < time()) {
            unset($_SESSION['cache'][$key]);
            return null;
        }

        return $item['value'] ?? null;
    }

    /**
     * Definir valor no cache
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a cachear
     * @param int $ttl Tempo de vida em segundos (padrão: 5 minutos)
     */
    public static function set($key, $value, $ttl = 300) {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['cache'])) {
            $_SESSION['cache'] = [];
        }

        $_SESSION['cache'][$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
    }

    /**
     * Verificar se existe no cache
     *
     * @param string $key Chave do cache
     * @return bool
     */
    public static function has($key) {
        return self::get($key) !== null;
    }

    /**
     * Deletar item do cache
     *
     * @param string $key Chave do cache
     */
    public static function delete($key) {
        if (!isset($_SESSION)) {
            session_start();
        }

        unset($_SESSION['cache'][$key]);
    }

    /**
     * Limpar todo o cache
     */
    public static function flush() {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['cache'] = [];
    }

    /**
     * Limpar cache por padrão (ex: 'palpites_*')
     *
     * @param string $pattern Padrão com * como wildcard
     */
    public static function flushPattern($pattern) {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['cache'])) {
            return;
        }

        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

        foreach (array_keys($_SESSION['cache']) as $key) {
            if (preg_match($regex, $key)) {
                unset($_SESSION['cache'][$key]);
            }
        }
    }

    /**
     * Lembrar: Obter do cache ou executar callback e cachear
     *
     * @param string $key Chave do cache
     * @param callable $callback Função a executar se não estiver no cache
     * @param int $ttl Tempo de vida em segundos
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = 300) {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Obter estatísticas do cache
     *
     * @return array
     */
    public static function stats() {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['cache'])) {
            return [
                'total_items' => 0,
                'expired_items' => 0,
                'valid_items' => 0,
                'total_size' => 0
            ];
        }

        $total = count($_SESSION['cache']);
        $expired = 0;
        $now = time();

        foreach ($_SESSION['cache'] as $item) {
            if (isset($item['expires_at']) && $item['expires_at'] < $now) {
                $expired++;
            }
        }

        return [
            'total_items' => $total,
            'expired_items' => $expired,
            'valid_items' => $total - $expired,
            'total_size' => strlen(serialize($_SESSION['cache']))
        ];
    }
}
