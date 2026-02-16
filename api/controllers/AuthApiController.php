<?php
/**
 * AuthApiController
 * Controller de autenticação para API REST
 *
 * Endpoints:
 * - POST /api/v1/auth/login  - Login, retorna JWT tokens
 * - POST /api/v1/auth/refresh - Refresh token
 * - POST /api/v1/auth/logout  - Logout (invalida token)
 * - GET  /api/v1/auth/me      - Dados do usuário autenticado
 *
 * @example
 * // Login
 * POST /api/v1/auth/login
 * Body: {"email": "user@example.com", "password": "secret"}
 *
 * Response:
 * {
 *   "success": true,
 *   "data": {
 *     "access_token": "eyJ...",
 *     "refresh_token": "eyJ...",
 *     "token_type": "Bearer",
 *     "expires_in": 3600
 *   }
 * }
 */

class AuthApiController extends ApiController {

    /**
     * Login via API
     *
     * @return void
     */
    public function login() {
        // Validar input
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $email = $this->input('email');
        $password = $this->input('password');

        // Rate limiting para login
        $rateLimiter = new RateLimiter('api_login');
        $key = $email . ':' . Request::ip();

        if (!$rateLimiter->check($key, 5, 300)) { // 5 tentativas em 5 minutos
            $retryAfter = $rateLimiter->retryAfter($key);
            $this->tooManyRequests($retryAfter);
        }

        // Tentar autenticar (primeiro como admin, depois como member)
        $user = $this->authenticateUser($email, $password);

        if (!$user) {
            $rateLimiter->increment($key, 300);

            Logger::getInstance()->security('API login falhou', [
                'email' => $email,
                'ip' => Request::ip()
            ]);

            $this->unauthorized('Credenciais inválidas');
        }

        // Limpar rate limit após sucesso
        $rateLimiter->clear($key);

        // Gerar tokens JWT
        $tokens = JWT::createTokenPair([
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['nome'] ?? $user['name'] ?? null,
            'role' => $user['role'] ?? 'user',
            'type' => $user['type'] // 'admin' ou 'member'
        ]);

        // Log de sucesso
        Logger::getInstance()->info('API login bem-sucedido', [
            'user_id' => $user['id'],
            'email' => $email,
            'ip' => Request::ip()
        ]);

        // Disparar evento
        Event::fire('api.login', $user);

        $this->success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['nome'] ?? $user['name'] ?? null,
                'role' => $user['role'] ?? 'user'
            ]
        ]);
    }

    /**
     * Refresh token
     *
     * @return void
     */
    public function refresh() {
        $this->validate([
            'refresh_token' => 'required'
        ]);

        $refreshToken = $this->input('refresh_token');

        try {
            // Validar refresh token
            $payload = JWT::decode($refreshToken);

            // Verificar se é um refresh token
            if (($payload['type'] ?? '') !== 'refresh') {
                $this->badRequest('Token inválido para refresh');
            }

            // Invalidar token antigo
            JWT::invalidate($refreshToken);

            // Buscar dados atualizados do usuário
            $userId = $payload['sub'];
            $user = $this->findUserById($userId);

            if (!$user) {
                $this->unauthorized('Usuário não encontrado');
            }

            // Gerar novos tokens
            $tokens = JWT::createTokenPair([
                'sub' => $user['id'],
                'email' => $user['email'],
                'name' => $user['nome'] ?? $user['name'] ?? null,
                'role' => $user['role'] ?? 'user',
                'type' => $user['type']
            ]);

            $this->success([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_type' => $tokens['token_type'],
                'expires_in' => $tokens['expires_in']
            ]);

        } catch (Exception $e) {
            $this->unauthorized('Refresh token inválido ou expirado');
        }
    }

    /**
     * Logout - invalida token atual
     *
     * @return void
     */
    public function logout() {
        $this->requireAuth();

        $token = $_REQUEST['jwt_token'] ?? Request::bearerToken();

        if ($token) {
            JWT::invalidate($token);
        }

        // Disparar evento
        Event::fire('api.logout', $this->user());

        $this->success(null, 'Logout realizado com sucesso');
    }

    /**
     * Obter dados do usuário autenticado
     *
     * @return void
     */
    public function me() {
        $this->requireAuth();

        $user = $this->user();
        $userId = $user['sub'] ?? $user['id'];

        // Buscar dados completos do usuário
        $fullUser = $this->findUserById($userId);

        if (!$fullUser) {
            $this->notFound('Usuário não encontrado');
        }

        // Remover dados sensíveis
        unset($fullUser['senha'], $fullUser['password'], $fullUser['password_hash']);

        $this->success($fullUser);
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Tentar autenticar usuário (admin ou member)
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    private function authenticateUser($email, $password) {
        $db = DB::connect();

        // Tentar como admin primeiro
        $admin = $db->selectOne('admin_users', ['email' => $email, 'ativo' => 1]);

        if ($admin && Security::verifyPassword($password, $admin['senha'])) {
            return array_merge($admin, [
                'type' => 'admin',
                'role' => 'admin'
            ]);
        }

        // Tentar como member
        if (Core::membersEnabled()) {
            $member = $db->selectOne('members', ['email' => $email, 'ativo' => 1]);

            if ($member && Security::verifyPassword($password, $member['senha'])) {
                // Obter role do member
                $role = 'member';
                if (!empty($member['plano_id'])) {
                    $plano = $db->selectOne('planos', ['id' => $member['plano_id']]);
                    $role = $plano['slug'] ?? 'member';
                }

                return array_merge($member, [
                    'type' => 'member',
                    'role' => $role
                ]);
            }
        }

        return null;
    }

    /**
     * Buscar usuário por ID (admin ou member)
     *
     * @param string $userId
     * @return array|null
     */
    private function findUserById($userId) {
        $db = DB::connect();

        // Tentar como admin
        $admin = $db->selectOne('admin_users', ['id' => $userId]);
        if ($admin) {
            return array_merge($admin, [
                'type' => 'admin',
                'role' => 'admin'
            ]);
        }

        // Tentar como member
        if (Core::membersEnabled()) {
            $member = $db->selectOne('members', ['id' => $userId]);
            if ($member) {
                $role = 'member';
                if (!empty($member['plano_id'])) {
                    $plano = $db->selectOne('planos', ['id' => $member['plano_id']]);
                    $role = $plano['slug'] ?? 'member';
                }

                return array_merge($member, [
                    'type' => 'member',
                    'role' => $role
                ]);
            }
        }

        return null;
    }
}
