<?php
/**
 * MySQL Adapter
 * Implementação MySQL usando PDO
 * Baseado no Oleg Framework
 */

class MySQLAdapter implements DatabaseInterface {

    private $pdo;
    private $host;
    private $database;
    private $username;
    private $password;

    public function __construct($host, $database, $username, $password) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect() {
        try {
            // Primeiro: conectar SEM especificar banco (para poder criar)
            $dsn = "mysql:host={$this->host};charset=utf8mb4";

            // Compatibilidade PHP 8.5+ (Pdo\Mysql::ATTR_INIT_COMMAND) e versões anteriores (PDO::MYSQL_ATTR_INIT_COMMAND)
            $initCommandKey = defined('Pdo\Mysql::ATTR_INIT_COMMAND') ? constant('Pdo\Mysql::ATTR_INIT_COMMAND') : PDO::MYSQL_ATTR_INIT_COMMAND;

            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,

                // ❌ Connection Pooling DESABILITADO - Causava problema de charset UTF-8
                // Com PERSISTENT=true, o método connect() não é chamado em toda requisição
                // Isso fazia com que as configurações de charset (linhas 49-54) não fossem aplicadas
                // Resultado: charset errado + duplicação de registros
                // PDO::ATTR_PERSISTENT => true,

                $initCommandKey => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            // Criar banco se não existir
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Selecionar o banco
            $this->pdo->exec("USE `{$this->database}`");

            // FORÇAR UTF8MB4 em todas as variáveis da sessão
            $this->pdo->exec("SET character_set_client = utf8mb4");
            $this->pdo->exec("SET character_set_connection = utf8mb4");
            $this->pdo->exec("SET character_set_results = utf8mb4");
            $this->pdo->exec("SET character_set_server = utf8mb4");
            $this->pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
            $this->pdo->exec("SET collation_server = utf8mb4_unicode_ci");

            // Otimizações de performance
            $this->pdo->exec("SET SESSION sql_mode='TRADITIONAL'");

            return true;
        } catch (PDOException $e) {
            throw new Exception("MySQL Connection Error: " . $e->getMessage());
        }
    }

    public function disconnect() {
        $this->pdo = null;
    }

    public function select($table, $where = [], $options = []) {
        // Sanitizar nome da tabela
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // Compatibilidade: Se $options for string, converter para array
        if (is_string($options)) {
            $options = ['order' => $options];
        }

        // Query base
        $sql = "SELECT * FROM {$table}";
        $params = [];

        // WHERE
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $field => $value) {
                $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);

                // Suporte para WHERE IN (array de valores)
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $conditions[] = "`{$field}` IN ({$placeholders})";
                    foreach ($value as $v) {
                        $params[] = $v;
                    }
                } else {
                    $conditions[] = "`{$field}` = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // ORDER BY
        if (isset($options['order'])) {
            $order = preg_replace('/[^a-zA-Z0-9_,\s`]/', '', $options['order']);
            $sql .= " ORDER BY {$order}";
        }

        // LIMIT
        if (isset($options['limit'])) {
            $limit = (int) $options['limit'];
            $sql .= " LIMIT {$limit}";
        }

        // Executar
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function insert($table, $data) {
        // Sanitizar tabela
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // ✅ Converter booleans para int (MySQL compatibility)
        $data = $this->normalizeBooleans($data);

        // Campos e valores
        $fields = array_keys($data);
        $fields = array_map(function($f) {
            $f = preg_replace('/[^a-zA-Z0-9_]/', '', $f);
            return "`{$f}`";
        }, $fields);

        $placeholders = array_fill(0, count($fields), '?');

        // Query
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        // Executar
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute(array_values($data));

        // Retornar ID para AUTO_INCREMENT ou TRUE para UUID/VARCHAR
        if ($success) {
            $lastId = $this->pdo->lastInsertId();
            return $lastId !== '0' ? $lastId : true;
        }

        return false;
    }

    public function update($table, $data, $where) {
        // Sanitizar tabela
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // ✅ Converter booleans para int (MySQL compatibility)
        $data = $this->normalizeBooleans($data);

        // SET
        $sets = [];
        $params = [];
        foreach ($data as $field => $value) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
            $sets[] = "`{$field}` = ?";
            $params[] = $value;
        }

        // WHERE
        $conditions = [];
        foreach ($where as $field => $value) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
            $conditions[] = "`{$field}` = ?";
            $params[] = $value;
        }

        // Query
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) .
               " WHERE " . implode(' AND ', $conditions);

        // Executar
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($table, $where) {
        // Sanitizar tabela
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        // WHERE
        $conditions = [];
        $params = [];
        foreach ($where as $field => $value) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
            $conditions[] = "`{$field}` = ?";
            $params[] = $value;
        }

        // Query
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $conditions);

        // Executar
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function getLastId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Normalizar valores booleanos para inteiros (MySQL compatibility)
     * MySQL não tem tipo BOOLEAN nativo - usa TINYINT(1) onde 0=false, 1=true
     *
     * @param array $data Array de dados
     * @return array Array com booleans convertidos para int
     */
    private function normalizeBooleans($data) {
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? 1 : 0;
            }
        }
        return $data;
    }

    public function tableExists($table) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    }

    public function getColumns($table) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $sql = "SHOW COLUMNS FROM {$table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
