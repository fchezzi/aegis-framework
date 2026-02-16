<?php
/**
 * Autoloader
 * PSR-4 compliant autoloader para AEGIS
 *
 * Suporta:
 * - Classes sem namespace (legado)
 * - Classes com namespace PSR-4
 * - Mapeamento customizado
 *
 * Namespaces registrados:
 * - Aegis\Core\       => core/
 * - Aegis\Database\   => database/
 * - Aegis\Admin\      => admin/controllers/
 * - Aegis\Public\     => public/controllers/
 * - Aegis\Api\        => api/controllers/
 * - Aegis\Modules\    => modules/
 *
 * @example
 * // Classe sem namespace (legado)
 * class Router { } // core/Router.php
 *
 * // Classe com namespace (novo)
 * namespace Aegis\Core;
 * class Router { } // core/Router.php
 *
 * // Uso
 * use Aegis\Core\Router;
 * Router::get('/home', ...);
 */

class Autoloader {

    /**
     * Diretórios para classes sem namespace (legado)
     */
    private static $directories = [
        'core/',
        'database/',
        'database/adapters/',
        'admin/controllers/',
        'public/controllers/',
        'frontend/controllers/',
        'api/controllers/'
    ];

    /**
     * Mapeamento PSR-4: namespace => diretório
     */
    private static $namespaces = [
        'Aegis\\Core\\'       => 'core/',
        'Aegis\\Database\\'   => 'database/',
        'Aegis\\Admin\\'      => 'admin/controllers/',
        'Aegis\\Public\\'     => 'public/controllers/',
        'Aegis\\Api\\'        => 'api/controllers/',
        'Aegis\\Modules\\'    => 'modules/'
    ];

    /**
     * Cache de classes já carregadas
     */
    private static $loaded = [];

    /**
     * Base path do projeto
     */
    private static $basePath = null;

    /**
     * Registrar autoloader
     */
    public static function register() {
        self::$basePath = dirname(__DIR__) . '/';
        spl_autoload_register([self::class, 'load']);
    }

    /**
     * Carregar classe
     *
     * @param string $class Nome completo da classe (com namespace)
     * @return bool
     */
    private static function load($class) {
        // Já carregada?
        if (isset(self::$loaded[$class])) {
            return true;
        }

        // Tentar PSR-4 primeiro (classes com namespace)
        if (strpos($class, '\\') !== false) {
            if (self::loadPsr4($class)) {
                return true;
            }
        }

        // Fallback: classes sem namespace (legado)
        if (self::loadLegacy($class)) {
            return true;
        }

        return false;
    }

    /**
     * Carregar classe PSR-4
     *
     * @param string $class Nome com namespace
     * @return bool
     */
    private static function loadPsr4($class) {
        foreach (self::$namespaces as $namespace => $directory) {
            // Verificar se classe pertence a este namespace
            if (strpos($class, $namespace) === 0) {
                // Remover namespace base
                $relativeClass = substr($class, strlen($namespace));

                // Converter namespace para path
                $relativePath = str_replace('\\', '/', $relativeClass);

                $file = self::$basePath . $directory . $relativePath . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    self::$loaded[$class] = true;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Carregar classe legado (sem namespace)
     *
     * @param string $class Nome simples da classe
     * @return bool
     */
    private static function loadLegacy($class) {
        foreach (self::$directories as $directory) {
            $file = self::$basePath . $directory . $class . '.php';

            if (file_exists($file)) {
                require_once $file;
                self::$loaded[$class] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Adicionar diretório para classes legado
     *
     * @param string $directory Caminho relativo ao ROOT_PATH
     */
    public static function addDirectory($directory) {
        if (!in_array($directory, self::$directories)) {
            self::$directories[] = $directory;
        }
    }

    /**
     * Registrar namespace PSR-4
     *
     * @param string $namespace Namespace (com \\ no final)
     * @param string $directory Diretório relativo ao ROOT_PATH
     */
    public static function addNamespace($namespace, $directory) {
        // Garantir que namespace termina com \\
        if (substr($namespace, -1) !== '\\') {
            $namespace .= '\\';
        }

        // Garantir que diretório termina com /
        if (substr($directory, -1) !== '/') {
            $directory .= '/';
        }

        self::$namespaces[$namespace] = $directory;
    }

    /**
     * Obter diretórios registrados
     *
     * @return array
     */
    public static function getDirectories() {
        return self::$directories;
    }

    /**
     * Obter namespaces registrados
     *
     * @return array
     */
    public static function getNamespaces() {
        return self::$namespaces;
    }

    /**
     * Verificar se classe existe (sem carregar)
     *
     * @param string $class
     * @return bool
     */
    public static function classExists($class) {
        // Já carregada
        if (class_exists($class, false)) {
            return true;
        }

        // PSR-4
        if (strpos($class, '\\') !== false) {
            foreach (self::$namespaces as $namespace => $directory) {
                if (strpos($class, $namespace) === 0) {
                    $relativeClass = substr($class, strlen($namespace));
                    $relativePath = str_replace('\\', '/', $relativeClass);
                    $file = self::$basePath . $directory . $relativePath . '.php';

                    if (file_exists($file)) {
                        return true;
                    }
                }
            }
        }

        // Legado
        foreach (self::$directories as $directory) {
            $file = self::$basePath . $directory . $class . '.php';
            if (file_exists($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Debug: listar todas as classes carregadas
     *
     * @return array
     */
    public static function getLoadedClasses() {
        return array_keys(self::$loaded);
    }
}
