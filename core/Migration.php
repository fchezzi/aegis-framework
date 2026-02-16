<?php
/**
 * Migration
 * Sistema de versionamento de schema de banco de dados
 *
 * Funcionalidades:
 * - Criar/alterar/dropar tabelas
 * - Adicionar/modificar/remover colunas
 * - Índices e foreign keys
 * - Rollback de migrações
 * - Tracking de migrações executadas
 *
 * @example
 * // Criar nova migração
 * // database/migrations/2024_01_15_create_posts_table.php
 *
 * class CreatePostsTable extends Migration {
 *     public function up() {
 *         $this->create('posts', function($table) {
 *             $table->uuid('id')->primary();
 *             $table->string('title');
 *             $table->text('content')->nullable();
 *             $table->uuid('user_id');
 *             $table->timestamps();
 *             $table->foreign('user_id')->references('id')->on('users');
 *         });
 *     }
 *
 *     public function down() {
 *         $this->drop('posts');
 *     }
 * }
 */

abstract class Migration {

    /**
     * Conexão com banco
     */
    protected $db;

    /**
     * Nome da migration
     */
    protected $name;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = DB::connect();
    }

    /**
     * Executar migration (up)
     */
    abstract public function up();

    /**
     * Reverter migration (down)
     */
    abstract public function down();

    /**
     * Criar tabela
     *
     * @param string $table
     * @param Closure $callback
     */
    protected function create($table, Closure $callback) {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $blueprint->toCreateSql();
        $this->db->execute($sql);

        // Criar índices
        foreach ($blueprint->getIndexes() as $index) {
            $this->db->execute($index);
        }

        // Criar foreign keys
        foreach ($blueprint->getForeignKeys() as $fk) {
            $this->db->execute($fk);
        }
    }

    /**
     * Modificar tabela existente
     *
     * @param string $table
     * @param Closure $callback
     */
    protected function table($table, Closure $callback) {
        $blueprint = new Blueprint($table);
        $blueprint->setAlter(true);
        $callback($blueprint);

        foreach ($blueprint->toAlterSql() as $sql) {
            $this->db->execute($sql);
        }
    }

    /**
     * Dropar tabela
     *
     * @param string $table
     */
    protected function drop($table) {
        $this->db->execute("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Dropar tabela se existir
     */
    protected function dropIfExists($table) {
        $this->drop($table);
    }

    /**
     * Renomear tabela
     *
     * @param string $from
     * @param string $to
     */
    protected function rename($from, $to) {
        $this->db->execute("RENAME TABLE {$from} TO {$to}");
    }

    /**
     * Verificar se tabela existe
     *
     * @param string $table
     * @return bool
     */
    protected function hasTable($table) {
        $result = $this->db->query("SHOW TABLES LIKE ?", [$table]);
        return !empty($result);
    }

    /**
     * Verificar se coluna existe
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    protected function hasColumn($table, $column) {
        $result = $this->db->query(
            "SHOW COLUMNS FROM {$table} LIKE ?",
            [$column]
        );
        return !empty($result);
    }

    /**
     * Executar SQL raw
     *
     * @param string $sql
     * @param array $bindings
     */
    protected function raw($sql, $bindings = []) {
        $this->db->execute($sql, $bindings);
    }
}

/**
 * Blueprint
 * Define estrutura de tabela para migrations
 */
class Blueprint {

    protected $table;
    protected $columns = [];
    protected $indexes = [];
    protected $foreignKeys = [];
    protected $primaryKey = null;
    protected $isAlter = false;
    protected $alterOperations = [];

    public function __construct($table) {
        $this->table = $table;
    }

    public function setAlter($alter) {
        $this->isAlter = $alter;
    }

    // ===================
    // COLUMN TYPES
    // ===================

    public function uuid($name) {
        return $this->addColumn($name, 'CHAR(36)');
    }

    public function id($name = 'id') {
        return $this->uuid($name)->primary();
    }

    public function increments($name) {
        return $this->addColumn($name, 'INT UNSIGNED AUTO_INCREMENT')->primary();
    }

    public function bigIncrements($name) {
        return $this->addColumn($name, 'BIGINT UNSIGNED AUTO_INCREMENT')->primary();
    }

    public function integer($name) {
        return $this->addColumn($name, 'INT');
    }

    public function bigInteger($name) {
        return $this->addColumn($name, 'BIGINT');
    }

    public function tinyInteger($name) {
        return $this->addColumn($name, 'TINYINT');
    }

    public function smallInteger($name) {
        return $this->addColumn($name, 'SMALLINT');
    }

    public function decimal($name, $precision = 8, $scale = 2) {
        return $this->addColumn($name, "DECIMAL({$precision},{$scale})");
    }

    public function float($name) {
        return $this->addColumn($name, 'FLOAT');
    }

    public function double($name) {
        return $this->addColumn($name, 'DOUBLE');
    }

    public function boolean($name) {
        return $this->addColumn($name, 'TINYINT(1)');
    }

    public function string($name, $length = 255) {
        return $this->addColumn($name, "VARCHAR({$length})");
    }

    public function char($name, $length = 1) {
        return $this->addColumn($name, "CHAR({$length})");
    }

    public function text($name) {
        return $this->addColumn($name, 'TEXT');
    }

    public function mediumText($name) {
        return $this->addColumn($name, 'MEDIUMTEXT');
    }

    public function longText($name) {
        return $this->addColumn($name, 'LONGTEXT');
    }

    public function json($name) {
        return $this->addColumn($name, 'JSON');
    }

    public function date($name) {
        return $this->addColumn($name, 'DATE');
    }

    public function dateTime($name) {
        return $this->addColumn($name, 'DATETIME');
    }

    public function time($name) {
        return $this->addColumn($name, 'TIME');
    }

    public function timestamp($name) {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    public function timestamps() {
        $this->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        return $this;
    }

    public function softDeletes($name = 'deleted_at') {
        return $this->timestamp($name)->nullable();
    }

    public function enum($name, $values) {
        $valueList = "'" . implode("','", $values) . "'";
        return $this->addColumn($name, "ENUM({$valueList})");
    }

    public function binary($name) {
        return $this->addColumn($name, 'BLOB');
    }

    // ===================
    // COLUMN MODIFIERS
    // ===================

    protected function addColumn($name, $type) {
        $column = new ColumnDefinition($name, $type);
        $this->columns[$name] = $column;

        if ($this->isAlter) {
            $this->alterOperations[] = ['type' => 'add', 'column' => $column];
        }

        return $column;
    }

    public function dropColumn($name) {
        if ($this->isAlter) {
            $this->alterOperations[] = ['type' => 'drop', 'name' => $name];
        }
        return $this;
    }

    public function renameColumn($from, $to) {
        if ($this->isAlter) {
            $this->alterOperations[] = ['type' => 'rename', 'from' => $from, 'to' => $to];
        }
        return $this;
    }

    // ===================
    // INDEXES
    // ===================

    public function index($columns, $name = null) {
        $columns = (array) $columns;
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_index';
        $columnList = implode(', ', $columns);

        $this->indexes[] = "CREATE INDEX {$name} ON {$this->table} ({$columnList})";

        return $this;
    }

    public function unique($columns, $name = null) {
        $columns = (array) $columns;
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_unique';
        $columnList = implode(', ', $columns);

        $this->indexes[] = "CREATE UNIQUE INDEX {$name} ON {$this->table} ({$columnList})";

        return $this;
    }

    public function dropIndex($name) {
        if ($this->isAlter) {
            $this->alterOperations[] = ['type' => 'dropIndex', 'name' => $name];
        }
        return $this;
    }

    // ===================
    // FOREIGN KEYS
    // ===================

    public function foreign($column) {
        $fk = new ForeignKeyDefinition($this->table, $column);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    public function dropForeign($name) {
        if ($this->isAlter) {
            $this->alterOperations[] = ['type' => 'dropForeign', 'name' => $name];
        }
        return $this;
    }

    // ===================
    // BUILD SQL
    // ===================

    public function toCreateSql() {
        $columnDefs = [];

        foreach ($this->columns as $column) {
            $columnDefs[] = $column->toSql();
        }

        if ($this->primaryKey) {
            $columnDefs[] = "PRIMARY KEY ({$this->primaryKey})";
        }

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (\n  %s\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $this->table,
            implode(",\n  ", $columnDefs)
        );

        return $sql;
    }

    public function toAlterSql() {
        $sqls = [];

        foreach ($this->alterOperations as $op) {
            switch ($op['type']) {
                case 'add':
                    $sqls[] = "ALTER TABLE {$this->table} ADD COLUMN " . $op['column']->toSql();
                    break;

                case 'drop':
                    $sqls[] = "ALTER TABLE {$this->table} DROP COLUMN {$op['name']}";
                    break;

                case 'rename':
                    $sqls[] = "ALTER TABLE {$this->table} RENAME COLUMN {$op['from']} TO {$op['to']}";
                    break;

                case 'dropIndex':
                    $sqls[] = "DROP INDEX {$op['name']} ON {$this->table}";
                    break;

                case 'dropForeign':
                    $sqls[] = "ALTER TABLE {$this->table} DROP FOREIGN KEY {$op['name']}";
                    break;
            }
        }

        return $sqls;
    }

    public function getIndexes() {
        return $this->indexes;
    }

    public function getForeignKeys() {
        $sqls = [];
        foreach ($this->foreignKeys as $fk) {
            $sqls[] = $fk->toSql();
        }
        return $sqls;
    }

    public function setPrimaryKey($column) {
        $this->primaryKey = $column;
    }
}

/**
 * ColumnDefinition
 * Define uma coluna de tabela
 */
class ColumnDefinition {

    protected $name;
    protected $type;
    protected $nullable = false;
    protected $default = null;
    protected $hasDefault = false;
    protected $unsigned = false;
    protected $autoIncrement = false;
    protected $isPrimary = false;
    protected $comment = null;
    protected $after = null;
    protected $first = false;

    public function __construct($name, $type) {
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable($nullable = true) {
        $this->nullable = $nullable;
        return $this;
    }

    public function default($value) {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    public function unsigned() {
        $this->unsigned = true;
        return $this;
    }

    public function autoIncrement() {
        $this->autoIncrement = true;
        return $this;
    }

    public function primary() {
        $this->isPrimary = true;
        return $this;
    }

    public function comment($comment) {
        $this->comment = $comment;
        return $this;
    }

    public function after($column) {
        $this->after = $column;
        return $this;
    }

    public function first() {
        $this->first = true;
        return $this;
    }

    public function toSql() {
        $sql = "`{$this->name}` {$this->type}";

        if ($this->unsigned) {
            $sql .= ' UNSIGNED';
        }

        if (!$this->nullable) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if ($this->hasDefault) {
            if ($this->default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (in_array($this->default, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])) {
                $sql .= " DEFAULT {$this->default}";
            } else {
                $sql .= " DEFAULT '{$this->default}'";
            }
        }

        if ($this->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($this->isPrimary) {
            $sql .= ' PRIMARY KEY';
        }

        if ($this->comment) {
            $sql .= " COMMENT '{$this->comment}'";
        }

        if ($this->first) {
            $sql .= ' FIRST';
        } elseif ($this->after) {
            $sql .= " AFTER `{$this->after}`";
        }

        return $sql;
    }
}

/**
 * ForeignKeyDefinition
 * Define uma foreign key
 */
class ForeignKeyDefinition {

    protected $table;
    protected $column;
    protected $referencesTable;
    protected $referencesColumn;
    protected $onDelete = 'CASCADE';
    protected $onUpdate = 'CASCADE';
    protected $name;

    public function __construct($table, $column) {
        $this->table = $table;
        $this->column = $column;
        $this->name = "{$table}_{$column}_foreign";
    }

    public function references($column) {
        $this->referencesColumn = $column;
        return $this;
    }

    public function on($table) {
        $this->referencesTable = $table;
        return $this;
    }

    public function onDelete($action) {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate($action) {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function name($name) {
        $this->name = $name;
        return $this;
    }

    public function toSql() {
        return sprintf(
            "ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s ON UPDATE %s",
            $this->table,
            $this->name,
            $this->column,
            $this->referencesTable,
            $this->referencesColumn,
            $this->onDelete,
            $this->onUpdate
        );
    }
}
