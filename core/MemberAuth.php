<?php
/**
 * @doc Security
 * @title Sistema de Autenticação (Members)
 * @description
 * Gerencia autenticação de membros/usuários do site (não-admins):
 * - Login separado do sistema de admin
 * - Rate limiting independente
 * - Sessões isoladas
 * - Integração com sistema de permissões
 *
 * @example
 * // Fazer login de member
 * if (MemberAuth::login($email, $password)) {
 *     Core::redirect('/home');
 * }
 *
 * // Verificar se member está logado
 * if (MemberAuth::check()) {
 *     $member = MemberAuth::member();
 *     echo "Bem-vindo, " . $member['name'];
 * }
 *
 * // Proteger rota
 * MemberAuth::require(); // Redireciona para /login se não logado
 */

/**
 * MemberAuth
 * Autenticação de usuários do site (não admin)
 */

class MemberAuth {

    /**
     * Fazer login
     */
    public static function login($email, $password) {
        // Rate limiting por email
        $rateLimitKey = 'member_login_' . $email;

        if (!RateLimit::check($rateLimitKey, 5, 300)) {
            throw new Exception('Muitas tentativas de login. Aguarde 5 minutos.');
        }

        // Sanitizar email
        $email = Security::sanitize($email);

        // Buscar membro
        $db = DB::connect();
        $members = $db->select('members', ['email' => $email, 'ativo' => 1]);

        if (empty($members)) {
            return false;
        }

        $member = $members[0];

        // Verificar senha e checar se precisa rehash
        $result = Security::verifyAndRehash($password, $member['password']);

        if (!$result['valid']) {
            return false;
        }

        // Atualizar hash se necessário (transparente para o usuário)
        if ($result['newHash'] !== null) {
            $db->update('members', ['password' => $result['newHash']], ['id' => $member['id']]);
        }

        // Login bem-sucedido - resetar rate limit
        RateLimit::reset($rateLimitKey);

        // Criar sessão
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_email'] = $member['email'];
        $_SESSION['member_name'] = $member['name'];
        $_SESSION['member_login_at'] = time();
        $_SESSION['member_last_validation'] = time();

        // Regenerar ID da sessão
        session_regenerate_id(true);

        return true;
    }

    /**
     * Fazer logout
     */
    public static function logout() {
        unset($_SESSION['member_id']);
        unset($_SESSION['member_email']);
        unset($_SESSION['member_name']);
        unset($_SESSION['member_login_at']);
        unset($_SESSION['member_last_validation']);

        session_regenerate_id(true);
    }

    /**
     * Verificar se está logado
     */
    public static function check() {
        if (!isset($_SESSION['member_id'])) {
            return false;
        }

        // Revalidar permissões a cada 5 minutos (300 segundos)
        $lastCheck = $_SESSION['member_last_validation'] ?? 0;
        $now = time();

        if (($now - $lastCheck) > 300) {
            // Verificar se member ainda está ativo no banco
            $db = DB::connect();
            $members = $db->select('members', ['id' => $_SESSION['member_id'], 'ativo' => 1]);

            if (empty($members)) {
                // Member foi desativado ou deletado - fazer logout
                self::logout();
                return false;
            }

            // Atualizar timestamp de validação
            $_SESSION['member_last_validation'] = $now;
        }

        return true;
    }

    /**
     * Pegar membro logado
     */
    public static function member() {
        if (!self::check()) {
            return null;
        }

        // Buscar dados atualizados do banco (inclui avatar)
        $db = DB::connect();
        $members = $db->select('members', ['id' => $_SESSION['member_id']]);

        if (empty($members)) {
            // Member foi deletado - fazer logout
            self::logout();
            return null;
        }

        return $members[0];
    }

    /**
     * Require auth (middleware)
     */
    public static function require() {
        if (!self::check()) {
            Core::redirect('/login');
        }
    }

