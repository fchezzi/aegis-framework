<?php
/**
 * MemberController
 * Gerenciar membros (usuários do site)
 */

class MemberController {

    /**
     * Listar todos os membros
     */
    public function index() {
        Auth::require();

        // Verificar se sistema de membros está habilitado
        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();

        // PAGINAÇÃO
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Contar total de membros
        $totalResult = $db->query("SELECT COUNT(*) as total FROM members");
        $total = $totalResult[0]['total'] ?? 0;
        $totalPages = ceil($total / $perPage);

        // Buscar membros com paginação
        $members = $db->query("SELECT * FROM members ORDER BY created_at DESC LIMIT ? OFFSET ?", [$perPage, $offset]);

        // Otimização: Buscar todos os grupos de uma vez
        if (!empty($members)) {
            $memberIds = array_column($members, 'id');

            // Buscar todos os relacionamentos de uma vez
            $allMemberGroups = [];
            foreach ($memberIds as $memberId) {
                $memberGroups = $db->select('member_groups', ['member_id' => $memberId]);
                foreach ($memberGroups as $mg) {
                    $allMemberGroups[$memberId][] = $mg['group_id'];
                }
            }

            // Buscar todos os grupos únicos
            $uniqueGroupIds = [];
            foreach ($allMemberGroups as $groupIds) {
                $uniqueGroupIds = array_merge($uniqueGroupIds, $groupIds);
            }
            $uniqueGroupIds = array_unique($uniqueGroupIds);

            // Buscar grupos (cache)
            $groupsCache = [];
            foreach ($uniqueGroupIds as $groupId) {
                $groups = $db->select('groups', ['id' => $groupId]);
                if (!empty($groups)) {
                    $groupsCache[$groupId] = $groups[0];
                }
            }

            // Associar grupos aos membros
            foreach ($members as $key => $member) {
                $members[$key]['groups'] = [];
                if (isset($allMemberGroups[$member['id']])) {
                    foreach ($allMemberGroups[$member['id']] as $groupId) {
                        if (isset($groupsCache[$groupId])) {
                            $members[$key]['groups'][] = $groupsCache[$groupId];
                        }
                    }
                }
            }
        }

        // Passar variáveis de paginação para a view
        $pagination = [
            'current' => $page,
            'total' => $totalPages,
            'perPage' => $perPage,
            'totalRecords' => $total
        ];

        require __DIR__ . '/../views/members/index.php';
    }

    /**
     * Exibir formulário de criar membro
     */
    public function create() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        // Buscar grupos disponíveis
        $db = DB::connect();
        $groups = $db->select('groups', [], 'name ASC');

