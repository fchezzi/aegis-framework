<?php
/**
 * MockDatabase
 * Banco de dados em memória para testes
 *
 * Permite testar código de banco sem conexão real
 *
 * @example
 * // Setup no teste
 * $mockDb = new MockDatabase();
 * $mockDb->addTable('users', [
 *     ['id' => '1', 'name' => 'John', 'email' => 'john@test.com']
 * ]);
 *
 * // Substituir DB
 * DB::setInstance($mockDb);
 *
 * // Agora queries usam dados mock
 * $user = DB::selectOne('users', ['id' => '1']);
 */

class MockDatabase {

    /**
     * Dados das tabelas (em memória)
     */
    protected $tables = [];

    /**
     * Queries executadas (para assertions)
     */
    protected $queryLog = [];

    /**
     * Auto-increment counters
     */
    protected $autoIncrements = [];

    /**
     * Adicionar tabela com dados
     */
    public function addTable($name, array $rows = []) {
        $this->tables[$name] = $rows;
        $this->autoIncrements[$name] = count($rows) + 1;
        return $this;
    }

    /**
     * Limpar todas as tabelas
     */
    public function reset() {
        $this->tables = [];
        $this->queryLog = [];
        $this->autoIncrements = [];
    }

    /**
     * Limpar tabela específica
     */
    public function truncate($table) {
        $this->tables[$table] = [];
        $this->autoIncrements[$table] = 1;
    }

    // ===================
    // CRUD OPERATIONS
    // ===================

    /**
     * SELECT
     */
    public function select($table, $where = [], $orderBy = null, $limit = null) {
        $this->log('SELECT', $table, $where);

        if (!isset($this->tables[$table])) {
            return [];
        }

        $results = $this->tables[$table];

        // Filtrar por where
        if (!empty($where)) {
            $results = array_filter($results, function($row) use ($where) {
                foreach ($where as $key => $value) {
                    if (!isset($row[$key]) || $row[$key] != $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Reindexar
        $results = array_values($results);

        // Ordenar
        if ($orderBy) {
            $parts = explode(' ', $orderBy);
            $column = $parts[0];
            $direction = strtoupper($parts[1] ?? 'ASC');

            usort($results, function($a, $b) use ($column, $direction) {
                $cmp = $a[$column] <=> $b[$column];
                return $direction === 'DESC' ? -$cmp : $cmp;
            });
        }

        // Limitar
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * SELECT ONE
     */
    public function selectOne($table, $where = []) {
        $results = $this->select($table, $where, null, 1);
        return $results[0] ?? null;
    }

    /**
     * INSERT
     */
    public function insert($table, $data) {
        $this->log('INSERT', $table, $data);

        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
        }

        // Auto-generate ID if not provided
        if (!isset($data['id'])) {
            $data['id'] = $this->autoIncrements[$table] ?? 1;
            $this->autoIncrements[$table] = ($this->autoIncrements[$table] ?? 1) + 1;
        }

        // Timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->tables[$table][] = $data;

        return $data['id'];
    }

    /**
     * UPDATE
     */
    public function update($table, $data, $where) {
        $this->log('UPDATE', $table, ['data' => $data, 'where' => $where]);

        if (!isset($this->tables[$table])) {
            return 0;
        }

        $count = 0;
        foreach ($this->tables[$table] as &$row) {
            $match = true;
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] != $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $row = array_merge($row, $data);
                $row['updated_at'] = date('Y-m-d H:i:s');
                $count++;
            }
        }

        return $count;
    }

    /**
     * DELETE
     */
    public function delete($table, $where) {
        $this->log('DELETE', $table, $where);

        if (!isset($this->tables[$table])) {
            return 0;
        }

        $count = 0;
        $this->tables[$table] = array_filter($this->tables[$table], function($row) use ($where, &$count) {
            foreach ($where as $key => $value) {
                if (!isset($row[$key]) || $row[$key] != $value) {
                    return true; // Keep
                }
            }
            $count++;
            return false; // Remove
        });

        $this->tables[$table] = array_values($this->tables[$table]);

        return $count;
    }

    /**
     * Query raw (simulada)
     */
    public function query($sql, $params = []) {
        $this->log('RAW', null, ['sql' => $sql, 'params' => $params]);

        // Parser básico de queries simples
        if (preg_match('/SELECT.*FROM\s+(\w+)/i', $sql, $matches)) {
            $table = $matches[1];
            return $this->select($table);
        }

        return [];
    }

    /**
     * Execute raw (simulada)
     */
    public function execute($sql, $params = []) {
        $this->log('EXECUTE', null, ['sql' => $sql, 'params' => $params]);
        return true;
    }

    // ===================
    // TRANSACTIONS
    // ===================

    protected $transactionData = null;

    public function beginTransaction() {
        $this->transactionData = $this->tables;
        return true;
    }

    public function commit() {
        $this->transactionData = null;
        return true;
    }

    public function rollback() {
        if ($this->transactionData !== null) {
            $this->tables = $this->transactionData;
            $this->transactionData = null;
        }
        return true;
    }

    // ===================
    // QUERY LOG
    // ===================

    /**
     * Log query
     */
    protected function log($type, $table, $data) {
        $this->queryLog[] = [
            'type' => $type,
            'table' => $table,
            'data' => $data,
            'time' => microtime(true)
        ];
    }

    /**
     * Obter log de queries
     */
    public function getQueryLog() {
        return $this->queryLog;
    }

    /**
     * Verificar se query foi executada
     */
    public function wasQueried($type, $table = null) {
        foreach ($this->queryLog as $log) {
            if ($log['type'] === $type) {
                if ($table === null || $log['table'] === $table) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Contar queries de um tipo
     */
    public function countQueries($type = null) {
        if ($type === null) {
            return count($this->queryLog);
        }

        return count(array_filter($this->queryLog, fn($log) => $log['type'] === $type));
    }

    /**
     * Limpar log
     */
    public function clearQueryLog() {
        $this->queryLog = [];
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Obter todos os dados de uma tabela
     */
    public function getTableData($table) {
        return $this->tables[$table] ?? [];
    }

    /**
     * Contar registros
     */
    public function count($table, $where = []) {
        return count($this->select($table, $where));
    }

    /**
     * Verificar se existe
     */
    public function exists($table, $where) {
        return $this->count($table, $where) > 0;
    }
}
