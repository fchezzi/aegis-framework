<?php
/**
 * @doc Core
 * @title Classe Core
 * @description
 * Facade principal do AEGIS Framework.
 * Delega funcionalidades para classes especializadas:
 * - CoreEnvironment: Detecção de ambiente
 * - CoreConfig: Gestão de configurações
 * - CoreResponse: Respostas HTTP
 * - Logger: Sistema de logging
 *
 * @example
 * // Redirecionar
 * Core::redirect('/admin/dashboard');
 *
 * // Verificar ambiente
 * if (Core::isDev()) { }
 *
 * // Verificar membros
 * if (Core::membersEnabled()) { }
 */

/**
 * Core
 * Facade do framework (delega para classes especializadas)
 */

class Core {

    const VERSION = '6.0.0';

    // ========================================
    // ENVIRONMENT
    // ========================================

    public static function detectEnvironment() {
        return CoreEnvironment::detect();
    }

    public static function isDev() {
        return CoreEnvironment::isDev();
    }

    public static function isProduction() {
        return CoreEnvironment::isProduction();
    }

    public static function forceEnvironment($env) {
        CoreEnvironment::force($env);
    }

    public static function environment() {
        return CoreEnvironment::name();
    }

    public static function configure() {
        CoreEnvironment::configure();
    }

    // ========================================
    // CONFIG
    // ========================================

    public static function loadConfig() {
        return CoreConfig::load();
    }

    public static function get($key, $default = null) {
        return CoreConfig::get($key, $default);
    }

    public static function isInstalled() {
        return CoreConfig::isInstalled();
    }

    public static function generateConfig($data) {
        return CoreConfig::generate($data);
    }

    // ========================================
    // RESPONSE
    // ========================================

    public static function redirect($url) {
        CoreResponse::redirect($url);
    }

    public static function url($path = '') {
        return CoreResponse::url($path);
    }

    public static function json($data, $statusCode = 200) {
        CoreResponse::json($data, $statusCode);
    }

    public static function error($statusCode, $message) {
        CoreResponse::error($statusCode, $message);
    }

    public static function success($message, $redirectUrl = null) {
        CoreResponse::success($message, $redirectUrl);
    }

    public static function renderBreadcrumb($items) {
        return CoreResponse::breadcrumb($items);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Verificar se sistema de membros está habilitado
     *
     * @return bool
     */
    public static function membersEnabled() {
        if (!defined('ENABLE_MEMBERS')) {
            return false;
        }

        $value = ENABLE_MEMBERS;
        return $value === true || $value === 'true';
    }

    /**
     * Get framework version
     */
    public static function version() {
        return self::VERSION;
    }

    /**
     * Require include com validação
     *
     * @deprecated Use autoloader ou require direto
     * @param string $file Caminho relativo ao ROOT_PATH
     * @param bool $critical Se true, exibe erro fatal
     * @return bool
     */
    public static function requireInclude($file, $critical = true) {
        $fullPath = ROOT_PATH . $file;

        if (!file_exists($fullPath)) {
            $error = "Include não encontrado: {$file}";

            Logger::critical($error, [
                'file' => $file,
                'full_path' => $fullPath,
                'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            if ($critical) {
                http_response_code(200);
                $siteName = Settings::get('site_name', 'AEGIS Framework');
                echo '<!DOCTYPE html>';
                echo '<html lang="pt-BR">';
                echo '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
                echo '<title>' . htmlspecialchars($siteName) . '</title>';
                echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;';
                echo 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f5f5;}';
                echo 'h1{font-size:32px;color:#333;text-align:center;}</style></head>';
                echo '<body><h1>' . htmlspecialchars($siteName) . '</h1></body></html>';
                exit;
            }

            return false;
        }

        require_once $fullPath;
        return true;
    }

    /**
     * Gerar UUID v4
     *
     * @deprecated Usar Security::generateUUID()
     * @return string
     */
    public static function generateUUID() {
        return Security::generateUUID();
    }
}
