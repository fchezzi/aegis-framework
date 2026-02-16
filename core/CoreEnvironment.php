<?php
/**
 * CoreEnvironment
 * Detecção e gestão de ambiente (development/production)
 */

class CoreEnvironment {

    private static $environment = null;

    /**
     * Detectar ambiente automaticamente
     *
     * @return string 'development' ou 'production'
     */
    public static function detect() {
        if (self::$environment !== null) {
            return self::$environment;
        }

        // Verificar override manual
        if (defined('ENVIRONMENT_OVERRIDE')) {
            self::$environment = ENVIRONMENT_OVERRIDE;
            return self::$environment;
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

        // Desenvolvimento (localhost, IP local, porta não-padrão)
        $isDev = (
            strpos($host, 'localhost') !== false ||
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '192.168.') !== false ||
            strpos($host, '10.0.') !== false ||
            strpos($serverAddr, '192.168.') !== false ||
            strpos($serverAddr, '10.0.') !== false ||
            strpos($host, ':') !== false
        );

        self::$environment = $isDev ? 'development' : 'production';
        return self::$environment;
    }

    /**
     * Verificar se está em desenvolvimento
     */
    public static function isDev() {
        return self::detect() === 'development';
    }

    /**
     * Verificar se está em produção
     */
    public static function isProduction() {
        return self::detect() === 'production';
    }

    /**
     * Forçar ambiente (override)
     */
    public static function force($env) {
        self::$environment = $env;
    }

    /**
     * Get environment name
     */
    public static function name() {
        return self::detect();
    }

    /**
     * Configurar PHP baseado no ambiente
     */
    public static function configure() {
        if (self::isDev()) {
            // Desenvolvimento - Mostrar erros
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            // Produção - Ocultar erros
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL);
            ini_set('log_errors', 1);
            // Só configurar error_log se LOG_PATH estiver definido
            if (defined('LOG_PATH')) {
                ini_set('error_log', LOG_PATH . 'php-errors.log');
            }
        }
    }
}
