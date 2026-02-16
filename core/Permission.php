<?php
/**
 * @doc Security
 * @title Sistema de Permissões
 * @description
 * Controla acesso de members às páginas com granularidade:
 * - Permissões por grupo (todos do grupo acessam)
 * - Permissões individuais (sobrescrevem grupo)
 * - Hierarquia: Individual > Grupo > Bloqueado
 *
 * @example
 * // Verificar se member pode acessar página
 * if (Permission::canAccess($memberId, $pageId)) {
 *     // Permitir acesso
 * } else {
 *     // Negar acesso
 *     http_response_code(403);
 * }
 *
 * // Dar permissão individual
 * Permission::grantIndividual($memberId, $pageId);
 *
 * // Dar permissão para grupo
 * Permission::grantGroup($groupId, $pageId);
 */

/**
 * Permission
 * Sistema de permissões para páginas (grupos + individual)
 */

class Permission {

    /**
     * Verificar se membro pode acessar página
     *
     * Prioridade:
     * 1. Permissão individual → sobrescreve grupo
     * 2. Permissão de grupo → se membro está no grupo
     * 3. Bloqueado → sem acesso
     */
    public static function canAccess($memberId, $pageId) {
        $db = DB::connect();

        // 1. Verificar se página existe
        $page = $db->select('pages', ['id' => $pageId]);

        if (empty($page)) {
            return false;
        }

        // 2. Verificar permissão individual (prioridade máxima)
        $individualPerm = $db->select('member_page_permissions', [
            'member_id' => $memberId,
            'page_id' => $pageId
        ]);

        if (!empty($individualPerm)) {
            // MySQL: presença = permitido (sem coluna 'allow')
            // Supabase: usar coluna 'allow'
            if (isset($individualPerm[0]['allow'])) {
                // Proteção contra NULL - tratar como false (bloqueado)
                if ($individualPerm[0]['allow'] === null) {
                    return false;
                }
                return (bool) $individualPerm[0]['allow'];
            }
            return true; // MySQL - permissão existe = permitido
        }

        // 3. Verificar permissão de grupo
        $hasGroupAccess = self::hasGroupAccess($memberId, $pageId);

        return $hasGroupAccess;
    }

    /**
     * Verificar se membro tem acesso via grupo
     */
    private static function hasGroupAccess($memberId, $pageId) {
        $db = DB::connect();

        // Query direta com subquery - mais confiável que select() com array
        $sql = "
            SELECT COUNT(*) as total
            FROM page_permissions pp
            WHERE pp.group_id IN (
                SELECT group_id
                FROM member_groups
                WHERE member_id = ?
            )
            AND pp.page_id = ?
        ";

        $result = $db->query($sql, [$memberId, $pageId]);

        return !empty($result) && $result[0]['total'] > 0;
    }

    /**
     * Listar páginas acessíveis pelo membro
     */
    public static function getAccessiblePages($memberId) {
        $db = DB::connect();
        $accessibleIds = [];

        // 1. Pegar páginas com permissão individual
        $individualPerms = $db->select('member_page_permissions', [
            'member_id' => $memberId
        ]);

        foreach ($individualPerms as $perm) {
            // MySQL: presença = permitido
            // Supabase: verificar 'allow'
            if (isset($perm['allow'])) {
                // Proteção contra NULL - tratar como bloqueado
                if ($perm['allow'] !== null && $perm['allow'] == 1) {
                    $accessibleIds[$perm['page_id']] = true;
                }
            } else {
                $accessibleIds[$perm['page_id']] = true; // MySQL
            }
        }

        // 2. Pegar páginas via grupo
        $memberGroups = $db->select('member_groups', ['member_id' => $memberId]);

        if (!empty($memberGroups)) {
            $groupIds = array_column($memberGroups, 'group_id');

            // ⚡ OTIMIZADO: WHERE IN ao invés de N queries
            $groupPerms = $db->select('page_permissions', ['group_id' => $groupIds]);

            foreach ($groupPerms as $perm) {
                $accessibleIds[$perm['page_id']] = true;
            }

            // 3. Remover negações individuais (só Supabase tem 'allow')
            if (!empty($individualPerms) && isset($individualPerms[0]['allow'])) {
                foreach ($individualPerms as $perm) {
                    if ($perm['allow'] == 0) {
                        unset($accessibleIds[$perm['page_id']]);
                    }
                }
            }
        }

        // 4. Buscar páginas pelos IDs (WHERE IN)
        if (empty($accessibleIds)) {
            return [];
        }

        $pageIds = array_keys($accessibleIds);
        $pages = $db->select('pages', ['id' => $pageIds, 'ativo' => 1]);

        return $pages;
    }

