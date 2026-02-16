<?php
/**
 * ServiceProvider
 * Sistema de providers para carregamento lazy de serviços
 *
 * Permite:
 * - Registrar serviços sob demanda
 * - Carregar classes apenas quando necessário
 * - Inicialização diferida (deferred)
 * - Boot hooks
 *
 * @example
 * // Registrar provider
 * ServiceProvider::register('mailer', function() {
 *     return new Mailer(config('mail'));
 * });
 *
 * // Usar (carrega apenas quando chamado)
 * $mailer = ServiceProvider::get('mailer');
 * $mailer->send($email);
 *
 * // Ou via helper
 * app('mailer')->send($email);
 */

class ServiceProvider {

    /**
     * Providers registrados
     */
    private static $providers = [];

    /**
     * Instâncias resolvidas (singletons)
     */
    private static $resolved = [];

    /**
     * Se provider é singleton
     */
    private static $singletons = [];

    /**
     * Callbacks de boot
     */
    private static $bootCallbacks = [];

    /**
     * Se já foi iniciado (booted)
     */
    private static $booted = false;

    /**
     * Aliases para providers
     */
    private static $aliases = [];

    // ===================
    // REGISTRATION
    // ===================

    /**
     * Registrar um provider
     *
     * @param string $name Nome do serviço
     * @param callable|string $resolver Callback ou nome da classe
     * @param bool $singleton Se deve ser singleton
     */
    public static function register($name, $resolver, $singleton = true) {
        self::$providers[$name] = $resolver;
        self::$singletons[$name] = $singleton;
    }

    /**
     * Registrar singleton (alias para register com singleton=true)
     */
    public static function singleton($name, $resolver) {
        self::register($name, $resolver, true);
    }

    /**
     * Registrar instância já resolvida
     */
    public static function instance($name, $instance) {
        self::$resolved[$name] = $instance;
        self::$singletons[$name] = true;
    }

    /**
     * Registrar alias para um provider
     */
    public static function alias($alias, $name) {
        self::$aliases[$alias] = $name;
    }

    /**
     * Registrar múltiplos providers de uma vez
     */
    public static function registerMany(array $providers) {
        foreach ($providers as $name => $resolver) {
            self::register($name, $resolver);
        }
    }

    // ===================
    // RESOLUTION
    // ===================

    /**
     * Obter/resolver um serviço
     *
     * @param string $name
     * @param array $parameters Parâmetros para o resolver
     * @return mixed
     */
    public static function get($name, array $parameters = []) {
        // Resolver alias
        if (isset(self::$aliases[$name])) {
            $name = self::$aliases[$name];
        }

        // Já resolvido (singleton)?
        if (isset(self::$resolved[$name]) && self::$singletons[$name]) {
            return self::$resolved[$name];
        }

        // Não registrado?
        if (!isset(self::$providers[$name])) {
            throw new RuntimeException("Service not found: {$name}");
        }

        // Resolver
        $resolver = self::$providers[$name];
        $instance = self::resolve($resolver, $parameters);

        // Armazenar se singleton
        if (self::$singletons[$name]) {
            self::$resolved[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Verificar se serviço existe
     */
    public static function has($name) {
        if (isset(self::$aliases[$name])) {
            $name = self::$aliases[$name];
        }

        return isset(self::$providers[$name]) || isset(self::$resolved[$name]);
    }

    /**
     * Resolver um provider
     */
    private static function resolve($resolver, array $parameters = []) {
        // Callable (closure ou array)
        if (is_callable($resolver)) {
            return call_user_func_array($resolver, $parameters);
        }

        // Nome de classe
        if (is_string($resolver) && class_exists($resolver)) {
            return self::buildClass($resolver, $parameters);
        }

        // Instância direta
        return $resolver;
    }

    /**
     * Construir instância de classe com auto-wiring
     */
    private static function buildClass($class, array $parameters = []) {
        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class;
        }

        $dependencies = self::resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolver dependências do construtor
     */
    private static function resolveDependencies(array $params, array $provided = []) {
        $dependencies = [];

        foreach ($params as $param) {
            $name = $param->getName();

            // Parâmetro fornecido explicitamente
            if (isset($provided[$name])) {
                $dependencies[] = $provided[$name];
                continue;
            }

            // Tentar resolver por type hint
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();

                // Verificar se temos provider para este tipo
                if (self::has($typeName)) {
                    $dependencies[] = self::get($typeName);
                    continue;
                }

                // Tentar instanciar diretamente
                if (class_exists($typeName)) {
                    $dependencies[] = self::buildClass($typeName);
                    continue;
                }
            }

            // Valor padrão
            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
                continue;
            }

            // Nullable
            if ($param->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new RuntimeException("Cannot resolve parameter: {$name}");
        }

        return $dependencies;
    }

    // ===================
    // LIFECYCLE
    // ===================

    /**
     * Registrar callback de boot
     */
    public static function booting(callable $callback) {
        if (self::$booted) {
            // Já iniciado, executar imediatamente
            $callback();
        } else {
            self::$bootCallbacks[] = $callback;
        }
    }

    /**
     * Iniciar todos os providers
     */
    public static function boot() {
        if (self::$booted) {
            return;
        }

        foreach (self::$bootCallbacks as $callback) {
            $callback();
        }

        self::$booted = true;
    }

    /**
     * Verificar se já foi iniciado
     */
    public static function isBooted() {
        return self::$booted;
    }

    // ===================
    // UTILITY
    // ===================

    /**
     * Limpar instância resolvida (forçar re-resolução)
     */
    public static function forget($name) {
        if (isset(self::$aliases[$name])) {
            $name = self::$aliases[$name];
        }

        unset(self::$resolved[$name]);
    }

    /**
     * Limpar todas as instâncias
     */
    public static function flush() {
        self::$resolved = [];
    }

    /**
     * Listar providers registrados
     */
    public static function getProviders() {
        return array_keys(self::$providers);
    }

    /**
     * Listar instâncias resolvidas
     */
    public static function getResolved() {
        return array_keys(self::$resolved);
    }

    /**
     * Obter aliases
     */
    public static function getAliases() {
        return self::$aliases;
    }

    /**
     * Resetar tudo (útil para testes)
     */
    public static function reset() {
        self::$providers = [];
        self::$resolved = [];
        self::$singletons = [];
        self::$bootCallbacks = [];
        self::$aliases = [];
        self::$booted = false;
    }
}

// ===================
// HELPER FUNCTION
// ===================

if (!function_exists('app')) {
    /**
     * Obter instância de serviço do container
     *
     * @param string|null $name
     * @return mixed
     */
    function app($name = null) {
        if ($name === null) {
            return ServiceProvider::class;
        }

        return ServiceProvider::get($name);
    }
}

if (!function_exists('resolve')) {
    /**
     * Alias para app()
     */
    function resolve($name) {
        return ServiceProvider::get($name);
    }
}
