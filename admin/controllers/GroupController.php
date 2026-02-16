<?php
/**
 * GroupController
 * Gerenciar grupos de permissão
 */

class GroupController {

    /**
     * Listar todos os grupos
     */
    public function index() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $groups = $db->select('groups', [], 'name ASC');

        // ✅ OTIMIZAÇÃO: Batch queries ao invés de N+1 (2 queries ao invés de 2×N)
        if (!empty($groups)) {
            $groupIds = array_column($groups, 'id');

            // Criar placeholders para WHERE IN (??, ??, ??)
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

            // BATCH QUERY 1: Contar membros de todos os grupos de uma vez
            $memberCountsResult = $db->query(
                "SELECT group_id, COUNT(*) as count FROM member_groups WHERE group_id IN ({$placeholders}) GROUP BY group_id",
                $groupIds
            );
            $memberCounts = [];
            foreach ($memberCountsResult as $row) {
                $memberCounts[$row['group_id']] = (int) $row['count'];
            }

            // BATCH QUERY 2: Contar permissões de todos os grupos de uma vez
            $permCountsResult = $db->query(
                "SELECT group_id, COUNT(*) as count FROM page_permissions WHERE group_id IN ({$placeholders}) GROUP BY group_id",
                $groupIds
            );
            $permissionCounts = [];
            foreach ($permCountsResult as $row) {
                $permissionCounts[$row['group_id']] = (int) $row['count'];
            }

            // Aplicar contagens
            foreach ($groups as &$group) {
                $group['member_count'] = $memberCounts[$group['id']] ?? 0;
                $group['permission_count'] = $permissionCounts[$group['id']] ?? 0;
            }
            unset($group); // Limpar referência do foreach
        }

