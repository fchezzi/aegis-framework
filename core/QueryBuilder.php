<?php
/**
 * QueryBuilder
 * Fluent interface para construção de queries SQL
 *
 * Funcionalidades:
 * - SELECT, INSERT, UPDATE, DELETE
 * - WHERE com operadores
 * - JOIN (inner, left, right)
 * - ORDER BY, GROUP BY, HAVING
 * - LIMIT, OFFSET
 * - Agregações (count, sum, avg, min, max)
 * - Subqueries
 * - Raw expressions
 *
 * @example
 * // SELECT básico
 * $users = DB::table('users')
 *     ->where('ativo', 1)
 *     ->orderBy('name')
 *     ->get();
 *
 * // SELECT com condições
 * $user = DB::table('users')
 *     ->where('email', 'user@example.com')
 *     ->first();
 *
 * // SELECT com joins
 * $posts = DB::table('posts')
 *     ->select('posts.*', 'users.name as author')
 *     ->join('users', 'posts.user_id', '=', 'users.id')
 *     ->where('posts.published', 1)
 *     ->get();
 *
 * // INSERT
 * $id = DB::table('users')->insert([
 *     'name' => 'John',
 *     'email' => 'john@example.com'
 * ]);
 *
 * // UPDATE
 * DB::table('users')
 *     ->where('id', 123)
 *     ->update(['name' => 'Jane']);
 *
 * // DELETE
 * DB::table('users')
 *     ->where('ativo', 0)
 *     ->delete();
 */

class QueryBuilder {

    /**
     * Conexão com banco
     */
    protected $db;

    /**
     * Tabela principal
     */
    protected $table;

    /**
     * Colunas para SELECT
     */
    protected $columns = ['*'];

    /**
     * JOINs
     */
    protected $joins = [];

    /**
     * Condições WHERE
     */
    protected $wheres = [];

    /**
     * Bindings para prepared statements
     */
    protected $bindings = [];

    /**
     * ORDER BY
     */
    protected $orders = [];

    /**
     * GROUP BY
     */
    protected $groups = [];

    /**
     * HAVING
     */
    protected $havings = [];

    /**
     * LIMIT
     */
    protected $limit = null;

    /**
     * OFFSET
     */
    protected $offset = null;

    /**
     * DISTINCT
     */
    protected $distinct = false;

    /**
     * Construtor
     *
     * @param string $table
     * @param object|null $db Conexão (usa DB::connect() se não fornecido)
     */
    public function __construct($table, $db = null) {
        $this->table = $table;
        $this->db = $db ?? DB::connect();
    }

    /**
     * Factory estático
     *
     * @param string $table
     * @return self
     */
    public static function table($table) {
        return new self($table);
    }

    // ===================
    // SELECT
    // ===================

    /**
     * Definir colunas para SELECT
     *
     * @param mixed ...$columns
     * @return self
     */
    public function select(...$columns) {
        $this->columns = [];

        foreach ($columns as $col) {
            if (is_array($col)) {
                $this->columns = array_merge($this->columns, $col);
            } else {
                $this->columns[] = $col;
            }
        }

        return $this;
    }

    /**
     * Adicionar colunas ao SELECT
     *
     * @param mixed ...$columns
     * @return self
     */
    public function addSelect(...$columns) {
        foreach ($columns as $col) {
            $this->columns[] = $col;
        }
        return $this;
    }

    /**
     * SELECT DISTINCT
     *
     * @return self
     */
    public function distinct() {
        $this->distinct = true;
        return $this;
    }

    // ===================
    // WHERE
    // ===================

    /**
     * Adicionar condição WHERE
     *
     * @param string|Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean AND ou OR
     * @return self
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND') {
        // Closure para grupo de condições
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // where('column', 'value') => where('column', '=', 'value')
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => $boolean
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * WHERE com OR
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null) {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereIn($column, $values, $boolean = 'AND', $not = false) {
        $type = $not ? 'not in' : 'in';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * WHERE NOT IN
     */
    public function whereNotIn($column, $values) {
        return $this->whereIn($column, $values, 'AND', true);
    }

    /**
     * WHERE NULL
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereNull($column, $boolean = 'AND', $not = false) {
        $type = $not ? 'not null' : 'null';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL
     */
    public function whereNotNull($column) {
        return $this->whereNull($column, 'AND', true);
    }

    /**
     * WHERE BETWEEN
     *
     * @param string $column
     * @param mixed $min
     * @param mixed $max
     * @param string $boolean
     * @param bool $not
     * @return self
     */
    public function whereBetween($column, $min, $max, $boolean = 'AND', $not = false) {
        $type = $not ? 'not between' : 'between';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'min' => $min,
            'max' => $max,
            'boolean' => $boolean
        ];

