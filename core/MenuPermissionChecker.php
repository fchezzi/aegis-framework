<?php
/**
 * MenuPermissionChecker
 * Filtra itens de menu baseado em permissões
 * ⚡ Otimizado: Usa PermissionManager para cache compartilhado
 *
 * @see PermissionManager Para o sistema unificado de permissões
 */

class MenuPermissionChecker {

    private static $cacheTTL = 60; // 60 segundos

    /**
     * Filtrar itens por permissões
     *
     * @param array $items Itens do menu
     * @param string|null $memberId ID do membro (null = visitante)
     * @return array Itens filtrados
     */
    public static function filter($items, $memberId = null) {
        if (empty($items)) {
            return [];
        }

        // Inicializar PermissionManager (reutiliza cache se já inicializado)
        if ($memberId !== null) {
            PermissionManager::initialize($memberId);
        }

        // Cache check para dados específicos do menu
        $cacheKey = 'menu_perms_' . ($memberId ?? 'public');
        $cachedData = self::getCachedData($cacheKey);

        if ($cachedData !== null) {
            return self::filterWithCache($items, $cachedData, $memberId);
        }

        // Cache miss: Pre-fetch dados do menu (páginas por slug, etc)
        $data = self::prefetchMenuData($memberId);
        $allowed = self::filterWithData($items, $data, $memberId);

        // Salvar cache
        self::setCachedData($cacheKey, $data);

        return $allowed;
    }

    /**
     * Pre-fetch dados específicos do menu
     * Permissões são delegadas ao PermissionManager
     *
     * @param string|null $memberId
     * @return array
     */
    private static function prefetchMenuData($memberId) {
        $db = DB::connect();
        $data = [];

        // 1. Grupos do member (via PermissionManager se disponível)
        $data['memberGroupIds'] = [];
        if ($memberId && Core::membersEnabled()) {
            $data['memberGroupIds'] = PermissionManager::getMemberGroups($memberId);
        }

        // 2. Todas as páginas (necessário para lookup por slug)
        $allPages = $db->select('pages', ['ativo' => 1]);
        $data['pagesBySlug'] = [];
        $data['pagesById'] = [];
        foreach ($allPages as $page) {
            if (!empty($page['slug'])) {
                $data['pagesBySlug'][$page['slug']] = $page;
                $data['pagesById'][$page['id']] = $page;
            }
        }

        // 3. Metadados dos módulos
        $data['moduleMetadata'] = self::getModuleMetadata();

        return $data;
    }

    /**
     * Filtrar com dados (com ou sem cache)
     */
    private static function filterWithData($items, $data, $memberId) {
        $allowed = [];

        foreach ($items as $item) {
            if (self::canAccessItem($item, $data, $memberId)) {
                $allowed[] = $item;
            }
        }

        return $allowed;
    }

    /**
     * Filtrar com cache
     */
    private static function filterWithCache($items, $cachedData, $memberId) {
        return self::filterWithData($items, $cachedData, $memberId);
    }

    /**
     * Verificar se member pode acessar item
     */
    private static function canAccessItem($item, $data, $memberId) {
        // 1. Verificar se é página pública
        if ($item['type'] === 'page' && !empty($item['page_slug'])) {
            if (!isset($data['pagesBySlug'][$item['page_slug']])) {
                return false; // Página não existe
            }

            $page = $data['pagesBySlug'][$item['page_slug']];
            if (isset($page['is_public']) && $page['is_public'] == 1) {
                return true; // Página pública
            }
        }

        // 2. Verificar permission_type do menu (para todos os tipos, incluindo módulos)
        $canAccess = self::checkPermissionType($item, $data, $memberId);

        // 3. Para módulos: se permission_type permitiu acesso, retornar true direto
        // O item de menu já controla o acesso, não precisa verificar module_permissions
        if ($item['type'] === 'module' && !empty($item['module_name'])) {
            return $canAccess;
        }

        // 4. Se é página privada, verificar permissões granulares
        if ($canAccess && $item['type'] === 'page' && !empty($item['page_slug'])) {
            $page = $data['pagesBySlug'][$item['page_slug']];

            if (isset($page['is_public']) && $page['is_public'] == 0) {
                // Página privada
                if ($memberId) {
                    return self::hasPagePermission($page['id'], $data, $memberId);
                }
                return false;
            }
        }

        return $canAccess;
    }

    /**
     * Verificar acesso a módulo
     * Delega para PermissionManager
     */
    private static function canAccessModule($item, $data, $memberId) {
        // Usar PermissionManager (já inicializado, lookup O(1))
        return PermissionManager::canAccessModule($memberId, $item['module_name']);
    }

    /**
     * Verificar permission_type do menu item
     */
    private static function checkPermissionType($item, $data, $memberId) {
        switch ($item['permission_type']) {
            case 'public':
                return true;

            case 'authenticated':
                return ($memberId !== null);

            case 'group':
                if (Core::membersEnabled()) {
                    if ($memberId && isset($item['group_id']) && $item['group_id']) {
                        // Múltiplos grupos permitidos (separados por vírgula)
                        $allowedGroupIds = explode(',', $item['group_id']);

                        // Verificar se member pertence a QUALQUER um dos grupos permitidos
                        foreach ($allowedGroupIds as $allowedGroupId) {
                            $trimmed = trim($allowedGroupId);
                            if (in_array($trimmed, $data['memberGroupIds'])) {
                                return true;
                            }
                        }
                    }
                }
                return false;

            case 'member':
                if (Core::membersEnabled()) {
                    return ($memberId && isset($item['member_id']) && $memberId === $item['member_id']);
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Verificar permissão de página
     * Delega para PermissionManager (O(1) com cache)
     *
     * @param string $pageId
     * @param array $data Dados do menu (não usado, mantido para compatibilidade)
     * @param string|null $memberId
     * @return bool
     */
    private static function hasPagePermission($pageId, $data, $memberId = null) {
        // Usar PermissionManager (já inicializado, lookup O(1))
        return PermissionManager::canAccessPage($memberId, $pageId);
    }

    /**
     * Obter metadados dos módulos instalados
     */
    private static function getModuleMetadata() {
        $metadata = [];
        $installedModules = defined('INSTALLED_MODULES') ? explode(',', INSTALLED_MODULES) : [];

        foreach ($installedModules as $moduleName) {
            $moduleName = trim($moduleName);
            if (empty($moduleName)) continue;

            $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
            if (file_exists($moduleJsonPath)) {
                $json = @file_get_contents($moduleJsonPath);
                if ($json) {
                    $data = json_decode($json, true);
                    if ($data) {
                        $metadata[$moduleName] = $data;
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Get cached data
     */
    private static function getCachedData($key) {
        if (!isset($_SESSION['menu_cache'][$key])) {
            return null;
        }

        $cached = $_SESSION['menu_cache'][$key];
        $age = time() - $cached['timestamp'];

        if ($age < self::$cacheTTL) {
            return $cached['data'];
        }

        return null;
    }

    /**
     * Set cached data
     */
    private static function setCachedData($key, $data) {
        $_SESSION['menu_cache'][$key] = [
            'timestamp' => time(),
            'data' => $data
        ];
    }

    /**
     * Clear cache
     * Limpa cache do menu E do PermissionManager
     */
    public static function clearCache() {
        if (isset($_SESSION['menu_cache'])) {
            unset($_SESSION['menu_cache']);
        }

        // Também limpar cache de permissões
        PermissionManager::invalidateAll();
    }
}