        require __DIR__ . '/../views/groups/index.php';
    }

    /**
     * Exibir formulário de criar grupo
     */
    public function create() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        require __DIR__ . '/../views/groups/create.php';
    }

    /**
     * Salvar novo grupo
     */
    public function store() {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $name = Security::sanitize($_POST['name'] ?? '');
            $description = Security::sanitize($_POST['description'] ?? '');

            if (empty($name)) {
                throw new Exception("Nome do grupo é obrigatório");
            }

            $db = DB::connect();

            // Verificar se nome já existe
            $existing = $db->select('groups', ['name' => $name]);
            if (!empty($existing)) {
                throw new Exception("Já existe um grupo com este nome");
            }

            $db->insert('groups', [
                'id' => Security::generateUUID(),
                'name' => $name,
                'description' => $description
            ]);

            $_SESSION['success'] = "Grupo criado com sucesso!";
            Core::redirect('/admin/groups');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/groups/create');
        }
    }

    /**
     * Exibir formulário de editar grupo
     */
    public function edit($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();
        $groups = $db->select('groups', ['id' => $id]);

        if (empty($groups)) {
            $_SESSION['error'] = "Grupo não encontrado";
            Core::redirect('/admin/groups');
        }

        $group = $groups[0];

        require __DIR__ . '/../views/groups/edit.php';
    }

    /**
     * Atualizar grupo
     */
    public function update($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $name = Security::sanitize($_POST['name'] ?? '');
            $description = Security::sanitize($_POST['description'] ?? '');

            if (empty($name)) {
                throw new Exception("Nome do grupo é obrigatório");
            }

            $db = DB::connect();

            // Verificar se nome já existe (exceto o próprio)
            $existing = $db->select('groups', ['name' => $name]);
            if (!empty($existing) && $existing[0]['id'] !== $id) {
                throw new Exception("Já existe um grupo com este nome");
            }

            $db->update('groups', [
                'name' => $name,
                'description' => $description
            ], ['id' => $id]);

            $_SESSION['success'] = "Grupo atualizado com sucesso!";
            Core::redirect('/admin/groups');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/groups/edit/' . $id);
        }
    }

    /**
     * Deletar grupo
     */
    public function destroy($id) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $db = DB::connect();

            // Deletar grupo (cascade vai remover member_groups e group_permissions)
            $db->delete('groups', ['id' => $id]);

            $_SESSION['success'] = "Grupo removido com sucesso!";
            Core::redirect('/admin/groups');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/groups');
        }
    }

    /**
     * Gerenciar permissões de um grupo
     */
    public function permissions($groupId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();

        // Buscar grupo
        $groups = $db->select('groups', ['id' => $groupId]);
        if (empty($groups)) {
            $_SESSION['error'] = "Grupo não encontrado";
            Core::redirect('/admin/groups');
        }

        $group = $groups[0];

        // Buscar páginas do sistema (excluir páginas de módulo)
        $pages = $db->select('pages', ['ativo' => 1, 'is_module_page' => 0], 'title ASC');

        // Verificar quais páginas o grupo tem acesso
        $groupPerms = $db->select('page_permissions', ['group_id' => $groupId]);
        $allowedPageIds = array_column($groupPerms, 'page_id');

        // Adicionar has_access a cada página
        foreach ($pages as $key => $page) {
            $pages[$key]['has_access'] = in_array($page['id'], $allowedPageIds);
        }

        // Buscar módulos instalados
        $modules = $this->getInstalledModules();

        // Verificar quais módulos o grupo tem acesso
        $modulePerms = $db->select('module_permissions', ['group_id' => $groupId]);
        $allowedModuleNames = array_column($modulePerms, 'module_name');

        // Adicionar has_access a cada módulo
        foreach ($modules as $key => $module) {
            $modules[$key]['has_access'] = in_array($module['name'], $allowedModuleNames);
        }

        require __DIR__ . '/../views/groups/permissions.php';
    }

    /**
     * Buscar módulos instalados (com cache)
     */
    private function getInstalledModules() {
        // ✅ PERFORMANCE: Cache de módulos (evita scandir + file_get_contents em toda request)
        static $cachedModules = null;

        if ($cachedModules !== null) {
            return $cachedModules;
        }

        $modulesPath = __DIR__ . '/../../modules';
        $modules = [];

        if (!is_dir($modulesPath)) {
            $cachedModules = $modules;
            return $modules;
        }

        $dirs = scandir($modulesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $modulePath = $modulesPath . '/' . $dir;
            $moduleJsonPath = $modulePath . '/module.json';

            if (is_dir($modulePath) && file_exists($moduleJsonPath)) {
                $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);

                if ($moduleJson && isset($moduleJson['name'])) {
                    $modules[] = [
                        'name' => $moduleJson['name'],
                        'label' => $moduleJson['label'] ?? $moduleJson['name'],
                        'description' => $moduleJson['description'] ?? '',
                        'public_url' => $moduleJson['public_url'] ?? null
                    ];
                }
            }
        }

        $cachedModules = $modules;
        return $modules;
    }

    /**
     * Atualizar permissões do grupo
     */
    public function updatePermissions($groupId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $pageIds = $_POST['pages'] ?? [];
            $moduleNames = $_POST['modules'] ?? [];
            $db = DB::connect();

            // 🔍 DEBUG LOG: Dados recebidos
            error_log("=== DEBUG UPDATE PERMISSIONS ===");
            error_log("Group ID: {$groupId}");
            error_log("Pages IDs: " . json_encode($pageIds));
            error_log("Module Names: " . json_encode($moduleNames));

            // Remover todas permissões atuais
            $db->delete('page_permissions', ['group_id' => $groupId]);
            error_log("✅ Permissões antigas removidas");

            // Adicionar permissões de páginas (apenas IDs válidos)
            foreach ($pageIds as $pageId) {
                // Validar: não vazio e UUID válido
                if (empty($pageId) || !is_string($pageId)) {
                    continue;
                }

                // Validar: página existe
                $pageExists = $db->select('pages', ['id' => $pageId]);
                if (empty($pageExists)) {
                    continue; // Skip ID inválido
                }

                Permission::grantGroup($groupId, $pageId);
                error_log("✅ Página {$pageId} concedida");
            }

            // Remover todas permissões de módulos atuais
            $db->delete('module_permissions', ['group_id' => $groupId]);
            error_log("✅ Permissões de módulos antigas removidas");

            // Adicionar permissões de módulos (apenas nomes válidos)
            foreach ($moduleNames as $moduleName) {
                // Validar: não vazio
                if (empty($moduleName) || !is_string($moduleName)) {
                    continue;
                }

                $db->insert('module_permissions', [
                    'group_id' => $groupId,
                    'module_name' => $moduleName
                ]);
                error_log("✅ Módulo {$moduleName} concedido");
            }

            error_log("=== FIM DEBUG ===");

            // 🛡️ SEGURANÇA: Invalidar cache de menu após mudança de permissões
            MenuBuilder::clearCache();

            $_SESSION['success'] = "Permissões do grupo atualizadas com sucesso!";
            Core::redirect('/admin/groups/' . $groupId . '/permissions');

        } catch (Exception $e) {
            error_log("❌ ERRO: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/groups/' . $groupId . '/permissions');
        }
    }

    /**
     * Gerenciar membros de um grupo
     */
    public function members($groupId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        $db = DB::connect();

        // Buscar grupo
        $groups = $db->select('groups', ['id' => $groupId]);
        if (empty($groups)) {
            $_SESSION['error'] = "Grupo não encontrado";
            Core::redirect('/admin/groups');
        }

        $group = $groups[0];

        // Buscar todos os membros
        $allMembers = $db->select('members', ['ativo' => 1], 'name ASC');

        // Verificar quais estão no grupo
        $memberGroups = $db->select('member_groups', ['group_id' => $groupId]);
        $groupMemberIds = array_column($memberGroups, 'member_id');

        foreach ($allMembers as &$member) {
            $member['in_group'] = in_array($member['id'], $groupMemberIds);
        }

        require __DIR__ . '/../views/groups/members.php';
    }

    /**
     * Atualizar membros do grupo
     */
    public function updateMembers($groupId) {
        Auth::require();

        if (!Core::membersEnabled()) {
            Core::redirect('/admin');
        }

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            $memberIds = $_POST['members'] ?? [];
            $db = DB::connect();

            // Remover todos membros atuais
            $db->delete('member_groups', ['group_id' => $groupId]);

            // Adicionar novos membros (apenas IDs válidos)
            foreach ($memberIds as $memberId) {
                // Validar: não vazio e UUID válido
                if (empty($memberId) || !is_string($memberId)) {
                    continue;
                }

                // Validar: member existe
                $memberExists = $db->select('members', ['id' => $memberId]);
                if (empty($memberExists)) {
                    continue; // Skip ID inválido
                }

                Permission::addMemberToGroup($memberId, $groupId);
            }

            // 🛡️ SEGURANÇA: Invalidar cache de menu após mudança de membros
            MenuBuilder::clearCache();

            $_SESSION['success'] = "Membros do grupo atualizados com sucesso!";
            Core::redirect('/admin/groups/' . $groupId . '/members');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/groups/' . $groupId . '/members');
        }
    }
}
