<?php
/**
 * Container
 * Simple Dependency Injection Container
 *
 * Funcionalidades:
 * - Registro de serviços (bind)
 * - Singletons
 * - Auto-wiring básico
 * - Aliases
 *
 * @example
 * // Registrar serviço
 * Container::bind('mailer', function() {
 *     return new Mailer(Config::get('mail'));
 * });
 *
 * // Registrar singleton
 * Container::singleton('db', function() {
 *     return DB::connect();
 * });
 *
 * // Resolver
 * $mailer = Container::make('mailer');
 *
 * // Alias
 * Container::alias('database', 'db');
 */

class Container {

    /**
     * Bindings registrados
     */
    private static $bindings = [];

    /**
     * Instâncias singleton
     */
    private static $instances = [];

    /**
     * Aliases
     */
    private static $aliases = [];

    /**
     * Registrar binding
     *
     * @param string $abstract Nome/interface
     * @param callable|string|null $concrete Implementação ou closure
     * @param bool $shared Se é singleton
     */
    public static function bind($abstract, $concrete = null, $shared = false) {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        self::$bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * Registrar singleton
     *
     * @param string $abstract
     * @param callable|string|null $concrete
     */
    public static function singleton($abstract, $concrete = null) {
        self::bind($abstract, $concrete, true);
    }

    /**
     * Registrar instância existente
     *
     * @param string $abstract
     * @param mixed $instance
     */
    public static function instance($abstract, $instance) {
        self::$instances[$abstract] = $instance;
    }

    /**
     * Criar alias
     *
     * @param string $alias
     * @param string $abstract
     */
    public static function alias($alias, $abstract) {
        self::$aliases[$alias] = $abstract;
    }

    /**
     * Resolver dependência
     *
     * @param string $abstract
     * @param array $parameters Parâmetros adicionais
     * @return mixed
     */
    public static function make($abstract, $parameters = []) {
        // Resolver alias
        $abstract = self::getAlias($abstract);

        // Retornar instância existente se singleton
        if (isset(self::$instances[$abstract])) {
            return self::$instances[$abstract];
        }

        // Obter concrete
        $concrete = self::getConcrete($abstract);

        // Build
        $object = self::build($concrete, $parameters);

        // Salvar se singleton
        if (self::isShared($abstract)) {
            self::$instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Verificar se binding existe
     *
     * @param string $abstract
     * @return bool
     */
    public static function has($abstract) {
        $abstract = self::getAlias($abstract);
        return isset(self::$bindings[$abstract]) || isset(self::$instances[$abstract]);
    }

    /**
     * Resolver alias recursivamente
     *
     * @param string $abstract
     * @return string
     */
    private static function getAlias($abstract) {
        if (isset(self::$aliases[$abstract])) {
            return self::getAlias(self::$aliases[$abstract]);
        }
        return $abstract;
    }

    /**
     * Obter concrete de um abstract
     *
     * @param string $abstract
     * @return mixed
     */
    private static function getConcrete($abstract) {
        if (isset(self::$bindings[$abstract])) {
            return self::$bindings[$abstract]['concrete'];
        }
        return $abstract;
    }

    /**
     * Verificar se é shared (singleton)
     *
     * @param string $abstract
     * @return bool
     */
    private static function isShared($abstract) {
        return isset(self::$bindings[$abstract]['shared'])
            && self::$bindings[$abstract]['shared'] === true;
    }

    /**
     * Construir objeto
     *
     * @param mixed $concrete
     * @param array $parameters
     * @return mixed
     */
    private static function build($concrete, $parameters = []) {
        // Se é closure, executar
        if ($concrete instanceof Closure) {
            return $concrete(new self(), $parameters);
        }

        // Se é callable (mas não closure)
        if (is_callable($concrete)) {
            return call_user_func_array($concrete, $parameters);
        }

        // Se não é string, retornar como está
        if (!is_string($concrete)) {
            return $concrete;
        }

        // Tentar auto-wire via reflection
        return self::resolve($concrete, $parameters);
    }

    /**
     * Resolver classe via reflection (auto-wiring)
     *
     * @param string $class
     * @param array $parameters
     * @return object
     */
    private static function resolve($class, $parameters = []) {
        // Verificar se classe existe
        if (!class_exists($class)) {
            throw new Exception("Class {$class} does not exist");
        }

        $reflector = new ReflectionClass($class);

        // Verificar se é instanciável
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        // Sem construtor = instância simples
        if ($constructor === null) {
            return new $class();
        }

        // Resolver dependências do construtor
        $dependencies = self::resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolver dependências de parâmetros
     *
     * @param ReflectionParameter[] $params
     * @param array $primitives Valores primitivos fornecidos
     * @return array
     */
    private static function resolveDependencies($params, $primitives = []) {
        $dependencies = [];

        foreach ($params as $param) {
            $name = $param->getName();

            // Valor fornecido manualmente?
            if (isset($primitives[$name])) {
                $dependencies[] = $primitives[$name];
                continue;
            }

            // Obter type hint
            $type = $param->getType();

            if ($type === null || $type->isBuiltin()) {
                // Tipo primitivo ou sem tipo
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter \${$name} for class");
                }
            } else {
                // É uma classe - resolver recursivamente
                $className = $type->getName();
                $dependencies[] = self::make($className);
            }
        }

        return $dependencies;
    }

    /**
     * Executar método com injeção de dependências
     *
     * @param object|string $class
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function call($class, $method, $parameters = []) {
        if (is_string($class)) {
            $class = self::make($class);
        }

        $reflector = new ReflectionMethod($class, $method);
        $dependencies = self::resolveDependencies($reflector->getParameters(), $parameters);

        return $reflector->invokeArgs($class, $dependencies);
    }

    /**
     * Limpar container
     */
    public static function flush() {
        self::$bindings = [];
        self::$instances = [];
        self::$aliases = [];
    }

    /**
     * Obter todas as bindings (debug)
     *
     * @return array
     */
    public static function getBindings() {
        return self::$bindings;
    }

    /**
     * Registrar serviços padrão do AEGIS
     */
    public static function registerDefaults() {
        // Database
        self::singleton('db', function() {
            return DB::connect();
        });
        self::alias('database', 'db');
        self::alias(DatabaseInterface::class, 'db');

        // Cache
        self::singleton('cache', function() {
            return new SimpleCache();
        });

        // Logger
        if (class_exists('Logger')) {
            self::singleton('logger', function() {
                return new Logger();
            });
        }
    }
}
