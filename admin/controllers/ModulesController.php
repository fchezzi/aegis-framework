<?php
/**
 * Modules Controller
 * Processa instalação e desinstalação de módulos
 */

class ModulesController {

    /**
     * Listar módulos instalados com configurações
     */
    public function index() {
        Auth::require();
        $user = Auth::user();

        // Dados para a interface de instalação/desinstalação (seção existente)
        $available = ModuleManager::getAvailable();
        $installed = ModuleManager::getInstalled();

        // ✅ SEGURANÇA: Sanitizar mensagens de GET
        $success = Security::sanitize($_GET['success'] ?? '');
        $error = Security::sanitize($_GET['error'] ?? '');
        $dbType = defined('DB_TYPE') ? DB_TYPE : 'none';

        // Dados para configurações de público/privado (ler de module.json)
        $modules = [];

        foreach ($installed as $moduleName) {
            // Carregar metadata do módulo
            $modulePath = __DIR__ . '/../../modules/' . $moduleName;
            $moduleJsonPath = $modulePath . '/module.json';

            if (!file_exists($moduleJsonPath)) {
                continue;
            }

            $metadata = json_decode(file_get_contents($moduleJsonPath), true);

            if (!$metadata) {
                continue;
            }

            // Ler "public" diretamente do module.json (fonte de verdade)
            $isPublic = ($metadata['public'] ?? false) ? 1 : 0;

            // ✅ SEGURANÇA: Sanitizar dados do module.json antes de passar para view
            $modules[] = [
                'name' => Security::sanitize($moduleName),
                'title' => Security::sanitize($metadata['title'] ?? $moduleName),
                'description' => Security::sanitize($metadata['description'] ?? ''),
                'version' => Security::sanitize($metadata['version'] ?? '1.0.0'),
                'public_url' => Security::sanitize($metadata['public_url'] ?? ''),
                'is_public' => $isPublic
            ];
        }

        require __DIR__ . '/../views/modules/index.php';
    }

    /**
     * Atualizar configurações de módulos (edita module.json)
     */
    public function update() {
        Auth::require();

        try {
            Security::validateCSRF($_POST['csrf_token'] ?? '');

            // ✅ SEGURANÇA: Sanitizar array de módulos públicos
            $publicModulesRaw = $_POST['public_modules'] ?? [];
            $publicModules = [];

            foreach ($publicModulesRaw as $moduleName) {
                // Validar nome do módulo (apenas letras, números, hífen, underscore)
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
                    $publicModules[] = Security::sanitize($moduleName);
                }
            }

            // Buscar todos os módulos instalados
            $installedModules = ModuleManager::getInstalled();
            $warnings = [];

            foreach ($installedModules as $moduleName) {
                $modulePath = __DIR__ . '/../../modules/' . $moduleName;
                $moduleJsonPath = $modulePath . '/module.json';

                if (!file_exists($moduleJsonPath)) {
                    continue;
                }

                // Ler module.json
                $metadata = json_decode(file_get_contents($moduleJsonPath), true);

                if (!$metadata) {
                    continue;
                }

                // Determinar se deve ser público
                $shouldBePublic = in_array($moduleName, $publicModules);

                // Atualizar campo "public" no module.json
                $metadata['public'] = $shouldBePublic;

                // Salvar module.json com formatação bonita
                $jsonContent = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if ($jsonContent === false) {
                    throw new Exception("Erro ao codificar JSON do módulo {$moduleName}");
                }

                $writeResult = file_put_contents($moduleJsonPath, $jsonContent);

                if ($writeResult === false) {
                    throw new Exception("Erro ao salvar module.json do módulo {$moduleName}");
                }

                // ⚠️ VALIDAÇÃO: Módulo privado sem grupos configurados
                if (!$shouldBePublic && defined('ENABLE_MEMBERS') && ENABLE_MEMBERS === true) {
                    $db = DB::connect();
                    $permissions = $db->select('module_permissions', ['module_name' => $moduleName]);

                    if (empty($permissions)) {
                        $warnings[] = "⚠️ Módulo '{$moduleName}' está PRIVADO mas nenhum grupo tem acesso. Configure em Grupos → Editar Grupo → Módulos.";
                    }
                }
            }

            // Mensagem de sucesso com warnings se houver
            $successMsg = "Configurações dos módulos atualizadas com sucesso!";

            if (!empty($warnings)) {
                $_SESSION['warning'] = implode('<br>', $warnings);
            }

            // 🛡️ SEGURANÇA: Invalidar cache de menu após mudança de configurações
            MenuBuilder::clearCache();

            $_SESSION['success'] = $successMsg;
            Core::redirect('/admin/modules');

        } catch (Exception $e) {
            error_log("[MODULES::UPDATE] ERRO: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            Core::redirect('/admin/modules');
        }
    }

