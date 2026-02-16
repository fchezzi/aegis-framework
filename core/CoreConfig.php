<?php
/**
 * CoreConfig
 * Gestão de configurações do framework
 */

class CoreConfig {

    private static $config = [];

    /**
     * Recarregar configurações em cache interno
     */
    public static function load() {
        self::$config = [
            'DB_TYPE' => defined('DB_TYPE') ? DB_TYPE : 'none',
            'DB_HOST' => defined('DB_HOST') ? DB_HOST : '',
            'DB_NAME' => defined('DB_NAME') ? DB_NAME : '',
            'DB_USER' => defined('DB_USER') ? DB_USER : '',
            'DB_PASS' => defined('DB_PASS') ? DB_PASS : '',
            'SUPABASE_URL' => defined('SUPABASE_URL') ? SUPABASE_URL : '',
            'SUPABASE_KEY' => defined('SUPABASE_KEY') ? SUPABASE_KEY : '',
            'APP_URL' => defined('APP_URL') ? APP_URL : '',
            'ADMIN_NAME' => defined('ADMIN_NAME') ? ADMIN_NAME : 'AEGIS',
            'ADMIN_SUBTITLE' => defined('ADMIN_SUBTITLE') ? ADMIN_SUBTITLE : 'Painel Administrativo',
            'ENVIRONMENT' => defined('ENVIRONMENT') ? ENVIRONMENT : 'production',
            'ENABLE_MEMBERS' => defined('ENABLE_MEMBERS') ? (ENABLE_MEMBERS === 'true' || ENABLE_MEMBERS === true) : false
        ];

        return true;
    }

    /**
     * Pegar valor de config
     */
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }

    /**
     * Verificar se está instalado
     */
    public static function isInstalled() {
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../';
        return file_exists($rootPath . '.env') || file_exists($rootPath . '_config.php');
    }

    /**
     * Gerar arquivo _config.php
     */
    public static function generate($data) {
        // Auto-detectar ambiente se não especificado
        if (!isset($data['ENVIRONMENT']) || empty($data['ENVIRONMENT'])) {
            $data['ENVIRONMENT'] = CoreEnvironment::detect();
        }

        $template = <<<'PHP'
<?php
/**
 * AEGIS Framework - Configuration
 * Generated automatically by setup wizard
 */

// Debug Mode (false = production, true = development)
// IMPORTANTE: Mude para true apenas em ambiente de desenvolvimento local
define('DEBUG_MODE', false);

// Database Type (mysql, supabase, none)
define('DB_TYPE', '{DB_TYPE}');

// MySQL Configuration
define('DB_HOST', '{DB_HOST}');
define('DB_NAME', '{DB_NAME}');
define('DB_USER', '{DB_USER}');
define('DB_PASS', '{DB_PASS}');

// Supabase Configuration
define('SUPABASE_URL', '{SUPABASE_URL}');
define('SUPABASE_KEY', '{SUPABASE_KEY}');

// App Configuration
define('APP_URL', '{APP_URL}');

// Admin Panel Customization
define('ADMIN_NAME', '{ADMIN_NAME}');
define('ADMIN_SUBTITLE', '{ADMIN_SUBTITLE}');

// TinyMCE API Key (para editor WYSIWYG)
define('TINYMCE_API_KEY', '{TINYMCE_API_KEY}');

// Members System (boolean)
define('ENABLE_MEMBERS', {ENABLE_MEMBERS});

// Environment (auto-detected: development or production)
// Override: Uncomment line below to force environment
// define('ENVIRONMENT_OVERRIDE', 'production');

// Paths
define('ROOT_PATH', __DIR__ . '/');
define('STORAGE_PATH', ROOT_PATH . 'storage/');
define('LOG_PATH', STORAGE_PATH . 'logs/');
define('UPLOAD_PATH', STORAGE_PATH . 'uploads/');
define('CACHE_PATH', STORAGE_PATH . 'cache/');

// Helper function for URLs
if (!function_exists('url')) {
    function url(DOLLAR_SIGNpath = '') {
        return rtrim(APP_URL, '/') . '/' . ltrim(DOLLAR_SIGNpath, '/');
    }
}
PHP;

        // Adicionar valores padrão para campos opcionais
        if (!isset($data['ADMIN_NAME']) || empty($data['ADMIN_NAME'])) {
            $data['ADMIN_NAME'] = 'AEGIS';
        }
        if (!isset($data['ADMIN_SUBTITLE']) || empty($data['ADMIN_SUBTITLE'])) {
            $data['ADMIN_SUBTITLE'] = 'Painel Administrativo';
        }

        // Substituir placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        // Substituir $ escapado
        $template = str_replace('DOLLAR_SIGN', '$', $template);

        // Salvar
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/../';
        $configFile = $rootPath . '_config.php';
        return file_put_contents($configFile, $template) !== false;
    }
}
