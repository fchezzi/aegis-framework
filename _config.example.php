<?php
/**
 * AEGIS Framework - Configuration Example
 *
 * INSTRUÇÕES:
 * 1. Copie este arquivo para _config.php
 * 2. Preencha com suas credenciais reais
 * 3. NUNCA commite o arquivo _config.php no git!
 */

// Debug Mode (false = production, true = development)
// IMPORTANTE: Mude para true apenas em ambiente de desenvolvimento local
define('DEBUG_MODE', false);

// Database Type (mysql, supabase, none)
define('DB_TYPE', 'mysql');

// MySQL Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_SOCKET', ''); // Para MAMP: /Applications/MAMP/tmp/mysql/mysql.sock
define('DB_NAME', 'seu_banco');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');

// Supabase Configuration
define('SUPABASE_URL', 'https://seu-projeto.supabase.co');
define('SUPABASE_KEY', 'sua_chave_supabase');

// App Configuration
define('APP_URL', 'http://localhost/seu-projeto');

// Admin Panel Customization
define('ADMIN_NAME', 'Aegis');
define('ADMIN_SUBTITLE', 'Painel Administrativo');

// TinyMCE API Key (para editor WYSIWYG)
// Obtenha em: https://www.tiny.cloud/auth/signup/
define('TINYMCE_API_KEY', 'sua_api_key_tinymce');

// Members System (boolean)
define('ENABLE_MEMBERS', true);

// Default Member Group (UUID do grupo padrão que novos members entram automaticamente)
// Deixe null para não adicionar a nenhum grupo por padrão
define('DEFAULT_MEMBER_GROUP', null);

// Installed Modules
define('INSTALLED_MODULES', 'blog,artigos');

// SMTP Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'seu-email@gmail.com');
define('SMTP_PASSWORD', 'sua_senha_app_gmail'); // App Password do Gmail
define('SMTP_FROM_EMAIL', 'seu-email@gmail.com');
define('SMTP_FROM_NAME', 'Seu Nome');
define('SMTP_ENCRYPTION', 'tls'); // tls ou ssl

// RD Station Configuration
define('RDSTATION_ENABLED', false); // true para habilitar
define('RDSTATION_API_KEY', 'sua_api_key_rdstation');

// UptimeRobot Configuration
define('UPTIME_ROBOT_API_KEY', 'sua_api_key_uptimerobot');

// Environment (auto-detected: development or production)
// Override: Uncomment line below to force environment
// define('ENVIRONMENT_OVERRIDE', 'production');

// Paths
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
define('STORAGE_PATH', ROOT_PATH . 'storage/');
define('LOG_PATH', STORAGE_PATH . 'logs/');
define('UPLOAD_PATH', STORAGE_PATH . 'uploads/');
define('CACHE_PATH', STORAGE_PATH . 'cache/');

// Helper function for URLs
if (!function_exists('url')) {
    function url($path = '') {
        return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    }
}