    /**
     * Instalar módulo
     */
    public function install() {
        Auth::require();

        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['module_name']) || empty($_POST['module_name'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Nome do módulo não informado'));
        }

        $moduleName = Security::sanitize($_POST['module_name']);

        // Instalar
        $result = ModuleManager::install($moduleName);

        if ($result['success']) {
            Core::redirect('/admin/modules?success=' . urlencode($result['message']));
        } else {
            Core::redirect('/admin/modules?error=' . urlencode($result['message']));
        }
    }

    /**
     * Desinstalar módulo
     * - MySQL: Desinstalação automática (funciona perfeitamente)
     * - Supabase: Desinstalação manual em 2 etapas (por limitações da API)
     */
    public function uninstall() {
        Auth::require();

        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['module_name']) || empty($_POST['module_name'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Nome do módulo não informado'));
        }

        $moduleName = Security::sanitize($_POST['module_name']);
        $confirmed = isset($_POST['confirmed']) && $_POST['confirmed'] === '1';

        // Detectar tipo de banco e escolher fluxo apropriado
        $dbType = defined('DB_TYPE') ? DB_TYPE : 'none';

        if ($dbType === 'mysql') {
            // ✅ MySQL: Desinstalação automática (confiável)
            $result = ModuleManager::uninstall($moduleName, $confirmed);

            if ($result['success']) {
                Core::redirect('/admin/modules?success=' . urlencode($result['message']));
            } else {
                Core::redirect('/admin/modules?error=' . urlencode($result['message']));
            }

        } else {
            // ☁️ Supabase/outros: Desinstalação manual (por segurança)
            Core::redirect('/admin/modules/uninstall-step1?module=' . urlencode($moduleName));
        }
    }

    /**
     * Verificar se tabelas foram deletadas e finalizar desinstalação
     */
    public function verifyUninstall() {
        Auth::require();

        // Validar CSRF
        if (!isset($_POST['csrf_token'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Token CSRF ausente'));
        }

        Security::validateCSRF($_POST['csrf_token']);

        // Validar dados
        if (!isset($_POST['module_name']) || empty($_POST['module_name'])) {
            Core::redirect('/admin/modules?error=' . urlencode('Nome do módulo não informado'));
        }

        $moduleName = Security::sanitize($_POST['module_name']);

        // Verificar se tabelas foram deletadas
        $verification = ModuleManager::verifySupabaseDeletion($moduleName);

        if (!$verification['success']) {
            Core::redirect('/admin/modules/uninstall-step1?module=' . urlencode($moduleName) . '&error=' . urlencode($verification['message']));
            return;
        }

        // Se ainda existem tabelas, mostrar erro
        if (!$verification['all_deleted']) {
            $tablesFound = implode(', ', $verification['tables_found']);
            $errorMsg = "❌ Ainda existem tabelas no Supabase: {$tablesFound}. Execute o SQL novamente e tente outra vez.";
            Core::redirect('/admin/modules/uninstall-step1?module=' . urlencode($moduleName) . '&error=' . urlencode($errorMsg));
            return;
        }

        // Todas as tabelas foram deletadas - finalizar desinstalação
        $result = ModuleManager::finalizeUninstall($moduleName);

        if ($result['success']) {
            Core::redirect('/admin/modules?success=' . urlencode($result['message']));
        } else {
            Core::redirect('/admin/modules?error=' . urlencode($result['message']));
        }
    }
}
