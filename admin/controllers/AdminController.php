<?php
/**
 * AdminController
 * Gerenciar administradores (super usuários)
 */

class AdminController extends BaseController {

    /**
     * Listar todos os administradores
     */
    public function index() {
        $this->requireAuth();
        $user = $this->getUser();

        $admins = $this->db()->select('users', [], 'created_at DESC');

        $this->render('admins/index', [
            'admins' => $admins,
            'user' => $user
        ]);
    }

    /**
     * Exibir formulário de criar administrador
     */
    public function create() {
        $this->requireAuth();
        $user = $this->getUser();

        $this->render('admins/create', ['user' => $user]);
    }

    /**
     * Salvar novo administrador
     */
    public function store() {
        $this->requireAuth();

        try {
            $this->validateCSRF();

            $email = $this->input('email');
            $password = $_POST['password'] ?? '';
            $name = $this->input('name');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            // Validações
            if (empty($email) || empty($password) || empty($name)) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }

            // Verificar se email já existe
            $existing = $this->db()->select('users', ['email' => $email]);
            if (!empty($existing)) {
                throw new Exception('Já existe um administrador com este email');
            }

            // Validar força da senha
            $passwordErrors = Security::validatePasswordStrength($password);
            if (!empty($passwordErrors)) {
                throw new Exception(implode('. ', $passwordErrors));
            }

            // Hash da senha
            $passwordHash = Security::hashPassword($password);

            // Inserir admin
            $adminId = Security::generateUUID();
            $this->db()->insert('users', [
                'id' => $adminId,
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'ativo' => $ativo,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->success('Administrador criado com sucesso!');
            $this->redirect('/admin/admins');

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/admins/create');
        }
    }

    /**
     * Exibir formulário de editar administrador
     */
    public function edit($id) {
        $this->requireAuth();
        $user = $this->getUser();

        $admins = $this->db()->select('users', ['id' => $id]);

        if (empty($admins)) {
            $this->error('Administrador não encontrado');
            $this->redirect('/admin/admins');
            return;
        }

        $admin = $admins[0];

        $this->render('admins/edit', [
            'admin' => $admin,
            'user' => $user
        ]);
    }

    /**
     * Atualizar administrador
     */
    public function update($id) {
        $this->requireAuth();

        try {
            $this->validateCSRF();

            $email = $this->input('email');
            $name = $this->input('name');
            $password = $_POST['password'] ?? '';
            $ativo = intval($_POST['ativo'] ?? 0);

            // Validações
            if (empty($email) || empty($name)) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }

            // Verificar se email já existe (exceto o próprio admin)
            $existing = $this->db()->query(
                "SELECT * FROM users WHERE email = ? AND id != ?",
                [$email, $id]
            );
            if (!empty($existing)) {
                throw new Exception('Já existe um administrador com este email');
            }

            // Dados para atualizar
            $data = [
                'name' => $name,
                'email' => $email,
                'ativo' => $ativo
            ];

            // Se senha foi fornecida, atualizar
            if (!empty($password)) {
                $passwordErrors = Security::validatePasswordStrength($password);
                if (!empty($passwordErrors)) {
                    throw new Exception(implode('. ', $passwordErrors));
                }
                $data['password'] = Security::hashPassword($password);
            }

            $this->db()->update('users', $data, ['id' => $id]);

            $this->success('Administrador atualizado com sucesso!');
            $this->redirect('/admin/admins');

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/admins/' . $id . '/edit');
        }
    }

    /**
     * Deletar administrador
     */
    public function destroy($id) {
        $this->requireAuth();

        try {
            $this->validateCSRF();

            // Verificar se admin existe
            $admins = $this->db()->select('users', ['id' => $id]);
            if (empty($admins)) {
                throw new Exception('Administrador não encontrado');
            }

            // Não permitir deletar a si mesmo
            $currentUser = $this->getUser();
            if ($currentUser['id'] == $id) {
                throw new Exception('Você não pode deletar a si mesmo');
            }

            // Verificar se não é o único admin ativo
            $activeAdmins = $this->db()->select('users', ['ativo' => 1]);
            if (count($activeAdmins) <= 1 && $admins[0]['ativo'] == 1) {
                throw new Exception('Não é possível deletar o único administrador ativo do sistema');
            }

            $this->db()->delete('users', ['id' => $id]);

            $this->success('Administrador deletado com sucesso!');
            $this->redirect('/admin/admins');

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/admin/admins');
        }
    }
}
