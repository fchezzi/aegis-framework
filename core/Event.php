<?php
/**
 * Event
 * Sistema de eventos e hooks para extensibilidade
 *
 * Permite que módulos e plugins reajam a eventos do sistema
 * sem modificar o código core.
 *
 * Eventos built-in:
 * - auth.login: Após login bem sucedido
 * - auth.logout: Após logout
 * - auth.failed: Tentativa de login falhou
 * - member.created: Novo membro criado
 * - member.updated: Membro atualizado
 * - member.deleted: Membro deletado
 * - page.view: Página visualizada
 * - module.installed: Módulo instalado
 * - module.uninstalled: Módulo desinstalado
 *
 * @example
 * // Registrar listener
 * Event::on('auth.login', function($data) {
 *     Logger::logInfo('Usuário logou', ['user' => $data['email']]);
 * });
 *
 * // Registrar com prioridade (menor = executa primeiro)
 * Event::on('auth.login', function($data) {
 *     // Executa primeiro
 * }, 10);
 *
 * // Disparar evento
 * Event::fire('auth.login', ['email' => $email, 'user_id' => $id]);
 *
 * // Filtros (modificam dados)
 * Event::filter('page.content', function($content, $page) {
 *     return str_replace('{{year}}', date('Y'), $content);
 * });
 *
 * $content = Event::applyFilters('page.content', $content, $page);
 */

class Event {

    /**
     * Listeners registrados por evento
     * [event_name => [[callback, priority], ...]]
     */
    private static $listeners = [];

    /**
     * Filtros registrados por nome
     * [filter_name => [[callback, priority], ...]]
     */
    private static $filters = [];

    /**
     * Eventos disparados (para debug)
     */
    private static $fired = [];

    /**
     * Se deve logar eventos (debug)
     */
    private static $debug = false;

    /**
     * Registrar listener para evento
     *
     * @param string $event Nome do evento
     * @param callable $callback Função a executar
     * @param int $priority Prioridade (menor = executa primeiro)
     */
    public static function on($event, $callback, $priority = 50) {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }

        self::$listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Ordenar por prioridade
        usort(self::$listeners[$event], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }

    /**
     * Alias para on()
     */
    public static function listen($event, $callback, $priority = 50) {
        self::on($event, $callback, $priority);
    }

    /**
     * Remover listener
     *
     * @param string $event Nome do evento
     * @param callable|null $callback Callback específico ou null para remover todos
     */
    public static function off($event, $callback = null) {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        if ($callback === null) {
            unset(self::$listeners[$event]);
            return;
        }

        self::$listeners[$event] = array_filter(
            self::$listeners[$event],
            function($listener) use ($callback) {
                return $listener['callback'] !== $callback;
            }
        );
    }

    /**
     * Disparar evento
     *
     * @param string $event Nome do evento
     * @param array $data Dados a passar para listeners
     * @return array Resultados dos listeners
     */
    public static function fire($event, $data = []) {
        self::$fired[] = [
            'event' => $event,
            'data' => $data,
            'time' => microtime(true)
        ];

        if (self::$debug) {
            Logger::logDebug("Event fired: {$event}", $data);
        }

        if (!isset(self::$listeners[$event])) {
            return [];
        }

        $results = [];

        foreach (self::$listeners[$event] as $listener) {
            try {
                $result = call_user_func($listener['callback'], $data);
                $results[] = $result;

                // Se retornar false, para a propagação
                if ($result === false) {
                    break;
                }
            } catch (Exception $e) {
                Logger::logError("Event listener error: {$event}", [
                    'exception' => $e
                ]);
            }
        }

        return $results;
    }

    /**
     * Alias para fire()
     */
    public static function dispatch($event, $data = []) {
        return self::fire($event, $data);
    }

    /**
     * Disparar evento apenas uma vez
     *
     * @param string $event
     * @param callable $callback
     * @param int $priority
     */
    public static function once($event, $callback, $priority = 50) {
        $wrapper = function($data) use ($event, $callback, &$wrapper) {
            self::off($event, $wrapper);
            return call_user_func($callback, $data);
        };

        self::on($event, $wrapper, $priority);
    }

    /**
     * Verificar se evento tem listeners
     *
     * @param string $event
     * @return bool
     */
    public static function hasListeners($event) {
        return !empty(self::$listeners[$event]);
    }