        $this->bindings[] = $min;
        $this->bindings[] = $max;

        return $this;
    }

    /**
     * WHERE LIKE
     *
     * @param string $column
     * @param string $value
     * @param string $boolean
     * @return self
     */
    public function whereLike($column, $value, $boolean = 'AND') {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * WHERE nested (grupo de condições)
     */
    protected function whereNested(Closure $callback, $boolean = 'AND') {
        $query = new self($this->table, $this->db);
        $callback($query);

        if (!empty($query->wheres)) {
            $this->wheres[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean
            ];

            $this->bindings = array_merge($this->bindings, $query->bindings);
        }

        return $this;
    }

    /**
     * WHERE raw SQL
     *
     * @param string $sql
     * @param array $bindings
     * @param string $boolean
     * @return self
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'AND') {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean
        ];

        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    // ===================
    // JOIN
    // ===================

    /**
     * INNER JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return self
     */
    public function join($table, $first, $operator = null, $second = null) {
        return $this->addJoin('inner', $table, $first, $operator, $second);
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin($table, $first, $operator = null, $second = null) {
        return $this->addJoin('left', $table, $first, $operator, $second);
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin($table, $first, $operator = null, $second = null) {
        return $this->addJoin('right', $table, $first, $operator, $second);
    }

    /**
     * Adicionar JOIN
     */
    protected function addJoin($type, $table, $first, $operator, $second) {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator ?? '=',
            'second' => $second
        ];

        return $this;
    }

    // ===================
    // ORDER BY
    // ===================

    /**
     * ORDER BY
     *
     * @param string $column
     * @param string $direction ASC ou DESC
     * @return self
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    /**
     * ORDER BY DESC
     */
    public function orderByDesc($column) {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * ORDER BY mais recente
     */
    public function latest($column = 'created_at') {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * ORDER BY mais antigo
     */
    public function oldest($column = 'created_at') {
        return $this->orderBy($column, 'ASC');
    }

    // ===================
    // GROUP BY / HAVING
    // ===================

    /**
     * GROUP BY
     *
     * @param mixed ...$columns
     * @return self
     */
    public function groupBy(...$columns) {
        foreach ($columns as $col) {
            $this->groups[] = $col;
        }
        return $this;
    }

    /**
     * HAVING
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function having($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        $this->bindings[] = $value;

        return $this;
    }

    // ===================
    // LIMIT / OFFSET
    // ===================

    /**
     * LIMIT
     *
     * @param int $limit
     * @return self
     */
    public function limit($limit) {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Alias para limit
     */
    public function take($limit) {
        return $this->limit($limit);
    }

    /**
     * OFFSET
     *
     * @param int $offset
     * @return self
     */
    public function offset($offset) {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Alias para offset
     */
    public function skip($offset) {
        return $this->offset($offset);
    }

    /**
     * Paginação simples
     *
     * @param int $page
     * @param int $perPage
     * @return self
     */
    public function forPage($page, $perPage = 15) {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    // ===================
    // EXECUTE SELECT
    // ===================

    /**
     * Executar e obter todos os resultados
     *
     * @return array
     */
    public function get() {
        $sql = $this->buildSelect();
        return $this->db->query($sql, $this->bindings);
    }

    /**
     * Obter primeiro resultado
     *
     * @return array|null
     */
    public function first() {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Obter valor de uma coluna
     *
     * @param string $column
     * @return mixed
     */
    public function value($column) {
        $row = $this->select($column)->first();
        return $row[$column] ?? null;
    }

    /**
     * Obter array de valores de uma coluna
     *
     * @param string $column
     * @param string|null $key Coluna para usar como chave
     * @return array
     */
    public function pluck($column, $key = null) {
        $results = $this->get();
        $plucked = [];

        foreach ($results as $row) {
            if ($key !== null) {
                $plucked[$row[$key]] = $row[$column];
            } else {
                $plucked[] = $row[$column];
            }
        }

        return $plucked;
    }

    /**
     * Verificar se existe registro
     *
     * @return bool
     */
    public function exists() {
        return $this->count() > 0;
    }

    /**
     * Verificar se NÃO existe registro
     *
     * @return bool
     */
    public function doesntExist() {
        return !$this->exists();
    }

    // ===================
    // AGREGAÇÕES
    // ===================

    /**
     * COUNT
     *
     * @param string $column
     * @return int
     */
    public function count($column = '*') {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * SUM
     *
     * @param string $column
     * @return float
     */
    public function sum($column) {
        return (float) $this->aggregate('SUM', $column);
    }

    /**
     * AVG
     *
     * @param string $column
     * @return float
     */
    public function avg($column) {
        return (float) $this->aggregate('AVG', $column);
    }

    /**
     * MIN
     *
     * @param string $column
     * @return mixed
     */
    public function min($column) {
        return $this->aggregate('MIN', $column);
    }

    /**
     * MAX
     *
     * @param string $column
     * @return mixed
     */
    public function max($column) {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Executar agregação
     */
    protected function aggregate($function, $column) {
        $this->columns = ["{$function}({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    // ===================
    // INSERT
    // ===================

    /**
     * INSERT
     *
     * @param array $data
     * @return mixed ID inserido ou bool
     */
    public function insert($data) {
        // Múltiplos inserts
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $row) {
                $this->insert($row);
            }
            return true;
        }

        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->db->insert($this->table, $data);
    }

    /**
     * INSERT ou UPDATE se existir
     *
     * @param array $attributes Atributos para busca
     * @param array $values Valores para atualizar/inserir
     * @return bool
     */
    public function updateOrInsert($attributes, $values) {
        $existing = $this->where($attributes)->first();

        if ($existing) {
            return $this->where($attributes)->update($values);
        }

        return $this->insert(array_merge($attributes, $values));
    }

    // ===================
    // UPDATE
    // ===================

    /**
     * UPDATE
     *
     * @param array $data
     * @return int Linhas afetadas
     */
    public function update($data) {
        $sets = [];
        $updateBindings = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $updateBindings[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s",
            $this->table,
            implode(', ', $sets)
        );

        // Adicionar WHERE
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        $allBindings = array_merge($updateBindings, $this->bindings);

        return $this->db->execute($sql, $allBindings);
    }

    /**
     * INCREMENT coluna
     *
     * @param string $column
     * @param int $amount
     * @return int
     */
    public function increment($column, $amount = 1) {
        $sql = sprintf(
            "UPDATE %s SET %s = %s + ?",
            $this->table,
            $column,
            $column
        );

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        $allBindings = array_merge([$amount], $this->bindings);

        return $this->db->execute($sql, $allBindings);
    }

    /**
     * DECREMENT coluna
     */
    public function decrement($column, $amount = 1) {
        return $this->increment($column, -$amount);
    }

    // ===================
    // DELETE
    // ===================

    /**
     * DELETE
     *
     * @return int Linhas afetadas
     */
    public function delete() {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        return $this->db->execute($sql, $this->bindings);
    }

    /**
     * TRUNCATE
     *
     * @return bool
     */
    public function truncate() {
        return $this->db->execute("TRUNCATE TABLE {$this->table}");
    }

    // ===================
    // BUILD SQL
    // ===================

    /**
     * Construir SELECT SQL
     */
    protected function buildSelect() {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= " FROM {$this->table}";

        // JOINs
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                " %s JOIN %s ON %s %s %s",
                strtoupper($join['type']),
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }

        // WHERE
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . $this->buildHavings();
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Construir cláusulas WHERE
     */
    protected function buildWheres() {
        $parts = [];

        foreach ($this->wheres as $i => $where) {
            $clause = '';

            switch ($where['type']) {
                case 'basic':
                    $clause = "{$where['column']} {$where['operator']} ?";
                    break;

                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clause = "{$where['column']} IN ({$placeholders})";
                    break;

                case 'not in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clause = "{$where['column']} NOT IN ({$placeholders})";
                    break;

                case 'null':
                    $clause = "{$where['column']} IS NULL";
                    break;

                case 'not null':
                    $clause = "{$where['column']} IS NOT NULL";
                    break;

                case 'between':
                    $clause = "{$where['column']} BETWEEN ? AND ?";
                    break;

                case 'not between':
                    $clause = "{$where['column']} NOT BETWEEN ? AND ?";
                    break;

                case 'nested':
                    $clause = '(' . $where['query']->buildWheres() . ')';
                    break;

                case 'raw':
                    $clause = $where['sql'];
                    break;
            }

            if ($i === 0) {
                $parts[] = $clause;
            } else {
                $parts[] = $where['boolean'] . ' ' . $clause;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Construir cláusulas HAVING
     */
    protected function buildHavings() {
        $parts = [];

        foreach ($this->havings as $having) {
            $parts[] = "{$having['column']} {$having['operator']} ?";
        }

        return implode(' AND ', $parts);
    }

    /**
     * Obter SQL gerado (debug)
     *
     * @return string
     */
    public function toSql() {
        return $this->buildSelect();
    }

    /**
     * Obter bindings (debug)
     *
     * @return array
     */
    public function getBindings() {
        return $this->bindings;
    }

    /**
     * Debug: dump SQL e bindings
     */
    public function dd() {
        echo "SQL: " . $this->toSql() . "\n";
        echo "Bindings: " . print_r($this->bindings, true) . "\n";
        exit;
    }
}