        require __DIR__ . '/../views/members/create.php';
    }

    /**
     * Salvar novo membro
     */
    public function store() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $email = Security::sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $name = Security::sanitize($_POST['name'] ?? '');
            $groupIds = is_array($_POST['groups'] ?? []) ? $_POST['groups'] : [];
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            // Validar e sanitizar group IDs
            $groupIds = array_filter($groupIds, function($id) {
                return Security::isValidUUID($id);
            });

            // Criar membro
            $memberId = MemberAuth::createMember($email, $password, $name, $groupIds, $ativo);

            $_SESSION['success'] = "Membro criado com sucesso!";
            Core::redirect('/admin/members');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/members/create');
        }
    }

    /**
     * Exibir formulário de editar membro
     */
    public function edit($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();

        // Buscar membro
        $members = $db->select('members', ['id' => $id]);
        if (empty($members)) {
            $_SESSION['error'] = "Membro não encontrado";
            Core::redirect('/admin/members');
        }

        $member = $members[0];

        // Buscar grupos do membro
        $memberGroups = $db->select('member_groups', ['member_id' => $id]);
        $memberGroupIds = array_column($memberGroups, 'group_id');

        // Buscar todos os grupos
        $allGroups = $db->select('groups', [], 'name ASC');

        require __DIR__ . '/../views/members/edit.php';
    }

    /**
     * Atualizar membro
     */
    public function update($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $data = [
                'name' => Security::sanitize($_POST['name'] ?? ''),
                'email' => Security::sanitize($_POST['email'] ?? ''),
            ];

            // Senha opcional (só atualiza se preenchida)
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }

            if (isset($_POST['ativo'])) {
                $data['ativo'] = (int) $_POST['ativo'];
            }

            // Upload de avatar (se enviado)
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = Upload::image($_FILES['avatar'], 'members/avatars');

                if ($uploadResult['success']) {
                    $data['avatar'] = '/storage/uploads/' . $uploadResult['path'];
                } else {
                    throw new Exception("Erro no upload: " . $uploadResult['error']);
                }
            }

            // Atualizar membro
            MemberAuth::updateMember($id, $data);

            // Atualizar grupos
            $newGroupIds = $_POST['groups'] ?? [];
            if (!is_array($newGroupIds)) {
                $newGroupIds = [];
            }

            // Validar e sanitizar group IDs
            $newGroupIds = array_filter($newGroupIds, function($gid) {
                return Security::isValidUUID($gid);
            });

            $db = DB::connect();

            // Remover grupos atuais
            $db->delete('member_groups', ['member_id' => $id]);

            // Adicionar novos grupos
            if (!empty($newGroupIds)) {
                foreach ($newGroupIds as $groupId) {
                    Permission::addMemberToGroup($id, $groupId);
                }
            }

            $_SESSION['success'] = "Membro atualizado com sucesso!";
            Core::redirect('/admin/members');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/members/edit/' . $id);
        }
    }

    /**
     * Deletar membro
     */
    public function destroy($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            MemberAuth::deleteMember($id);

            $_SESSION['success'] = "Membro removido com sucesso!";
            Core::redirect('/admin/members');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/members');
        }
    }

    /**
     * Gerenciar permissões individuais de um membro
     */
    public function permissions($memberId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();

        // Buscar membro
        $members = $db->select('members', ['id' => $memberId]);
        if (empty($members)) {
            $_SESSION['error'] = "Membro não encontrado";
            Core::redirect('/admin/members');
        }

        $member = $members[0];

        // Buscar todas as páginas
        $contents = $db->select('pages', ['ativo' => 1], 'title ASC');

        // Inicializar PermissionManager (pre-fetch de permissões)
        PermissionManager::initialize($memberId);

        // Para cada conteúdo, verificar permissão
        foreach ($contents as &$content) {
            // Verificar permissão individual (não existe mais member_permissions individuais)
            $individualPerms = [];

            if (!empty($individualPerms)) {
                $content['individual_permission'] = $individualPerms[0]['allow'];
            } else {
                $content['individual_permission'] = null; // Sem override
            }

            // Verificar se tem acesso via grupo (usa PermissionManager com cache)
            $content['has_group_access'] = PermissionManager::canAccessPage($memberId, $content['id']);

            // Valores padrão para compatibilidade com views
            $content['type'] = 'page';
            $content['is_public'] = 0;
        }

        require __DIR__ . '/../views/members/permissions.php';
    }

    /**
     * Atualizar permissões individuais
     */
    public function updatePermissions($memberId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $permissions = $_POST['permissions'] ?? [];

            foreach ($permissions as $contentId => $action) {
                if ($action === 'allow') {
                    Permission::grantIndividual($memberId, $contentId);
                } elseif ($action === 'deny') {
                    Permission::denyIndividual($memberId, $contentId);
                } elseif ($action === 'remove') {
                    Permission::removeIndividual($memberId, $contentId);
                }
            }

            $_SESSION['success'] = "Permissões atualizadas com sucesso!";
            Core::redirect('/admin/members/' . $memberId . '/permissions');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/members/' . $memberId . '/permissions');
        }
    }
}