    /**
     * Criar membro
     */
    public static function createMember($email, $password, $name, $groupIds = [], $ativo = 1) {
        $db = DB::connect();

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
        $existing = $db->select('members', ['email' => $email]);
        if (!empty($existing)) {
            throw new Exception("Email já cadastrado");
        }

        // Hash senha
        $hashedPassword = Security::hashPassword($password);

        // Inserir membro
        $memberId = Security::generateUUID();

        // LOG: Rastrear criação de members
        Logger::getInstance()->audit('Member criado', null, [
            'member_id' => $memberId,
            'email' => $email,
            'name' => $name,
            'ativo' => $ativo,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);

        $result = $db->insert('members', [
            'id' => $memberId,
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
            'ativo' => $ativo ? 1 : 0
        ]);

        // Adicionar ao grupo padrão (se configurado)
        if (defined('DEFAULT_MEMBER_GROUP') && DEFAULT_MEMBER_GROUP !== null) {
            $groupIds[] = DEFAULT_MEMBER_GROUP;
        }

        // Adicionar aos grupos
        if (!empty($groupIds)) {
            foreach ($groupIds as $groupId) {
                Permission::addMemberToGroup($memberId, $groupId);
            }
        }

        return $memberId;
    }

    /**
     * Atualizar membro
     */
    public static function updateMember($memberId, $data) {
        $db = DB::connect();

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = Security::sanitize($data['name']);
        }

        if (isset($data['email'])) {
            $email = Security::sanitize($data['email']);
            if (!Security::validateEmail($email)) {
                throw new Exception("Email inválido");
            }

            // Verificar se email já existe (exceto o próprio)
            $existing = $db->select('members', ['email' => $email]);
            if (!empty($existing) && $existing[0]['id'] !== $memberId) {
                throw new Exception("Email já está em uso");
            }

            $updateData['email'] = $email;
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $passwordErrors = Security::validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                throw new Exception(implode(', ', $passwordErrors));
            }

            $updateData['password'] = Security::hashPassword($data['password']);
        }

        if (isset($data['ativo'])) {
            $updateData['ativo'] = (int) $data['ativo'];
        }

        if (isset($data['avatar'])) {
            $updateData['avatar'] = Security::sanitize($data['avatar']);
        }

        if (empty($updateData)) {
            return true;
        }

        return $db->update('members', $updateData, ['id' => $memberId]);
    }

    /**
     * Deletar membro
     */
    public static function deleteMember($memberId) {
        $db = DB::connect();

        // Remover de grupos
        $db->delete('member_groups', ['member_id' => $memberId]);

        // Remover permissões individuais
        $db->delete('member_page_permissions', ['member_id' => $memberId]);

        // Deletar membro
        return $db->delete('members', ['id' => $memberId]);
    }

    /**
     * Buscar páginas que o member tem permissão para acessar
     *
     * @param string $memberId ID do member
     * @return array Lista de páginas permitidas
     */
    public static function getPaginasPermitidas($memberId = null) {
        // Se não passar ID, pega do member logado
        if ($memberId === null) {
            $member = self::member();
            if (!$member) {
                return [];
            }
            $memberId = $member['id'];
        }

        $db = DB::connect();

        // Buscar todas as páginas criadas no sistema
        $todasPaginas = [];
        $pagesDir = ROOT_PATH . 'frontend/pages/';

        if (is_dir($pagesDir)) {
            $files = scandir($pagesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $slug = pathinfo($file, PATHINFO_FILENAME);

                    // Ler título da página (primeira linha com comentário ou usar slug)
                    $filePath = $pagesDir . $file;
                    $content = file_get_contents($filePath);

                    // Tentar extrair título do pageTitle
                    $title = $slug;
                    if (preg_match('/\$pageTitle\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                        $title = $matches[1];
                    }

                    $todasPaginas[] = [
                        'slug' => $slug,
                        'title' => $title
                    ];
                }
            }
        }

        // Se não tiver sistema de membros habilitado, retorna todas
        if (!Core::membersEnabled()) {
            return $todasPaginas;
        }

        // TODO: Aqui você pode adicionar lógica de permissões
        // Por enquanto, retorna todas as páginas
        // No futuro, pode filtrar baseado em grupos/permissões do member

        return $todasPaginas;
    }
}
