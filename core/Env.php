<?php
/**
 * Environment Variables Loader
 * Carrega variáveis do arquivo .env
 */

class Env {

    private static $loaded = false;
    private static $validated = false;
    private static $vars = [];

    /**
     * Carregar arquivo .env
     */
    public static function load($file = null) {
        if (self::$loaded) {
            return; // Já carregado
        }

        if ($file === null) {
            $file = ROOT_PATH . '.env';
        }

        if (!file_exists($file)) {
            // .env não existe, continuar sem ele (usar _config.php)
            self::$loaded = true;
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;

            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse: KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Validar nome da variável
                if (empty($key) || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
                    error_log("⚠️ .env linha {$lineNumber}: Nome de variável inválido '{$key}'");
                    continue;
                }

                // Remover aspas
                $value = trim($value, '"\'');

                // Salvar em cache
                self::$vars[$key] = $value;

                // Definir como constante se não existir
                if (!defined($key)) {
                    define($key, $value);
                }

                // Também adicionar em $_ENV e $_SERVER
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Obter valor de variável de ambiente
     *
     * @param string $key Nome da variável
     * @param mixed $default Valor padrão se não existir
     * @return mixed
     */
    public static function get($key, $default = null) {
        // Tentar carregar se ainda não foi
        if (!self::$loaded) {
            self::load();
        }

        // Prioridade: constante > cache > $_ENV > $_SERVER > default
        if (defined($key)) {
            return constant($key);
        }

        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    /**
     * Verificar se variável existe
     *
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }

        return defined($key)
            || isset(self::$vars[$key])
            || isset($_ENV[$key])
            || isset($_SERVER[$key]);
    }

    /**
     * Obter todas as variáveis carregadas
     *
     * @return array
     */
    public static function all() {
        if (!self::$loaded) {
            self::load();
        }

        return self::$vars;
    }

    /**
     * Verificar se está em ambiente de produção
     *
     * @return bool
     */
    public static function isProduction() {
        return strtolower(self::get('ENVIRONMENT', 'production')) === 'production';
    }

    /**
     * Verificar se está em ambiente de desenvolvimento
     *
     * @return bool
     */
    public static function isDevelopment() {
        return strtolower(self::get('ENVIRONMENT', 'development')) === 'development';
    }

    /**
     * Validar variáveis obrigatórias do .env
     *
     * @param array $errors Array de erros encontrados (passado por referência)
     * @param bool $forceRevalidate Forçar revalidação mesmo se já validado
     * @return bool True se válido, false se houver erros
     */
    public static function validate(&$errors = [], $forceRevalidate = false) {
        // Cache: se já validado e sem erros, retornar true
        if (self::$validated && !$forceRevalidate) {
            $errors = [];
            return true;
        }

        $errors = [];

        $dbType = self::get('DB_TYPE', 'none');

        // Validar DB_TYPE
        if (!in_array($dbType, ['mysql', 'supabase', 'none'])) {
            $errors[] = "DB_TYPE inválido: '{$dbType}'. Use: mysql, supabase ou none";
        }

        // Validar MySQL
        if ($dbType === 'mysql') {
            if (empty(self::get('DB_HOST'))) {
                $errors[] = "DB_HOST é obrigatório quando DB_TYPE=mysql";
            }
            if (empty(self::get('DB_NAME'))) {
                $errors[] = "DB_NAME é obrigatório quando DB_TYPE=mysql";
            }
            if (empty(self::get('DB_USER'))) {
                $errors[] = "DB_USER é obrigatório quando DB_TYPE=mysql";
            }
        }

        // Validar Supabase
        if ($dbType === 'supabase') {
            $supabaseUrl = self::get('SUPABASE_URL');
            $supabaseKey = self::get('SUPABASE_KEY');

            if (empty($supabaseUrl)) {
                $errors[] = "SUPABASE_URL é obrigatório quando DB_TYPE=supabase";
            } elseif (!filter_var($supabaseUrl, FILTER_VALIDATE_URL)) {
                $errors[] = "SUPABASE_URL inválida: '{$supabaseUrl}'";
            }

            if (empty($supabaseKey)) {
                $errors[] = "SUPABASE_KEY é obrigatório quando DB_TYPE=supabase";
            }
        }

        // Validar APP_URL
        $appUrl = self::get('APP_URL');
        if (!empty($appUrl) && !filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $errors[] = "APP_URL inválida: '{$appUrl}'";
        }

        // Validar ENVIRONMENT
        $environment = self::get('ENVIRONMENT');
        if (!empty($environment) && !in_array($environment, ['development', 'production'])) {
            $errors[] = "ENVIRONMENT inválido: '{$environment}'. Use: development ou production";
        }

        // Validar ENABLE_MEMBERS
        $enableMembers = self::get('ENABLE_MEMBERS');
        if (!empty($enableMembers) && !in_array($enableMembers, ['true', 'false'])) {
            $errors[] = "ENABLE_MEMBERS inválido: '{$enableMembers}'. Use: true ou false";
        }

        // Validar CACHE_ENABLED
        $cacheEnabled = self::get('CACHE_ENABLED');
        if (!empty($cacheEnabled) && !in_array($cacheEnabled, ['true', 'false'])) {
            $errors[] = "CACHE_ENABLED inválido: '{$cacheEnabled}'. Use: true ou false";
        }

        // Validar permissões do arquivo .env (apenas em sistemas Unix)
        if (file_exists(ROOT_PATH . '.env') && DIRECTORY_SEPARATOR === '/') {
            $perms = fileperms(ROOT_PATH . '.env') & 0777;
            // Permitir: 0600 (owner apenas), 0640 (owner+group read), 0644 (todos read)
            // Rejeitar: 0666, 0777 (escrita por outros)
            if ($perms & 0002) { // World writable
                $errors[] = ".env tem permissões inseguras (" . decoct($perms) . "). Use: chmod 600 .env";
            }
        }

        // Cachear resultado se válido
        $isValid = empty($errors);
        if ($isValid) {
            self::$validated = true;
        }

        return $isValid;
    }
}
