<?php
/**
 * @doc Security
 * @title PermissionManager - Sistema Unificado de Permissões
 * @description
 * Gerenciador central de permissões com cache otimizado.
 * Substitui chamadas diretas a Permission e MenuPermissionChecker.
 *
 * Características:
 * - Pre-fetch de TODAS as permissões em 5 queries (1x por request)
 * - Cache em memória (static) durante a request
 * - Cache persistente opcional (APCu/Session)
 * - Lookup O(1) após inicialização
 *
 * Hierarquia de permissões:
 * 1. Permissão individual (prioridade máxima)
 * 2. Permissão de grupo
 * 3. Bloqueado (default)
 *
 * @example
 * // Inicializar no início da request (automático)
 * PermissionManager::initialize($memberId);
 *
 * // Verificar acesso a página - O(1), zero queries
 * if (PermissionManager::canAccessPage($memberId, $pageId)) {
 *     // permitido
 * }
 *
 * // Verificar acesso a módulo
 * if (PermissionManager::canAccessModule($memberId, 'blog')) {
 *     // permitido
 * }
 */

class PermissionManager {

    /**
     * Cache em memória (por request)
     * Estrutura: [memberId => ['groups' => [], 'pages' => [], 'modules' => []]]
     */
    private static $cache = [];

    /**
     * Flag para evitar inicialização duplicada
     */
    private static $initialized = [];

    /**
     * TTL do cache persistente (5 minutos)
     */
    private const CACHE_TTL = 300;

    /**
     * Inicializar permissões do membro
     * Faz pre-fetch de TUDO em 5 queries
     *
     * @param string|null $memberId ID do membro (null = visitante)
     * @return void
     */
    public static function initialize($memberId = null) {
        // Visitante não precisa de cache
        if ($memberId === null) {
            return;
        }

        // Já inicializado nesta request?
        if (isset(self::$initialized[$memberId])) {
            return;
        }

        // Tentar cache persistente primeiro
        $cached = self::getPersistedCache($memberId);
        if ($cached !== null) {
            self::$cache[$memberId] = $cached;
            self::$initialized[$memberId] = true;
            return;
        }

        // Cache miss: buscar do banco
        self::$cache[$memberId] = self::fetchAllPermissions($memberId);
        self::$initialized[$memberId] = true;

        // Persistir cache
        self::setPersistedCache($memberId, self::$cache[$memberId]);
    }

    /**
     * Buscar TODAS as permissões do membro (5 queries)
     *
     * @param string $memberId
     * @return array
     */
    private static function fetchAllPermissions($memberId) {
        $db = DB::connect();
        $data = [
            'groups' => [],
            'pages' => [],
            'modules' => [],
            'publicPages' => [],
            'publicModules' => []
        ];

        // Query 1: Grupos do membro
        $memberGroups = $db->select('member_groups', ['member_id' => $memberId]);
        $data['groups'] = array_column($memberGroups, 'group_id');

        // Query 2: Permissões individuais de página
        $individualPerms = $db->select('member_page_permissions', ['member_id' => $memberId]);
        foreach ($individualPerms as $perm) {
            // MySQL: presença = permitido | Supabase: coluna 'allow'
            if (isset($perm['allow'])) {
                $data['pages'][$perm['page_id']] = [
                    'allowed' => $perm['allow'] !== null && (bool) $perm['allow'],
                    'source' => 'individual'
                ];
            } else {
                $data['pages'][$perm['page_id']] = [
                    'allowed' => true,
                    'source' => 'individual'
                ];
            }
        }

        // Query 3: Permissões de grupo para páginas
        if (!empty($data['groups'])) {
            $groupPerms = $db->select('page_permissions', ['group_id' => $data['groups']]);
            foreach ($groupPerms as $perm) {
                // Só adiciona se não tiver permissão individual (individual tem prioridade)
                if (!isset($data['pages'][$perm['page_id']])) {
                    $data['pages'][$perm['page_id']] = [
                        'allowed' => true,
                        'source' => 'group'
                    ];
                }
            }
        }

        // Query 4: Permissões de grupo para módulos
        if (!empty($data['groups'])) {
            $modulePerms = $db->select('module_permissions', ['group_id' => $data['groups']]);
            foreach ($modulePerms as $perm) {
                $data['modules'][$perm['module_name']] = true;
            }
        }

        // Query 5: Páginas públicas (is_public = 1)
        $publicPages = $db->select('pages', ['is_public' => 1, 'ativo' => 1]);
        foreach ($publicPages as $page) {
            $data['publicPages'][$page['id']] = true;
            $data['publicPages']['slug:' . $page['slug']] = $page['id'];
        }

        // Módulos públicos (do module.json) - sem query, leitura de arquivo
        $data['publicModules'] = self::getPublicModules();

        return $data;
    }

