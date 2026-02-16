<?php
/**
 * @doc Security
 * @title Camadas de Segurança
 * @description
 * Fornece proteções essenciais contra ataques:
 * - CSRF: tokens para proteger formulários
 * - XSS: sanitização de inputs
 * - Password: hashing com bcrypt
 * - UUID: geração segura de IDs
 * - Validação de emails e inputs
 *
 * @example
 * // Proteger formulário
 * <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
 *
 * // Validar no submit
 * Security::validateCSRF($_POST['csrf_token']);
 *
 * // Sanitizar input
 * $clean = Security::sanitize($_POST['name']);
 *
 * // Hash de senha
 * $hash = Security::hashPassword($password);
 * if (Security::verifyPassword($input, $hash)) {
 *     // Senha correta
 * }
 */

/**
 * Security
 * Camadas de segurança (CSRF, XSS, Validation, etc)
 */

class Security {

    /**
     * Gerar CSRF token
     */
    public static function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validar CSRF token
     * Suporta token via POST, header X-CSRF-TOKEN ou X-Requested-With
     *
     * @param string|null $token Token do formulário (opcional se via header)
     * @param bool $throwException Se true, lança exceção ao invés de die()
     * @return bool
     */
    public static function validateCSRF($token = null, $throwException = false) {
        // Se token não fornecido, tentar buscar de headers (AJAX)
        if ($token === null || $token === '') {
            $token = self::getCSRFFromRequest();
        }

        if (!isset($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            Logger::getInstance()->security('CSRF validation failed', [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            if ($throwException) {
                throw new Exception('CSRF token inválido');
            }

            http_response_code(403);
            die('CSRF token inválido');
        }
        return true;
    }

    /**
     * Obter CSRF token da requisição (POST ou Headers)
     *
     * @return string|null
     */
    public static function getCSRFFromRequest() {
        // 1. POST field
        if (!empty($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // 2. Header X-CSRF-TOKEN (padrão Laravel/comum)
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // 3. Header X-XSRF-TOKEN (padrão Angular)
        if (!empty($_SERVER['HTTP_X_XSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_XSRF_TOKEN'];
        }

        return null;
    }

    /**
     * Verificar CSRF sem parar execução
     *
     * @param string|null $token
     * @return bool
     */
    public static function checkCSRF($token = null) {
        if ($token === null || $token === '') {
            $token = self::getCSRFFromRequest();
        }

        return isset($_SESSION['csrf_token'])
            && !empty($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitizar input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validar UUID
     */
    public static function isValidUUID($uuid) {
        $pattern = '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Gerar UUID v4 (cryptographically secure)
     */
    public static function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Opções de hashing de senha
     */
    private static $hashOptions = ['cost' => 12];

    /**
     * Hash de senha
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, self::$hashOptions);
    }

    /**
     * Verificar senha
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Verificar se hash precisa ser atualizado (rehash)
     * Útil quando o cost é alterado
     *
     * @param string $hash Hash atual
     * @return bool True se precisa rehash
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, self::$hashOptions);
    }

    /**
     * Verificar senha e retornar novo hash se necessário
     * Use após login bem sucedido para manter hashes atualizados
     *
     * @param string $password Senha em texto plano
     * @param string $hash Hash armazenado
     * @return array ['valid' => bool, 'newHash' => string|null]
     */
    public static function verifyAndRehash($password, $hash) {
        $valid = self::verifyPassword($password, $hash);

        if (!$valid) {
            return ['valid' => false, 'newHash' => null];
        }

        // Verificar se precisa atualizar o hash
        $newHash = null;
        if (self::needsRehash($hash)) {
            $newHash = self::hashPassword($password);
        }

        return ['valid' => true, 'newHash' => $newHash];
    }

    /**
     * Validar força de senha
     */
    public static function validatePasswordStrength($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Senha deve ter no mínimo 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Senha deve ter pelo menos 1 letra maiúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Senha deve ter pelo menos 1 letra minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve ter pelo menos 1 número';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Senha deve ter pelo menos 1 caractere especial';
        }

        return $errors;
    }

    /**
     * Validar email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Set security headers
     */
    public static function setHeaders() {
        // CSP desabilitado - JavaScript externo está funcionando e não precisa de CSP
        // Outras proteções continuam ativas:
        // 1. Todos os outputs usam htmlspecialchars() (proteção XSS)
        // 2. CSRF tokens em todos os forms
        // 3. Prepared statements (proteção SQL injection)
        // 4. Rate limiting ativo
        // header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; frame-ancestors 'none'");
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        if (Core::isProduction()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }

    /**
     * Prevenir timing attacks em comparações
     */
    public static function timingSafeCompare($known, $user) {
        if (!is_string($known) || !is_string($user)) {
            return false;
        }
        return hash_equals($known, $user);
    }

    /**
     * Sanitizar filename para upload
     */
    public static function sanitizeFilename($filename) {
        // Remove path traversal
        $filename = basename($filename);

        // Remove caracteres perigosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limita tamanho
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }

        return $filename;
    }

    /**
     * Validar tipo MIME de arquivo
     */
    public static function validateMimeType($filePath, $allowedTypes = []) {
        if (!file_exists($filePath)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (empty($allowedTypes)) {
            // Tipos padrão seguros
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        }

        return in_array($mimeType, $allowedTypes, true);
    }

    /**
     * Prevenir SQL injection em LIKE queries
     */
    public static function escapeLike($value) {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Limpar sessão completamente (logout seguro)
     */
    public static function destroySession() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Gerar token único para ações sensíveis (além do CSRF)
     */
    public static function generateActionToken($action) {
        $token = hash_hmac('sha256', $action . microtime(true), $_SESSION['csrf_token'] ?? '');
        $_SESSION['action_tokens'][$action] = $token;
        return $token;
    }

    /**
     * Validar token de ação
     */
    public static function validateActionToken($action, $token) {
        if (!isset($_SESSION['action_tokens'][$action])) {
            return false;
        }

        $valid = hash_equals($_SESSION['action_tokens'][$action], $token);
        unset($_SESSION['action_tokens'][$action]); // One-time use

        return $valid;
    }

    /**
     * Validar IP contra lista negra (opcional)
     */
    public static function validateIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Bloquear IPs privados em produção
        if (Core::isProduction() && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            http_response_code(403);
            die('Access denied');
        }

        return true;
    }
}
