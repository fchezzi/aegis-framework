<?php
/**
 * Helper Functions
 * Funções globais utilitárias do AEGIS
 *
 * Carregue no bootstrap:
 * require_once ROOT_PATH . 'core/helpers.php';
 */

// ===================
// ARRAYS
// ===================

if (!function_exists('array_get')) {
    /**
     * Obter valor de array usando notação de ponto
     *
     * @example array_get($data, 'user.name', 'default')
     */
    function array_get($array, $key, $default = null) {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('array_set')) {
    /**
     * Definir valor em array usando notação de ponto
     *
     * @example array_set($data, 'user.name', 'John')
     */
    function array_set(&$array, $key, $value) {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}

if (!function_exists('array_only')) {
    /**
     * Retornar apenas chaves específicas de um array
     */
    function array_only($array, $keys) {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

if (!function_exists('array_except')) {
    /**
     * Retornar array exceto chaves específicas
     */
    function array_except($array, $keys) {
        return array_diff_key($array, array_flip((array) $keys));
    }
}

if (!function_exists('array_first')) {
    /**
     * Retornar primeiro elemento que passa no teste
     */
    function array_first($array, callable $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($array) ? $default : reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('array_last')) {
    /**
     * Retornar último elemento que passa no teste
     */
    function array_last($array, callable $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($array) ? $default : end($array);
        }

        return array_first(array_reverse($array, true), $callback, $default);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * Extrair valores de uma chave específica
     */
    function array_pluck($array, $key, $indexKey = null) {
        $results = [];

        foreach ($array as $item) {
            $value = is_object($item) ? $item->$key : $item[$key];

            if ($indexKey) {
                $index = is_object($item) ? $item->$indexKey : $item[$indexKey];
                $results[$index] = $value;
            } else {
                $results[] = $value;
            }
        }

        return $results;
    }
}

// ===================
// STRINGS
// ===================

if (!function_exists('str_contains')) {
    /**
     * Verificar se string contém substring
     */
    function str_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * Verificar se string começa com
     */
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * Verificar se string termina com
     */
    function str_ends_with($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_limit')) {
    /**
     * Truncar string
     */
    function str_limit($value, $limit = 100, $end = '...') {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }
}

if (!function_exists('str_slug')) {
    /**
     * Gerar slug a partir de string
     */
    function str_slug($string, $separator = '-') {
        // Remover acentos
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        // Minúsculas
        $string = strtolower($string);
        // Remover caracteres especiais
        $string = preg_replace('/[^a-z0-9\-]/', $separator, $string);
        // Remover separadores duplicados
        $string = preg_replace('/' . preg_quote($separator) . '+/', $separator, $string);
        // Trim separadores
        return trim($string, $separator);
    }
}

if (!function_exists('str_random')) {
    /**
     * Gerar string aleatória
     */
    function str_random($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('str_mask')) {
    /**
     * Mascarar parte de uma string
     *
     * @example str_mask('12345678901', 3, 4) => '123****8901'
     */
    function str_mask($string, $start = 0, $length = null, $char = '*') {
        $stringLength = strlen($string);
        $length = $length ?? $stringLength - $start;

        return substr($string, 0, $start) .
               str_repeat($char, $length) .
               substr($string, $start + $length);
    }
}

// ===================
// URLs & PATHS
// ===================

if (!function_exists('url')) {
    /**
     * Gerar URL completa
     */
    function url($path = '') {
        $base = defined('APP_URL') ? APP_URL : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Gerar URL para asset
     */
    function asset($path) {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('route')) {
    /**
     * Gerar URL para rota nomeada
     */
    function route($name, $params = []) {
        // Implementação básica - requer suporte no Router
        return url($name);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirecionar para URL
     */
    function redirect($url, $code = 302) {
        header("Location: {$url}", true, $code);
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redirecionar para página anterior
     */
    function back() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        redirect($referer);
    }
}

// ===================
// REQUEST & RESPONSE
// ===================

if (!function_exists('request')) {
    /**
     * Obter valor do request
     */
    function request($key = null, $default = null) {
        if ($key === null) {
            return $_REQUEST;
        }

        return $_REQUEST[$key] ?? $_GET[$key] ?? $_POST[$key] ?? $default;
    }
}

if (!function_exists('old')) {
    /**
     * Obter valor antigo do formulário (flash)
     */
    function old($key, $default = '') {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('session')) {
    /**
     * Obter/definir valor na sessão
     */
    function session($key = null, $default = null) {
        if ($key === null) {
            return $_SESSION;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return null;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Definir mensagem flash
     */
    function flash($key, $value = null) {
        if ($value === null) {
            $val = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $val;
        }

        $_SESSION['flash'][$key] = $value;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Obter token CSRF
     */
    function csrf_token() {
        return Security::getCSRFToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Gerar campo hidden com token CSRF
     */
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Gerar campo hidden para método HTTP
     */
    function method_field($method) {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

// ===================
// AUTH
// ===================

if (!function_exists('auth')) {
    /**
     * Obter usuário autenticado (admin)
     */
    function auth() {
        return Auth::check() ? Auth::user() : null;
    }
}

if (!function_exists('member')) {
    /**
     * Obter member autenticado
     */
    function member() {
        return MemberAuth::check() ? MemberAuth::member() : null;
    }
}

if (!function_exists('guest')) {
    /**
     * Verificar se é visitante (não logado)
     */
    function guest() {
        return !Auth::check() && !MemberAuth::check();
    }
}

// ===================
// DEBUG & LOGGING
// ===================

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$vars) {
        echo '<pre style="background:#1a1a2e;color:#4ecca3;padding:15px;margin:10px;border-radius:5px;font-family:monospace;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo '</pre>';
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump without die
     */
    function dump(...$vars) {
        echo '<pre style="background:#1a1a2e;color:#4ecca3;padding:15px;margin:10px;border-radius:5px;font-family:monospace;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo '</pre>';
    }
}

if (!function_exists('logger')) {
    /**
     * Log uma mensagem
     */
    function logger($message, $level = 'info') {
        Logger::getInstance()->$level($message);
    }
}

// ===================
// DATE & TIME
// ===================

if (!function_exists('now')) {
    /**
     * Obter data/hora atual
     */
    function now($format = 'Y-m-d H:i:s') {
        return date($format);
    }
}

if (!function_exists('carbon')) {
    /**
     * Criar objeto DateTime
     */
    function carbon($time = 'now') {
        return new DateTime($time);
    }
}

if (!function_exists('time_ago')) {
    /**
     * Converter data para "tempo atrás"
     */
    function time_ago($datetime) {
        $time = is_string($datetime) ? strtotime($datetime) : $datetime;
        $diff = time() - $time;

        if ($diff < 60) {
            return 'agora mesmo';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min atrás';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h atrás';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'd atrás';
        } else {
            return date('d/m/Y', $time);
        }
    }
}

// ===================
// FORMATTING
// ===================

if (!function_exists('money')) {
    /**
     * Formatar como moeda (BRL)
     */
    function money($value, $symbol = 'R$ ') {
        return $symbol . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('bytes')) {
    /**
     * Formatar bytes
     */
    function bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('phone')) {
    /**
     * Formatar telefone brasileiro
     */
    function phone($number) {
        $number = preg_replace('/\D/', '', $number);

        if (strlen($number) === 11) {
            return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 5) . '-' . substr($number, 7);
        } elseif (strlen($number) === 10) {
            return '(' . substr($number, 0, 2) . ') ' . substr($number, 2, 4) . '-' . substr($number, 6);
        }

        return $number;
    }
}

if (!function_exists('cpf')) {
    /**
     * Formatar CPF
     */
    function cpf($number) {
        $number = preg_replace('/\D/', '', $number);
        return substr($number, 0, 3) . '.' .
               substr($number, 3, 3) . '.' .
               substr($number, 6, 3) . '-' .
               substr($number, 9, 2);
    }
}

if (!function_exists('cnpj')) {
    /**
     * Formatar CNPJ
     */
    function cnpj($number) {
        $number = preg_replace('/\D/', '', $number);
        return substr($number, 0, 2) . '.' .
               substr($number, 2, 3) . '.' .
               substr($number, 5, 3) . '/' .
               substr($number, 8, 4) . '-' .
               substr($number, 12, 2);
    }
}

// ===================
// MISC
// ===================

if (!function_exists('e')) {
    /**
     * Escape HTML
     */
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    /**
     * Obter configuração
     */
    function config($key, $default = null) {
        // Configurações podem vir de constantes ou arquivo
        $key = strtoupper($key);
        return defined($key) ? constant($key) : $default;
    }
}

if (!function_exists('env')) {
    /**
     * Obter variável de ambiente
     */
    function env($key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Converter strings para tipos
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

if (!function_exists('abort')) {
    /**
     * Abortar com código HTTP
     */
    function abort($code, $message = '') {
        http_response_code($code);
        if ($message) {
            echo $message;
        }
        exit;
    }
}

if (!function_exists('retry')) {
    /**
     * Tentar executar callback com retry
     */
    function retry($times, callable $callback, $sleep = 0) {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $times) {
            try {
                return $callback($attempts);
            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $times && $sleep > 0) {
                    usleep($sleep * 1000);
                }
            }
        }

        throw $lastException;
    }
}

if (!function_exists('tap')) {
    /**
     * Tap pattern - executa callback e retorna valor original
     */
    function tap($value, callable $callback) {
        $callback($value);
        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Retorna valor ou executa callable
     */
    function value($value) {
        return $value instanceof Closure ? $value() : $value;
    }
}

// ===================
// COMPONENTS
// ===================

// Carregar helper de tabelas
require_once __DIR__ . '/helpers/table_helper.php';
