<?php
/**
 * ModuleInstaller
 * Responsável por instalar módulos e suas dependências
 */

class ModuleInstaller {

    /**
     * Instalar módulo
     *
     * @param string $moduleName Nome do módulo
     * @return array ['success' => bool, 'message' => string]
     */
    public static function install($moduleName) {
        try {
            // 1. Validar módulo
            $modulePath = ROOT_PATH . "modules/{$moduleName}";
            if (!is_dir($modulePath)) {
                return ['success' => false, 'message' => 'Módulo não encontrado'];
            }

            // 2. Ler metadados
            $metadata = self::readMetadata($moduleName);
            if (!$metadata) {
                return ['success' => false, 'message' => 'module.json inválido'];
            }

            // 3. Validar requisitos
            $validation = self::validateRequirements($metadata);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // 4. Executar schema SQL
            $result = self::executeSchema($moduleName, $metadata);
            if (!$result['success']) {
                return $result;
            }

            // 5. Registrar módulo na tabela modules
            self::registerModule($moduleName, $metadata);

            // 6. Criar menu item (se necessário)
            self::createMenuItemIfNeeded($moduleName, $metadata);

            // 7. Adicionar à lista de instalados
            $currentModules = ModuleManager::getInstalled();
            if (!in_array($moduleName, $currentModules)) {
                $currentModules[] = $moduleName;
            }

            // 8. Atualizar config
            $updateResult = self::updateInstalledModules($currentModules);
            if (!$updateResult['success']) {
                return $updateResult;
            }

            // 9. Invalidar cache
            ModuleManager::clearCache();
            PermissionManager::invalidateAll();

            // 10. Auto-bump version
            try {
                Version::autoBump();
            } catch (Exception $e) {
                error_log("Auto-bump falhou: " . $e->getMessage());
            }

            return ['success' => true, 'message' => 'Módulo instalado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Ler metadados do módulo (com cache)
     */
    private static function readMetadata($moduleName) {
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
     * Validar requisitos do módulo
     */
    private static function validateRequirements($metadata) {
        // Verificar database
        if (isset($metadata['requires']['database'])) {
            $requiredDbs = $metadata['requires']['database'];
            $currentDb = defined('DB_TYPE') ? DB_TYPE : 'none';

            if (!in_array($currentDb, $requiredDbs)) {
                return [
                    'valid' => false,
                    'message' => 'Este módulo requer banco: ' . implode(' ou ', $requiredDbs)
                ];
            }
        }

        // Verificar members
        if (isset($metadata['requires']['members']) && $metadata['requires']['members'] === true) {
            if (!Core::membersEnabled()) {
                return [
                    'valid' => false,
                    'message' => 'Este módulo requer sistema de membros habilitado'
                ];
            }
        }

        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Executar schema SQL do módulo
     */
    private static function executeSchema($moduleName, $metadata) {
        $dbType = defined('DB_TYPE') ? DB_TYPE : 'none';

        if ($dbType === 'none') {
            return ['success' => true, 'message' => 'Sem banco - nada a executar'];
        }

        $schemaFile = ROOT_PATH . "modules/{$moduleName}/database/{$dbType}-schema.sql";

        if (!file_exists($schemaFile)) {
            return ['success' => false, 'message' => "Schema para {$dbType} não encontrado"];
        }

        $sql = file_get_contents($schemaFile);

        if (empty($sql)) {
            return ['success' => false, 'message' => 'Schema SQL vazio'];
        }

        try {
            $db = DB::connect();

            // Limpar comentários (melhorado - respeita strings)
            $sql = self::cleanSqlComments($sql);

            // Dividir e executar queries
            $queries = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($queries as $query) {
                if (!empty($query)) {
                    $db->execute($query);
                }
            }

            return ['success' => true, 'message' => 'Schema executado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao executar schema: ' . $e->getMessage()];
        }
    }

    /**
     * Limpar comentários SQL (respeitando strings)
     */
    private static function cleanSqlComments($sql) {
        // Remove comentários de linha (-- ...)
        $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);

        // Remove comentários de bloco (/* ... */)
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        return $sql;
    }

    /**
     * Registrar módulo na tabela modules
     * Centraliza informações de acesso (is_public) no banco
     */
    private static function registerModule($moduleName, $metadata) {
        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return;
        }

        try {
            $db = DB::connect();

            // Verificar se tabela modules existe
            $tableExists = $db->query("SHOW TABLES LIKE 'modules'");
            if (empty($tableExists)) {
                return; // Tabela não existe ainda
            }

            // Verificar se módulo já está registrado
            $existing = $db->select('modules', ['name' => $moduleName]);

            $isPublic = !empty($metadata['public']) ? 1 : 0;
            $label = $metadata['label'] ?? $metadata['title'] ?? $moduleName;
            $version = $metadata['version'] ?? '1.0.0';

            if (!empty($existing)) {
                // Atualizar
                $db->update('modules', [
                    'label' => $label,
                    'version' => $version,
                    'is_public' => $isPublic,
                    'is_active' => 1
                ], ['name' => $moduleName]);
            } else {
                // Inserir
                $db->insert('modules', [
                    'name' => $moduleName,
                    'label' => $label,
                    'version' => $version,
                    'is_public' => $isPublic,
                    'is_active' => 1
                ]);
            }
        } catch (Exception $e) {
            // Log error mas não interrompe instalação
            error_log("Erro ao registrar módulo na tabela: " . $e->getMessage());
        }
    }

    /**
     * Criar menu item se necessário
     */
    private static function createMenuItemIfNeeded($moduleName, $metadata) {
        if (empty($metadata['label']) || empty($metadata['public_url'])) {
            return;
        }

        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return;
        }

        $db = DB::connect();

        // Verificar se já existe
        $existingMenu = $db->select('menu_items', [
            'module_name' => $moduleName,
            'type' => 'module'
        ]);

        if (!empty($existingMenu)) {
            return; // Já existe
        }

        // Pegar ordem máxima
        $maxOrdem = $db->query("SELECT MAX(ordem) as max_ordem FROM menu_items WHERE parent_id IS NULL");
        $ordem = isset($maxOrdem[0]['max_ordem']) ? ($maxOrdem[0]['max_ordem'] + 1) : 1;

        // Criar menu item
        $db->insert('menu_items', [
            'id' => Security::generateUUID(),
            'label' => $metadata['label'],
            'type' => 'module',
            'module_name' => $moduleName,
            'url' => $metadata['public_url'],
            'page_slug' => null,
            'icon' => $metadata['icon'] ?? 'box',
            'permission_type' => ($metadata['public'] ?? false) ? 'public' : 'authenticated',
            'visible' => 1,
            'ordem' => $ordem,
            'parent_id' => null
        ]);
    }

    /**
     * Atualizar lista de módulos instalados
     */
    public static function updateInstalledModules($modules) {
        $modulesString = implode(',', $modules);

        // Tentar .env primeiro
        if (file_exists(ROOT_PATH . '.env')) {
            $envContent = file_get_contents(ROOT_PATH . '.env');

            if (strpos($envContent, 'INSTALLED_MODULES=') !== false) {
                $envContent = preg_replace(
                    '/^INSTALLED_MODULES=.*$/m',
                    'INSTALLED_MODULES=' . $modulesString,
                    $envContent
                );
            } else {
                $envContent .= "\nINSTALLED_MODULES=" . $modulesString . "\n";
            }

            file_put_contents(ROOT_PATH . '.env', $envContent);
            return ['success' => true, 'message' => 'Config atualizado'];
        }

        // Fallback: _config.php
        if (file_exists(ROOT_PATH . '_config.php')) {
            $configContent = file_get_contents(ROOT_PATH . '_config.php');

            $newLine = "define('INSTALLED_MODULES', '" . $modulesString . "');";

            if (strpos($configContent, "define('INSTALLED_MODULES'") !== false) {
                $configContent = preg_replace(
                    "/define\\('INSTALLED_MODULES',\\s*'[^']*'\\);/",
                    $newLine,
                    $configContent
                );
            } else {
                $configContent = preg_replace(
                    "/(define\\('ENABLE_MEMBERS',\\s*\\w+\\);)/",
                    "$1\n\n// Installed Modules\n" . $newLine,
                    $configContent
                );
            }

            file_put_contents(ROOT_PATH . '_config.php', $configContent);
            return ['success' => true, 'message' => 'Config atualizado'];
        }

        return ['success' => false, 'message' => 'Arquivo de config não encontrado'];
    }
}
