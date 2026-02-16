<?php
/**
 * ModuleUninstaller
 * Responsável por desinstalar módulos e remover suas dependências
 */

class ModuleUninstaller {

    /**
     * Desinstalar módulo
     *
     * @param string $moduleName Nome do módulo
     * @param bool $confirmed Confirmação do usuário
     * @return array ['success' => bool, 'message' => string]
     */
    public static function uninstall($moduleName, $confirmed = false) {
        if (!$confirmed) {
            return ['success' => false, 'message' => 'Confirmação necessária'];
        }

        try {
            // 1. Ler metadados
            $moduleJsonFile = ROOT_PATH . "modules/{$moduleName}/module.json";
            if (!file_exists($moduleJsonFile)) {
                return ['success' => false, 'message' => 'module.json não encontrado'];
            }

            $moduleJson = file_get_contents($moduleJsonFile);
            $metadata = json_decode($moduleJson, true);

            // 2. Remover tabelas do banco
            $result = self::dropTables($metadata);
            if (!$result['success']) {
                return $result;
            }

            // 3. Remover menu item
            self::removeMenuItemIfExists($moduleName);

            // 4. Remover da lista de instalados
            $currentModules = ModuleManager::getInstalled();
            $currentModules = array_filter($currentModules, function($m) use ($moduleName) {
                return $m !== $moduleName;
            });

            // 5. Atualizar config
            $updateResult = ModuleInstaller::updateInstalledModules($currentModules);
            if (!$updateResult['success']) {
                return $updateResult;
            }

            // 6. Invalidar cache
            ModuleManager::clearCache();

            // 7. Auto-bump version
            try {
                Version::autoBump();
            } catch (Exception $e) {
                error_log("Auto-bump falhou: " . $e->getMessage());
            }

            return ['success' => true, 'message' => 'Módulo desinstalado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Remover tabelas do módulo
     */
    private static function dropTables($metadata) {
        if (!isset($metadata['tables']) || empty($metadata['tables'])) {
            return ['success' => true, 'message' => 'Nenhuma tabela para remover'];
        }

        try {
            $db = DB::connect();

            // Usar transação se possível
            $useTransaction = DB_TYPE === 'mysql';

            if ($useTransaction) {
                $db->execute("START TRANSACTION");
                $db->execute("SET FOREIGN_KEY_CHECKS = 0");
            }

            // Remover views primeiro
            if (isset($metadata['views'])) {
                foreach ($metadata['views'] as $view) {
                    try {
                        $db->execute("DROP VIEW IF EXISTS {$view}");
                    } catch (Exception $e) {
                        // Continuar
                    }
                }
            }

            // Remover tabelas
            $errors = [];
            foreach ($metadata['tables'] as $table) {
                try {
                    $db->execute("DROP TABLE IF EXISTS {$table}");
                } catch (Exception $e) {
                    $errors[] = "Erro ao remover {$table}: " . $e->getMessage();
                }
            }

            if ($useTransaction) {
                $db->execute("SET FOREIGN_KEY_CHECKS = 1");
                $db->execute("COMMIT");
            }

            // Verificar se tabelas foram deletadas
            $tabelasRestantes = self::verifyTablesDeleted($db, $metadata['tables']);

            if (!empty($tabelasRestantes)) {
                $msg = 'ERRO: Tabelas não deletadas: ' . implode(', ', $tabelasRestantes);
                if (!empty($errors)) {
                    $msg .= ' | Erros: ' . implode('; ', $errors);
                }
                return ['success' => false, 'message' => $msg];
            }

            return ['success' => true, 'message' => 'Tabelas removidas com sucesso'];

        } catch (Exception $e) {
            // Rollback em caso de erro
            if ($useTransaction) {
                try {
                    $db->execute("SET FOREIGN_KEY_CHECKS = 1");
                    $db->execute("ROLLBACK");
                } catch (Exception $ex) {
                    // Ignorar
                }
            }
            return ['success' => false, 'message' => 'Erro ao remover tabelas: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar se tabelas foram deletadas
     */
    private static function verifyTablesDeleted($db, $tables) {
        $tabelasRestantes = [];

        foreach ($tables as $table) {
            try {
                $db->query("SELECT 1 FROM {$table} LIMIT 1");
                // Tabela ainda existe
                $tabelasRestantes[] = $table;
            } catch (Exception $e) {
                // Erro = tabela não existe (correto)
            }
        }

        return $tabelasRestantes;
    }

    /**
     * Remover menu item se existir
     */
    private static function removeMenuItemIfExists($moduleName) {
        if (!defined('DB_TYPE') || DB_TYPE === 'none') {
            return;
        }

        try {
            $db = DB::connect();
            $db->delete('menu_items', [
                'module_name' => $moduleName,
                'type' => 'module'
            ]);
        } catch (Exception $e) {
            // Ignorar erros
        }
    }

    /**
     * Verificar se tabelas do módulo foram deletadas (Supabase)
     *
     * @param string $moduleName Nome do módulo
     * @return array ['success' => bool, 'all_deleted' => bool, 'tables_found' => array]
     */
    public static function verifySupabaseDeletion($moduleName) {
        $moduleJsonFile = ROOT_PATH . "modules/{$moduleName}/module.json";
        if (!file_exists($moduleJsonFile)) {
            return [
                'success' => false,
                'message' => 'module.json não encontrado'
            ];
        }

        $moduleJson = file_get_contents($moduleJsonFile);
        $metadata = json_decode($moduleJson, true);

        if (!isset($metadata['tables']) || empty($metadata['tables'])) {
            return [
                'success' => true,
                'all_deleted' => true,
                'tables_found' => [],
                'message' => 'Nenhuma tabela para verificar'
            ];
        }

        try {
            $db = DB::connect();
            $tablesFound = self::verifyTablesDeleted($db, $metadata['tables']);
            $allDeleted = empty($tablesFound);

            return [
                'success' => true,
                'all_deleted' => $allDeleted,
                'tables_found' => $tablesFound,
                'total_expected' => count($metadata['tables']),
                'total_found' => count($tablesFound),
                'message' => $allDeleted
                    ? 'Todas as tabelas foram deletadas!'
                    : 'Ainda existem ' . count($tablesFound) . ' tabela(s)'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao verificar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Finalizar desinstalação (limpar registros locais)
     */
    public static function finalizeUninstall($moduleName) {
        try {
            // Remover da lista de instalados
            $currentModules = ModuleManager::getInstalled();
            $currentModules = array_filter($currentModules, function($m) use ($moduleName) {
                return $m !== $moduleName;
            });

            // Atualizar config
            $updateResult = ModuleInstaller::updateInstalledModules($currentModules);
            if (!$updateResult['success']) {
                return $updateResult;
            }

            // Invalidar cache
            ModuleManager::clearCache();

            // Limpar migrations
            try {
                $db = DB::connect();
                $db->delete('module_migrations', ['module_name' => $moduleName]);
            } catch (Exception $e) {
                // Ignorar
            }

            // Limpar páginas virtuais
            try {
                $db = DB::connect();
                $db->delete('pages', [
                    'module_name' => $moduleName,
                    'is_virtual' => true
                ]);
            } catch (Exception $e) {
                // Ignorar
            }

            return [
                'success' => true,
                'message' => 'Módulo desinstalado com sucesso!'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao finalizar: ' . $e->getMessage()
            ];
        }
    }
}
