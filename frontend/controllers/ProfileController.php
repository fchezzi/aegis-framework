<?php
/**
 * ProfileController
 * Permite member editar próprio perfil (avatar e senha)
 */

class ProfileController {

    /**
     * Página de perfil
     */
    public function index() {
        // Requer autenticação de member
        if (!MemberAuth::check()) {
            $_SESSION['error'] = 'Você precisa estar logado para acessar seu perfil';
            Core::redirect('/login');
            return;
        }

        // Buscar dados do member logado
        $member = MemberAuth::member();

        // Renderizar página
        require ROOT_PATH . 'frontend/pages/profile.php';
    }

    /**
     * Atualizar avatar
     */
    public function updateAvatar() {
        // Requer autenticação
        if (!MemberAuth::check()) {
            return $this->jsonError('Não autorizado', 401);
        }

        try {
            // Validar CSRF
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            // Verificar se arquivo foi enviado
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo enviado');
            }

            // Upload da imagem
            $uploadResult = Upload::image($_FILES['avatar'], 'members/avatars');

            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['error'] ?? 'Erro no upload');
            }

            // Atualizar avatar do member logado
            $member = MemberAuth::member();
            $memberId = $member['id'];

            $data = [
                'avatar' => '/storage/uploads/' . $uploadResult['path']
            ];

            MemberAuth::updateMember($memberId, $data);

            return $this->jsonSuccess([
                'message' => 'Avatar atualizado com sucesso!',
                'avatar_url' => $data['avatar']
            ]);

        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * Atualizar senha
     */
    public function updatePassword() {
        try {
            // Requer autenticação
            if (!MemberAuth::check()) {
                return $this->jsonError('Não autorizado', 401);
            }

            // Validar CSRF
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $member = MemberAuth::member();
            $memberId = $member['id'];

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validações
            if (empty($currentPassword)) {
                throw new Exception('Senha atual é obrigatória');
            }

            if (empty($newPassword)) {
                throw new Exception('Nova senha é obrigatória');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('As senhas não coincidem');
            }

            // Verificar senha atual
            $db = DB::connect();
            $members = $db->select('members', ['id' => $memberId]);

            if (empty($members)) {
                throw new Exception('Member não encontrado');
            }

            $storedHash = $members[0]['password'];

            if (!Security::verifyPassword($currentPassword, $storedHash)) {
                throw new Exception('Senha atual incorreta');
            }

            // Validar força da nova senha
            $passwordErrors = Security::validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                throw new Exception(implode(', ', $passwordErrors));
            }

            // Atualizar senha
            $data = [
                'password' => $newPassword
            ];

            MemberAuth::updateMember($memberId, $data);

            // Regenerar sessão (segurança)
            session_regenerate_id(true);

            return $this->jsonSuccess([
                'message' => 'Senha atualizada com sucesso!'
            ]);

        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            return $this->jsonError('Erro interno: ' . $e->getMessage());
        }
    }

    /**
     * Resposta JSON de sucesso
     */
    private function jsonSuccess($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    /**
     * Resposta JSON de erro
     */
    private function jsonError($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}
