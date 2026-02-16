<?php
/**
 * @doc Security
 * @title Sistema de Autenticação (Admin)
 * @description
 * Gerencia autenticação de administradores do sistema:
 * - Login com rate limiting (máx 5 tentativas em 5min)
 * - Sessões seguras com regeneração de ID
 * - Verificação de permissões
 * - Logout seguro
 *
 * @example
 * // Fazer login
 * if (Auth::login($email, $password, $db)) {
 *     Core::redirect('/admin/dashboard');
 * }
 *
 * // Verificar se está logado
 * if (Auth::check()) {
 *     $admin = Auth::user();
 *     echo $admin['name'];
 * }
 *
 * // Proteger rota
 * Auth::require(); // Redireciona se não logado
 */

/**
 * Auth
 * Sistema de autenticação
 */

class Auth {

    /**
     * Fazer login
     */
    public static function login($email, $password, $db) {
        // Rate limiting por email
        $rateLimitKey = 'login_' . $email;

        if (!RateLimit::check($rateLimitKey, 5, 300)) {
            throw new Exception('Muitas tentativas de login. Aguarde 5 minutos.');
        }

        // Sanitizar email
        $email = Security::sanitize($email);

        // Buscar usuário
        $users = $db->select('users', ['email' => $email, 'ativo' => true]);

        if (empty($users)) {
            return false;
        }

        $user = $users[0];

        // Verificar senha e checar se precisa rehash
        $result = Security::verifyAndRehash($password, $user['password']);

        if (!$result['valid']) {
            return false;
        }

        // Atualizar hash se necessário (transparente para o usuário)
        if ($result['newHash'] !== null) {
            $db->update('users', ['password' => $result['newHash']], ['id' => $user['id']]);
        }

        // Login bem-sucedido - resetar rate limit
        RateLimit::reset($rateLimitKey);

        // Criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['login_at'] = time();

        // Regenerar ID da sessão
        session_regenerate_id(true);

        return true;
    }

    /**
     * Fazer logout
     */
    public static function logout() {
        // Limpar apenas os dados de autenticação, manter a sessão para mensagens
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['login_at']);
        unset($_SESSION['last_activity']);

        // Regenerar ID para segurança
        session_regenerate_id(true);
    }

    /**
     * Verificar se está logado
     */
    public static function check() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Timeout de sessão (2 horas)
        if (isset($_SESSION['login_at']) && (time() - $_SESSION['login_at']) > 7200) {
            self::logout();
            return false;
        }

        // Atualizar último acesso
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Pegar usuário logado
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name']
        ];
    }

    /**
     * Pegar ID do usuário logado
     */
    public static function id() {
        $user = self::user();
        return $user ? $user['id'] : null;
    }

    /**
     * Require auth (middleware)
     */
    public static function require() {
        if (!self::check()) {
            Core::redirect('/admin/login');
        }
    }

    /**
     * Criar usuário
     */
    public static function createUser($email, $password, $name, $db) {
        // Validar email
        if (!Security::validateEmail($email)) {
            throw new Exception("Email inválido");
        }

        // Validar força de senha
        $passwordErrors = Security::validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            throw new Exception(implode(', ', $passwordErrors));
        }

        // Verificar se email já existe
        $existing = $db->select('users', ['email' => $email]);
        if (!empty($existing)) {
            throw new Exception("Email já cadastrado");
        }

        // Hash senha
        $hashedPassword = Security::hashPassword($password);

        // Preparar dados
        $userData = [
            'id' => Security::generateUUID(),
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
            'ativo' => true
        ];

        // Inserir
        $insertedId = $db->insert('users', $userData);

        // Verificar se realmente foi inserido
        $verify = $db->select('users', ['email' => $email]);
        if (empty($verify)) {
            throw new Exception("ERRO CRÍTICO: Usuário não foi inserido no banco de dados!");
        }

        return $insertedId;
    }
}