    /**
     * Verificar se membro pode acessar página
     *
     * @param string|null $memberId
     * @param string $pageId
     * @return bool
     */
    public static function canAccessPage($memberId, $pageId) {
        // Página pública?
        if (self::isPublicPage($pageId)) {
            return true;
        }

        // Visitante não pode acessar páginas privadas
        if ($memberId === null) {
            return false;
        }

        // Garantir inicialização
        self::initialize($memberId);

        // Lookup O(1)
        if (isset(self::$cache[$memberId]['pages'][$pageId])) {
            return self::$cache[$memberId]['pages'][$pageId]['allowed'];
        }

        return false;
    }

    /**
     * Verificar se membro pode acessar módulo
     *
     * @param string|null $memberId
     * @param string $moduleName
     * @return bool
     */
    public static function canAccessModule($memberId, $moduleName) {
        // Módulo público?
        if (self::isPublicModule($moduleName)) {
            return true;
        }

        // Sistema sem members = acesso liberado
        if (!Core::membersEnabled()) {
            return true;
        }

        // Visitante não pode acessar módulos privados
        if ($memberId === null) {
            return false;
        }

        // Garantir inicialização
        self::initialize($memberId);

        // Lookup O(1)
        return isset(self::$cache[$memberId]['modules'][$moduleName]);
    }

    /**
     * Verificar se página é pública
     *
     * @param string $pageId
     * @return bool
     */
    public static function isPublicPage($pageId) {
        // Verificar no cache de qualquer membro inicializado
        foreach (self::$cache as $memberCache) {
            if (isset($memberCache['publicPages'][$pageId])) {
                return true;
            }
        }

        // Fallback: consulta direta (raro)
        $db = DB::connect();
        $pages = $db->select('pages', ['id' => $pageId, 'is_public' => 1, 'ativo' => 1]);
        return !empty($pages);
    }

    /**
     * Verificar se página é pública pelo slug
     *
     * @param string $slug
     * @return bool
     */
    public static function isPublicPageBySlug($slug) {
        foreach (self::$cache as $memberCache) {
            if (isset($memberCache['publicPages']['slug:' . $slug])) {
                return true;
            }
        }

        // Fallback
        $db = DB::connect();
        $pages = $db->select('pages', ['slug' => $slug, 'is_public' => 1, 'ativo' => 1]);
        return !empty($pages);
    }

    /**
     * Verificar se módulo é público
     *
     * @param string $moduleName
     * @return bool
     */
    public static function isPublicModule($moduleName) {
        $publicModules = self::getPublicModules();
        return isset($publicModules[$moduleName]);
    }

    /**
     * Obter lista de módulos públicos (cache estático)
     * Prioridade: DB > JSON (fallback para compatibilidade)
     *
     * @return array
     */
    private static function getPublicModules() {
        static $publicModules = null;

        if ($publicModules !== null) {
            return $publicModules;
        }

        $publicModules = [];

        // Tentar buscar do banco primeiro
        try {
            $db = DB::connect();

            // Verificar se tabela modules existe
            $tableExists = $db->query("SHOW TABLES LIKE 'modules'");

            if (!empty($tableExists)) {
                // Buscar módulos públicos do banco
                $modules = $db->select('modules', ['is_public' => 1, 'is_active' => 1]);
                foreach ($modules as $module) {
                    $publicModules[$module['name']] = true;
                }
                return $publicModules;
            }
        } catch (Exception $e) {
            // Fallback para JSON se banco falhar
        }

        // Fallback: ler do module.json (compatibilidade)
        $installedModules = defined('INSTALLED_MODULES') ? explode(',', INSTALLED_MODULES) : [];

        foreach ($installedModules as $moduleName) {
            $moduleName = trim($moduleName);
            if (empty($moduleName)) continue;

            $moduleJsonPath = ROOT_PATH . "modules/{$moduleName}/module.json";
            if (file_exists($moduleJsonPath)) {
                $json = @file_get_contents($moduleJsonPath);
                if ($json) {
                    $data = json_decode($json, true);
                    if ($data && !empty($data['public'])) {
                        $publicModules[$moduleName] = true;
                    }
                }
            }
        }

        return $publicModules;
    }

