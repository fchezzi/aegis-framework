<?php
/**
 * ModuleManager
 * Gerencia módulos opcionais do AEGIS Framework
 *
 * Responsabilidades:
 * - Listar módulos disponíveis e instalados
 * - Carregar rotas de módulos ativos
 * - Obter metadados e menu items
 */

class ModuleManager {

    /**
     * Cache estático (em memória durante requisição)
     */
    private static $cachedModules = null;

    /**
     * Obter lista de módulos instalados
     *
     * @performance Cached (2-level): static + file cache (1h TTL)
     * @return array Lista de módulos instalados
     */
    public static function getInstalled() {
        // Cache Level 1: Static (in-memory)
        if (self::$cachedModules !== null) {
            return self::$cachedModules;
        }

        // Cache Level 2: File cache (1h)
        $cacheKey = 'installed_modules';
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            self::$cachedModules = $cached;
            return $cached;
        }

        // Cache miss: Ler de config
        $modules = [];

        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            $modules = [];
        } else if (defined('INSTALLED_MODULES') && !empty(INSTALLED_MODULES)) {
            $modules = explode(',', INSTALLED_MODULES);
            $modules = array_map('trim', $modules);
        }

        // Salvar em ambos caches
        Cache::set($cacheKey, $modules, 3600);
        self::$cachedModules = $modules;

        return $modules;
    }

    /**
     * Invalidar cache de módulos instalados
     *
     * @internal Chamado após install/uninstall
     */
    public static function clearCache() {
        self::$cachedModules = null;
        Cache::delete('installed_modules');

        // Limpar cache de metadados também
        $available = self::getAvailable();
        foreach ($available as $moduleName => $metadata) {
            SimpleCache::delete("module_metadata_{$moduleName}");
        }
    }

    /**
     * Obter lista de módulos disponíveis
     *
     * @return array Módulos disponíveis com metadados
     */
    public static function getAvailable() {
        $modulesPath = ROOT_PATH . 'modules/';

        if (!is_dir($modulesPath)) {
            return [];
        }

        $available = [];
        $dirs = scandir($modulesPath);

        foreach ($dirs as $dir) {
            // Ignorar . .. e arquivos ocultos
            if ($dir === '.' || $dir === '..' || strpos($dir, '.') === 0) {
                continue;
            }

            $modulePath = $modulesPath . $dir;

            if (!is_dir($modulePath)) {
                continue;
            }

            // Verificar module.json
            $moduleJsonFile = $modulePath . '/module.json';
            if (!file_exists($moduleJsonFile)) {
                continue;
            }

            // Ler metadados
            $metadata = self::readModuleMetadata($dir);

            if ($metadata) {
                $metadata['installed'] = self::isInstalled($dir);
                $available[$dir] = $metadata;
            }
        }

        return $available;
    }

    /**
     * Verificar se módulo está instalado
     */
    public static function isInstalled($moduleName) {
        $installed = self::getInstalled();
        return in_array($moduleName, $installed);
    }

    /**
     * Ler metadados do módulo (com cache)
     *
     * @param string $moduleName Nome do módulo
     * @return array|null Metadados ou null se inválido
     */
    public static function readModuleMetadata($moduleName) {
        $cacheKey = "module_metadata_{$moduleName}";
        $cached = SimpleCache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $moduleJsonFile = ROOT_PATH . "modules/{$moduleName}/module.json";
        if (!file_exists($moduleJsonFile)) {
            return null;
        }

        $moduleJson = file_get_contents($moduleJsonFile);
        $metadata = json_decode($moduleJson, true);

        if ($metadata) {
            SimpleCache::set($cacheKey, $metadata, 3600); // 1h
        }

        return $metadata;
    }

    /**
     * Carregar rotas de todos os módulos instalados
     */
    public static function loadAllRoutes() {
        $installed = self::getInstalled();

        if (empty($installed)) {
            return;
        }

        foreach ($installed as $moduleName) {
            self::loadModuleRoutes($moduleName);
        }
    }

    /**
     * Carregar rotas de um módulo específico
     */
    private static function loadModuleRoutes($moduleName) {
        $routeFile = ROOT_PATH . "modules/{$moduleName}/routes.php";

        if (file_exists($routeFile)) {
            require_once $routeFile;
        }
    }

    /**
     * Obter itens de menu de módulos instalados
     *
     * @return array Items de menu
     */
    public static function getMenuItems() {
        $installed = self::getInstalled();
        $menuItems = [];

        foreach ($installed as $moduleName) {
            $metadata = self::readModuleMetadata($moduleName);

            if ($metadata && isset($metadata['menu']['admin'])) {
                $menuItems[] = $metadata['menu']['admin'];
            }
        }

        return $menuItems;
    }

    /**
     * Instalar módulo
     *
     * @param string $moduleName Nome do módulo
     * @return array ['success' => bool, 'message' => string]
     */
    public static function install($moduleName) {
        return ModuleInstaller::install($moduleName);
    }

    /**
     * Desinstalar módulo
     *
     * @param string $moduleName Nome do módulo
     * @param bool $confirmed Confirmação do usuário
     * @return array ['success' => bool, 'message' => string]
     */
    public static function uninstall($moduleName, $confirmed = false) {
        return ModuleUninstaller::uninstall($moduleName, $confirmed);
    }

    /**
     * Verificar se tabelas foram deletadas (Supabase)
     */
    public static function verifySupabaseDeletion($moduleName) {
        return ModuleUninstaller::verifySupabaseDeletion($moduleName);
    }

    /**
     * Finalizar desinstalação
     */
    public static function finalizeUninstall($moduleName) {
        return ModuleUninstaller::finalizeUninstall($moduleName);
    }
}