    /**
     * Dar permissão individual
     */
    public static function grantIndividual($memberId, $pageId) {
        $db = DB::connect();

        // Verificar se já existe
        $existing = $db->select('member_page_permissions', [
            'member_id' => $memberId,
            'page_id' => $pageId
        ]);

        if (!empty($existing)) {
            // MySQL: já existe = nada a fazer (presença = permitido)
            // Supabase: atualizar allow
            if (isset($existing[0]['allow'])) {
                return $db->update('member_page_permissions',
                    ['allow' => 1],
                    ['id' => $existing[0]['id']]
                );
            }
            return true;
        }

        // Inserir
        // MySQL: composite PK (member_id, page_id), sem 'id' e 'allow'
        // Supabase: tem 'id' e 'allow'
        $data = [
            'member_id' => $memberId,
            'page_id' => $pageId
        ];

        // Detectar se é Supabase
        if (defined('DB_TYPE') && DB_TYPE === 'supabase') {
            $data['id'] = Security::generateUUID();
            $data['allow'] = 1;
        }

        $result = $db->insert('member_page_permissions', $data);

        // Invalidar cache do membro
        PermissionManager::invalidate($memberId);

        return $result;
    }

    /**
     * Negar permissão individual (só funciona em Supabase)
     */
    public static function denyIndividual($memberId, $pageId) {
        $db = DB::connect();

        // MySQL não suporta negação (sem coluna 'allow')
        // Apenas remove a permissão
        if (!defined('DB_TYPE') || DB_TYPE !== 'supabase') {
            return self::removeIndividual($memberId, $pageId);
        }

        // Verificar se já existe
        $existing = $db->select('member_page_permissions', [
            'member_id' => $memberId,
            'page_id' => $pageId
        ]);

        if (!empty($existing)) {
            // Atualizar
            $result = $db->update('member_page_permissions',
                ['allow' => 0],
                ['id' => $existing[0]['id']]
            );

            // Invalidar cache do membro
            PermissionManager::invalidate($memberId);

            return $result;
        }

        // Inserir negação
        $result = $db->insert('member_page_permissions', [
            'id' => Security::generateUUID(),
            'member_id' => $memberId,
            'page_id' => $pageId,
            'allow' => 0
        ]);

        // Invalidar cache do membro
        PermissionManager::invalidate($memberId);

        return $result;
    }

    /**
     * Remover permissão individual
     */
    public static function removeIndividual($memberId, $pageId) {
        $db = DB::connect();

        $result = $db->delete('member_page_permissions', [
            'member_id' => $memberId,
            'page_id' => $pageId
        ]);

        // Invalidar cache do membro
        PermissionManager::invalidate($memberId);

        return $result;
    }

    /**
     * Dar permissão para grupo
     */
    public static function grantGroup($groupId, $pageId) {
        $db = DB::connect();

        // Verificar se já existe
        $existing = $db->select('page_permissions', [
            'group_id' => $groupId,
            'page_id' => $pageId
        ]);

        if (!empty($existing)) {
            return true; // Já existe
        }

        // Schema usa composite PK (group_id, page_id), sem 'id'
        $data = [
            'group_id' => $groupId,
            'page_id' => $pageId
        ];

        $result = $db->insert('page_permissions', $data);

        // Invalidar cache de permissões
        PermissionManager::invalidateAll();

        return $result;
    }

    /**
     * Remover permissão de grupo
     */
    public static function removeGroup($groupId, $pageId) {
        $db = DB::connect();

        $result = $db->delete('page_permissions', [
            'group_id' => $groupId,
            'page_id' => $pageId
        ]);

        // Invalidar cache de permissões
        PermissionManager::invalidateAll();

        return $result;
    }

    /**
     * Adicionar membro ao grupo
     */
    public static function addMemberToGroup($memberId, $groupId) {
        $db = DB::connect();

        // Verificar se já existe
        $existing = $db->select('member_groups', [
            'member_id' => $memberId,
            'group_id' => $groupId
        ]);

        if (!empty($existing)) {
            return true;
        }

        // Schema usa composite PK (member_id, group_id), sem 'id'
        $result = $db->insert('member_groups', [
            'member_id' => $memberId,
            'group_id' => $groupId
        ]);

        // Invalidar cache do membro específico
        PermissionManager::invalidate($memberId);

        return $result;
    }

    /**
     * Remover membro do grupo
     */
    public static function removeMemberFromGroup($memberId, $groupId) {
        $db = DB::connect();

        $result = $db->delete('member_groups', [
            'member_id' => $memberId,
            'group_id' => $groupId
        ]);

        // Invalidar cache do membro específico
        PermissionManager::invalidate($memberId);

        return $result;
    }
}