    /**
     * Obter quantidade de listeners
     *
     * @param string $event
     * @return int
     */
    public static function countListeners($event) {
        return count(self::$listeners[$event] ?? []);
    }

    // ===================
    // FILTROS
    // ===================

    /**
     * Registrar filtro
     *
     * @param string $name Nome do filtro
     * @param callable $callback Função que modifica o valor
     * @param int $priority Prioridade
     */
    public static function filter($name, $callback, $priority = 50) {
        if (!isset(self::$filters[$name])) {
            self::$filters[$name] = [];
        }

        self::$filters[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        usort(self::$filters[$name], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }

    /**
     * Aplicar filtros a um valor
     *
     * @param string $name Nome do filtro
     * @param mixed $value Valor inicial
     * @param mixed ...$args Argumentos adicionais
     * @return mixed Valor filtrado
     */
    public static function applyFilters($name, $value, ...$args) {
        if (!isset(self::$filters[$name])) {
            return $value;
        }

        foreach (self::$filters[$name] as $filter) {
            try {
                $value = call_user_func($filter['callback'], $value, ...$args);
            } catch (Exception $e) {
                Logger::logError("Filter error: {$name}", [
                    'exception' => $e
                ]);
            }
        }

        return $value;
    }

    /**
     * Remover filtro
     *
     * @param string $name
     * @param callable|null $callback
     */
    public static function removeFilter($name, $callback = null) {
        if (!isset(self::$filters[$name])) {
            return;
        }

        if ($callback === null) {
            unset(self::$filters[$name]);
            return;
        }

        self::$filters[$name] = array_filter(
            self::$filters[$name],
            function($filter) use ($callback) {
                return $filter['callback'] !== $callback;
            }
        );
    }

    // ===================
    // BUILT-IN EVENTS
    // ===================

    /**
     * Eventos de autenticação
     */
    public static function onLogin($callback, $priority = 50) {
        self::on('auth.login', $callback, $priority);
    }

    public static function onLogout($callback, $priority = 50) {
        self::on('auth.logout', $callback, $priority);
    }

    public static function onLoginFailed($callback, $priority = 50) {
        self::on('auth.failed', $callback, $priority);
    }

    /**
     * Eventos de member
     */
    public static function onMemberCreated($callback, $priority = 50) {
        self::on('member.created', $callback, $priority);
    }

    public static function onMemberUpdated($callback, $priority = 50) {
        self::on('member.updated', $callback, $priority);
    }

    public static function onMemberDeleted($callback, $priority = 50) {
        self::on('member.deleted', $callback, $priority);
    }

    /**
     * Eventos de página
     */
    public static function onPageView($callback, $priority = 50) {
        self::on('page.view', $callback, $priority);
    }

    /**
     * Eventos de módulo
     */
    public static function onModuleInstalled($callback, $priority = 50) {
        self::on('module.installed', $callback, $priority);
    }

    public static function onModuleUninstalled($callback, $priority = 50) {
        self::on('module.uninstalled', $callback, $priority);
    }

    // ===================
    // DEBUG & UTILS
    // ===================

    /**
     * Habilitar modo debug
     */
    public static function enableDebug() {
        self::$debug = true;
    }

    /**
     * Desabilitar modo debug
     */
    public static function disableDebug() {
        self::$debug = false;
    }

    /**
     * Obter eventos disparados (debug)
     *
     * @return array
     */
    public static function getFired() {
        return self::$fired;
    }

    /**
     * Obter todos os listeners registrados (debug)
     *
     * @return array
     */
    public static function getListeners() {
        return self::$listeners;
    }

    /**
     * Obter todos os filtros registrados (debug)
     *
     * @return array
     */
    public static function getFilters() {
        return self::$filters;
    }

    /**
     * Limpar todos os listeners e filtros
     */
    public static function flush() {
        self::$listeners = [];
        self::$filters = [];
        self::$fired = [];
    }

    /**
     * Registrar listeners de um arquivo de configuração
     *
     * @param string $file Caminho para arquivo PHP que retorna array
     */
    public static function loadFromFile($file) {
        if (!file_exists($file)) {
            return;
        }

        $events = require $file;

        if (!is_array($events)) {
            return;
        }

        foreach ($events as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                if (is_callable($listener)) {
                    self::on($event, $listener);
                } elseif (is_array($listener) && isset($listener['callback'])) {
                    self::on($event, $listener['callback'], $listener['priority'] ?? 50);
                }
            }
        }
    }
}
