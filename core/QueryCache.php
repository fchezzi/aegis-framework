<?php
/**
 * QueryCache
 * Sistema de cache automático para queries do banco de dados
 *
 * Funcionalidades:
 * - Cache automático de SELECT queries
 * - Invalidação por tabela
 * - TTL configurável
 * - Tags para invalidação em grupo
 * - Estatísticas de hit/miss
 *
 * @example
 * // Habilitar cache globalmente
 * QueryCache::enable();
 *
 * // Query com cache automático
 * $users = DB::table('users')->where('ativo', 1)->get();
 * // Segunda chamada vem do cache
 *
 * // Forçar cache em query específica
 * $users = DB::table('users')->cache(300)->get();
 *
 * // Query sem cache (bypass)
 * $users = DB::table('users')->noCache()->get();
 *
 * // Invalidar cache de uma tabela
 * QueryCache::invalidate('users');
 *
 * // Invalidar tudo
 * QueryCache::flush();
 */

class QueryCache {

    /**
     * Se o cache está habilitado globalmente
     */
    private static $enabled = false;

    /**
     * TTL padrão em segundos
     */
    private static $defaultTtl = 300; // 5 minutos

    /**
     * Prefixo das chaves de cache
     */
    private static $prefix = 'qc:';

    /**
     * Estatísticas
     */
    private static $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'invalidations' => 0
    ];

    /**
     * Queries que devem ser ignoradas (patterns)
     */
    private static $ignorePatterns = [
        '/^INSERT/i',
        '/^UPDATE/i',
        '/^DELETE/i',
        '/^CREATE/i',
        '/^ALTER/i',
        '/^DROP/i',
        '/^TRUNCATE/i'
    ];

    /**
     * Tabelas que não devem ser cacheadas
     */
    private static $ignoreTables = [
        'sessions',
        'cache',
        'jobs',
        'failed_jobs',
        'migrations'
    ];

    // ===================
    // CONFIGURATION
    // ===================

    /**
     * Habilitar cache de queries
     */
    public static function enable() {
        self::$enabled = true;
    }

    /**
     * Desabilitar cache de queries
     */
    public static function disable() {
        self::$enabled = false;
    }

    /**
     * Verificar se está habilitado
     */
    public static function isEnabled() {
        return self::$enabled;
    }

    /**
     * Definir TTL padrão
     */
    public static function setDefaultTtl($seconds) {
        self::$defaultTtl = $seconds;
    }

    /**
     * Adicionar tabela à lista de ignoradas
     */
    public static function ignoreTable($table) {
        self::$ignoreTables[] = $table;
    }

    /**
     * Adicionar pattern de query a ignorar
     */
    public static function ignorePattern($pattern) {
        self::$ignorePatterns[] = $pattern;
    }

    // ===================
    // CACHE OPERATIONS
    // ===================

    /**
     * Obter resultado do cache ou executar query
     *
     * @param string $sql Query SQL
     * @param array $bindings Parâmetros
     * @param callable $executor Função que executa a query
     * @param int|null $ttl TTL específico (null = padrão)
     * @param array $tables Tabelas envolvidas (para invalidação)
     * @return mixed
     */
    public static function remember($sql, $bindings, callable $executor, $ttl = null, $tables = []) {
        // Se desabilitado, executar direto
        if (!self::$enabled) {
            return $executor();
        }

        // Verificar se query deve ser ignorada
        if (self::shouldIgnore($sql, $tables)) {
            return $executor();
        }

        $ttl = $ttl ?? self::$defaultTtl;
        $key = self::generateKey($sql, $bindings);

        // Tentar obter do cache
        $cached = Cache::get(self::$prefix . $key);

        if ($cached !== null) {
            self::$stats['hits']++;

            // Log para DebugBar
            if (class_exists('DebugBar')) {
                DebugBar::log("Query Cache HIT: {$key}", 'info');
            }

            return $cached;
        }

        // Cache miss - executar query
        self::$stats['misses']++;
        $result = $executor();

        // Armazenar no cache
        Cache::set(self::$prefix . $key, $result, $ttl);
        self::$stats['writes']++;

        // Registrar relação tabela -> keys para invalidação
        foreach ($tables as $table) {
            self::registerTableKey($table, $key);
        }

        // Log para DebugBar
        if (class_exists('DebugBar')) {
            DebugBar::log("Query Cache MISS: {$key}", 'warning');
        }

        return $result;
    }

    /**
     * Forçar bypass do cache para uma execução
     */
    public static function bypass(callable $executor) {
        $wasEnabled = self::$enabled;
        self::$enabled = false;

        try {
            return $executor();
        } finally {
            self::$enabled = $wasEnabled;
        }
    }

    /**
     * Invalidar cache de uma tabela
     */
    public static function invalidate($table) {
        $keysKey = self::$prefix . 'tables:' . $table;
        $keys = Cache::get($keysKey) ?? [];

        foreach ($keys as $key) {
            Cache::delete(self::$prefix . $key);
        }

        Cache::delete($keysKey);
        self::$stats['invalidations']++;

        // Log
        if (class_exists('DebugBar')) {
            DebugBar::log("Query Cache invalidated for table: {$table}", 'info');
        }
    }

    /**
     * Invalidar múltiplas tabelas
     */
    public static function invalidateTables(array $tables) {
        foreach ($tables as $table) {
            self::invalidate($table);
        }
    }

    /**
     * Limpar todo o cache de queries
     */
    public static function flush() {
        // Usar tag se disponível
        if (method_exists('Cache', 'tag')) {
            Cache::tag('query_cache')->flush();
        } else {
            // Fallback: invalidar tabelas conhecidas
            $tablesKey = self::$prefix . 'known_tables';
            $tables = Cache::get($tablesKey) ?? [];

            foreach ($tables as $table) {
                self::invalidate($table);
            }

            Cache::delete($tablesKey);
        }

        self::$stats['invalidations']++;
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Gerar chave de cache para uma query
     */
    private static function generateKey($sql, $bindings) {
        $data = $sql . ':' . serialize($bindings);
        return md5($data);
    }

    /**
     * Verificar se query deve ser ignorada
     */
    private static function shouldIgnore($sql, $tables) {
        // Verificar patterns
        foreach (self::$ignorePatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        // Verificar tabelas
        foreach ($tables as $table) {
            if (in_array($table, self::$ignoreTables)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar relação tabela -> key para invalidação futura
     */
    private static function registerTableKey($table, $key) {
        $keysKey = self::$prefix . 'tables:' . $table;
        $keys = Cache::get($keysKey) ?? [];

        if (!in_array($key, $keys)) {
            $keys[] = $key;
            // Manter apenas últimas 1000 keys por tabela
            if (count($keys) > 1000) {
                $keys = array_slice($keys, -1000);
            }
            Cache::set($keysKey, $keys, 86400); // 24h
        }

        // Registrar tabela conhecida
        $tablesKey = self::$prefix . 'known_tables';
        $tables = Cache::get($tablesKey) ?? [];
        if (!in_array($table, $tables)) {
            $tables[] = $table;
            Cache::set($tablesKey, $tables, 86400);
        }
    }

    /**
     * Extrair tabelas de uma query SQL
     */
    public static function extractTables($sql) {
        $tables = [];

        // FROM table
        if (preg_match('/FROM\s+[`"\']?(\w+)[`"\']?/i', $sql, $matches)) {
            $tables[] = $matches[1];
        }

        // JOIN table
        if (preg_match_all('/JOIN\s+[`"\']?(\w+)[`"\']?/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }

        // INSERT INTO table
        if (preg_match('/INSERT\s+INTO\s+[`"\']?(\w+)[`"\']?/i', $sql, $matches)) {
            $tables[] = $matches[1];
        }

        // UPDATE table
        if (preg_match('/UPDATE\s+[`"\']?(\w+)[`"\']?/i', $sql, $matches)) {
            $tables[] = $matches[1];
        }

        // DELETE FROM table
        if (preg_match('/DELETE\s+FROM\s+[`"\']?(\w+)[`"\']?/i', $sql, $matches)) {
            $tables[] = $matches[1];
        }

        return array_unique($tables);
    }

    // ===================
    // STATISTICS
    // ===================

    /**
     * Obter estatísticas
     */
    public static function getStats() {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRate = $total > 0 ? round(self::$stats['hits'] / $total * 100, 2) : 0;

        return array_merge(self::$stats, [
            'total' => $total,
            'hit_rate' => $hitRate . '%',
            'enabled' => self::$enabled
        ]);
    }

    /**
     * Resetar estatísticas
     */
    public static function resetStats() {
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'invalidations' => 0
        ];
    }

    /**
     * Log estatísticas (para DebugBar)
     */
    public static function logStats() {
        if (class_exists('DebugBar')) {
            $stats = self::getStats();
            DebugBar::log("Query Cache Stats: {$stats['hits']} hits, {$stats['misses']} misses ({$stats['hit_rate']})", 'info');
        }
    }
}

/**
 * Trait para adicionar cache ao QueryBuilder
 */
trait QueryCacheable {

    /**
     * TTL do cache para esta query
     */
    protected $cacheTtl = null;

    /**
     * Se deve usar cache
     */
    protected $useCache = null;

    /**
     * Habilitar cache para esta query
     *
     * @param int $ttl TTL em segundos
     * @return self
     */
    public function cache($ttl = 300) {
        $this->useCache = true;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Desabilitar cache para esta query
     *
     * @return self
     */
    public function noCache() {
        $this->useCache = false;
        return $this;
    }

    /**
     * Executar query com cache (se habilitado)
     *
     * @param string $sql
     * @param array $bindings
     * @param callable $executor
     * @return mixed
     */
    protected function executeWithCache($sql, $bindings, callable $executor) {
        // Verificar se deve usar cache
        $shouldCache = $this->useCache ?? QueryCache::isEnabled();

        if (!$shouldCache) {
            return $executor();
        }

        // Extrair tabelas para invalidação
        $tables = QueryCache::extractTables($sql);
        if (isset($this->table)) {
            $tables[] = $this->table;
        }
        $tables = array_unique($tables);

        return QueryCache::remember(
            $sql,
            $bindings,
            $executor,
            $this->cacheTtl,
            $tables
        );
    }
}