    /**
     * Obter grupos do membro
     *
     * @param string $memberId
     * @return array
     */
    public static function getMemberGroups($memberId) {
        self::initialize($memberId);
        return self::$cache[$memberId]['groups'] ?? [];
    }

    /**
     * Obter todas as páginas acessíveis pelo membro
     *
     * @param string $memberId
     * @return array Lista de page IDs
     */
    public static function getAccessiblePageIds($memberId) {
        self::initialize($memberId);

        $accessible = [];

        // Páginas públicas
        foreach (self::$cache[$memberId]['publicPages'] ?? [] as $key => $value) {
            if (strpos($key, 'slug:') !== 0) {
                $accessible[] = $key;
            }
        }

        // Páginas com permissão
        foreach (self::$cache[$memberId]['pages'] ?? [] as $pageId => $perm) {
            if ($perm['allowed']) {
                $accessible[] = $pageId;
            }
        }

        return array_unique($accessible);
    }

    /**
     * Invalidar cache do membro
     * Chamar após alterações de permissão
     *
     * @param string $memberId
     * @return void
     */
    public static function invalidate($memberId) {
        unset(self::$cache[$memberId]);
        unset(self::$initialized[$memberId]);
        self::deletePersistedCache($memberId);
    }

    /**
     * Invalidar TODO o cache
     * Chamar após alterações em grupos/páginas
     *
     * @return void
     */
    public static function invalidateAll() {
        self::$cache = [];
        self::$initialized = [];

        // Limpar cache de sessão
        if (isset($_SESSION['perm_cache'])) {
            unset($_SESSION['perm_cache']);
        }
    }

    /**
     * Obter cache persistente
     *
     * @param string $memberId
     * @return array|null
     */
    private static function getPersistedCache($memberId) {
        // Tentar APCu primeiro (se disponível)
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch("perms:{$memberId}");
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fallback: Session
        if (isset($_SESSION['perm_cache'][$memberId])) {
            $cached = $_SESSION['perm_cache'][$memberId];

            // Verificar TTL
            if (isset($cached['_expires']) && $cached['_expires'] > time()) {
                unset($cached['_expires']);
                return $cached;
            }

            // Expirado
            unset($_SESSION['perm_cache'][$memberId]);
        }

        return null;
    }

    /**
     * Salvar cache persistente
     *
     * @param string $memberId
     * @param array $data
     * @return void
     */
    private static function setPersistedCache($memberId, $data) {
        // Tentar APCu primeiro
        if (function_exists('apcu_store')) {
            apcu_store("perms:{$memberId}", $data, self::CACHE_TTL);
            return;
        }

        // Fallback: Session
        $data['_expires'] = time() + self::CACHE_TTL;
        $_SESSION['perm_cache'][$memberId] = $data;
    }

    /**
     * Deletar cache persistente
     *
     * @param string $memberId
     * @return void
     */
    private static function deletePersistedCache($memberId) {
        if (function_exists('apcu_delete')) {
            apcu_delete("perms:{$memberId}");
        }

        if (isset($_SESSION['perm_cache'][$memberId])) {
            unset($_SESSION['perm_cache'][$memberId]);
        }
    }

    /**
     * Debug: obter estatísticas do cache
     *
     * @return array
     */
    public static function getStats() {
        return [
            'initialized_members' => count(self::$initialized),
            'cached_members' => count(self::$cache),
            'apcu_available' => function_exists('apcu_fetch'),
            'session_cache_size' => isset($_SESSION['perm_cache']) ? count($_SESSION['perm_cache']) : 0
        ];
    }
}
