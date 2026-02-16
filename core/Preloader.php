<?php
/**
 * Preloader
 * Otimização de carregamento via OPcache preloading (PHP 7.4+)
 *
 * Este arquivo pode ser usado com opcache.preload para
 * carregar classes frequentemente usadas na inicialização do PHP.
 *
 * @example
 * // Em php.ini:
 * // opcache.preload=/path/to/aegis/core/Preloader.php
 * // opcache.preload_user=www-data
 *
 * // Ou gerar lista dinamicamente:
 * Preloader::generate();
 */

class Preloader {

    /**
     * Classes core que devem ser preloaded
     */
    private static $coreClasses = [
        'Core',
        'Router',
        'Request',
        'Response',
        'View',
        'DB',
        'Model',
        'Session',
        'Auth',
        'Validator',
        'Cache',
        'Logger',
        'Security',
        'Middleware'
    ];

    /**
     * Arquivos adicionais para preload
     */
    private static $additionalFiles = [];

    /**
     * Estatísticas de preload
     */
    private static $stats = [
        'loaded' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    /**
     * Executar preload
     * Este método é chamado pelo opcache.preload
     */
    public static function load() {
        if (!self::isSupported()) {
            return false;
        }

        $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/';

        // Preload classes core
        foreach (self::$coreClasses as $class) {
            $file = $basePath . 'core/' . $class . '.php';
            self::preloadFile($file);
        }

        // Preload arquivos adicionais
        foreach (self::$additionalFiles as $file) {
            self::preloadFile($basePath . $file);
        }

        return self::$stats;
    }

    /**
     * Preload de um arquivo específico
     */
    private static function preloadFile($file) {
        if (!file_exists($file)) {
            self::$stats['skipped']++;
            return false;
        }

        try {
            opcache_compile_file($file);
            self::$stats['loaded']++;
            return true;
        } catch (Throwable $e) {
            self::$stats['failed']++;
            return false;
        }
    }

    /**
     * Verificar se preloading é suportado
     */
    public static function isSupported() {
        return PHP_VERSION_ID >= 70400
            && function_exists('opcache_compile_file')
            && ini_get('opcache.enable');
    }

    /**
     * Adicionar classe para preload
     */
    public static function addClass($class) {
        if (!in_array($class, self::$coreClasses)) {
            self::$coreClasses[] = $class;
        }
    }

    /**
     * Adicionar arquivo para preload
     */
    public static function addFile($file) {
        if (!in_array($file, self::$additionalFiles)) {
            self::$additionalFiles[] = $file;
        }
    }

    /**
     * Gerar arquivo de preload otimizado
     */
    public static function generate($outputPath = null) {
        $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/';
        $outputPath = $outputPath ?? $basePath . 'storage/preload.php';

        $files = self::collectFiles($basePath);

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * Preload file gerado automaticamente\n";
        $content .= " * Gerado em: " . date('Y-m-d H:i:s') . "\n";
        $content .= " * \n";
        $content .= " * Adicione ao php.ini:\n";
        $content .= " * opcache.preload={$outputPath}\n";
        $content .= " */\n\n";

        foreach ($files as $file) {
            $content .= "opcache_compile_file('{$file}');\n";
        }

        file_put_contents($outputPath, $content);

        return [
            'path' => $outputPath,
            'files' => count($files)
        ];
    }

    /**
     * Coletar arquivos para preload
     */
    private static function collectFiles($basePath) {
        $files = [];

        // Core classes
        foreach (self::$coreClasses as $class) {
            $file = $basePath . 'core/' . $class . '.php';
            if (file_exists($file)) {
                $files[] = $file;
            }
        }

        // Modelos mais usados (se existirem)
        $modelsPath = $basePath . 'models/';
        if (is_dir($modelsPath)) {
            $modelFiles = glob($modelsPath . '*.php');
            // Limitar a 20 modelos mais recentes
            usort($modelFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $files = array_merge($files, array_slice($modelFiles, 0, 20));
        }

        // Controllers mais usados
        $controllersPath = $basePath . 'controllers/';
        if (is_dir($controllersPath)) {
            $controllerFiles = glob($controllersPath . '*.php');
            usort($controllerFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $files = array_merge($files, array_slice($controllerFiles, 0, 10));
        }

        return array_unique($files);
    }

    /**
     * Obter status do OPcache
     */
    public static function getOpcacheStatus() {
        if (!function_exists('opcache_get_status')) {
            return null;
        }

        $status = opcache_get_status(false);

        if (!$status) {
            return null;
        }

        return [
            'enabled' => $status['opcache_enabled'] ?? false,
            'memory_used' => self::formatBytes($status['memory_usage']['used_memory'] ?? 0),
            'memory_free' => self::formatBytes($status['memory_usage']['free_memory'] ?? 0),
            'memory_wasted' => self::formatBytes($status['memory_usage']['wasted_memory'] ?? 0),
            'hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 2) . '%',
            'scripts_cached' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
            'preload_enabled' => ini_get('opcache.preload') ? true : false
        ];
    }

    /**
     * Limpar OPcache
     */
    public static function clearOpcache() {
        if (function_exists('opcache_reset')) {
            return opcache_reset();
        }
        return false;
    }

    /**
     * Invalidar arquivo específico no OPcache
     */
    public static function invalidate($file, $force = true) {
        if (function_exists('opcache_invalidate')) {
            return opcache_invalidate($file, $force);
        }
        return false;
    }

    /**
     * Formatar bytes para leitura humana
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Obter estatísticas do preload
     */
    public static function getStats() {
        return self::$stats;
    }
}

// Se chamado via opcache.preload, executar automaticamente
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'Preloader.php') {
    Preloader::load();
}
