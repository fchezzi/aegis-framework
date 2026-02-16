<?php
/**
 * Migrator
 * Gerencia execução de migrations
 *
 * Funcionalidades:
 * - Executar migrations pendentes
 * - Rollback de migrations
 * - Status de migrations
 * - Criar novas migrations
 *
 * @example
 * // Executar todas as migrations pendentes
 * Migrator::migrate();
 *
 * // Rollback última migration
 * Migrator::rollback();
 *
 * // Rollback todas
 * Migrator::reset();
 *
 * // Status
 * Migrator::status();
 *
 * // Criar nova migration
 * Migrator::create('create_posts_table');
 */

class Migrator {

    /**
     * Caminho das migrations
     */
    protected static $path = null;

    /**
     * Tabela de controle
     */
    protected static $table = 'migrations';

    /**
     * Conexão com banco
     */
    protected static $db = null;

    /**
     * Output callback
     */
    protected static $output = null;

    /**
     * Configurar migrator
     */
    public static function configure($config) {
        if (isset($config['path'])) {
            self::$path = $config['path'];
        }
        if (isset($config['table'])) {
            self::$table = $config['table'];
        }
    }

    /**
     * Definir callback de output
     */
    public static function setOutput($callback) {
        self::$output = $callback;
    }

    /**
     * Output message
     */
    protected static function output($message, $type = 'info') {
        if (self::$output) {
            call_user_func(self::$output, $message, $type);
        } else {
            echo "[{$type}] {$message}\n";
        }
    }

    /**
     * Obter conexão com banco
     */
    protected static function db() {
        if (self::$db === null) {
            self::$db = DB::connect();
        }
        return self::$db;
    }

    /**
     * Obter caminho das migrations
     */
    protected static function getPath() {
        if (self::$path === null) {
            self::$path = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__) . '/') . 'database/migrations/';
        }

        if (!is_dir(self::$path)) {
            @mkdir(self::$path, 0755, true);
        }

        return self::$path;
    }

    /**
     * Garantir que tabela de migrations existe
     */
    protected static function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS " . self::$table . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT UNSIGNED NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        self::db()->execute($sql);
    }

    /**
     * Obter migrations já executadas
     */
    protected static function getRan() {
        self::ensureTable();

        $results = self::db()->query(
            "SELECT migration FROM " . self::$table . " ORDER BY batch, migration"
        );

        return array_column($results, 'migration');
    }

    /**
     * Obter último batch
     */
    protected static function getLastBatch() {
        $result = self::db()->query(
            "SELECT MAX(batch) as batch FROM " . self::$table
        );

        return (int) ($result[0]['batch'] ?? 0);
    }

    /**
     * Obter arquivos de migration
     */
    protected static function getMigrationFiles() {
        $path = self::getPath();
        $files = glob($path . '*.php');

        $migrations = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $migrations[$name] = $file;
        }

        ksort($migrations);

        return $migrations;
    }

    /**
     * Obter migrations pendentes
     */
    public static function getPending() {
        $files = self::getMigrationFiles();
        $ran = self::getRan();

        return array_diff_key($files, array_flip($ran));
    }

    /**
     * Executar migrations pendentes
     *
     * @return int Número de migrations executadas
     */
    public static function migrate() {
        $pending = self::getPending();

        if (empty($pending)) {
            self::output('Nenhuma migration pendente.', 'info');
            return 0;
        }

        $batch = self::getLastBatch() + 1;
        $count = 0;

        foreach ($pending as $name => $file) {
            self::output("Migrando: {$name}", 'info');

            try {
                $migration = self::resolve($file);
                $migration->up();

                self::db()->insert(self::$table, [
                    'migration' => $name,
                    'batch' => $batch
                ]);

                self::output("Migrado: {$name}", 'success');
                $count++;

            } catch (Exception $e) {
                self::output("Erro em {$name}: " . $e->getMessage(), 'error');
                throw $e;
            }
        }

        self::output("Total: {$count} migrations executadas.", 'info');

        return $count;
    }

    /**
     * Rollback última batch de migrations
     *
     * @param int $steps Número de batches para reverter
     * @return int Número de migrations revertidas
     */
    public static function rollback($steps = 1) {
        $batch = self::getLastBatch();

        if ($batch === 0) {
            self::output('Nenhuma migration para reverter.', 'info');
            return 0;
        }

        $count = 0;

        for ($i = 0; $i < $steps && $batch > 0; $i++, $batch--) {
            $migrations = self::db()->query(
                "SELECT migration FROM " . self::$table . " WHERE batch = ? ORDER BY migration DESC",
                [$batch]
            );

            foreach ($migrations as $row) {
                $name = $row['migration'];
                $files = self::getMigrationFiles();

                if (!isset($files[$name])) {
                    self::output("Migration não encontrada: {$name}", 'warning');
                    continue;
                }

                self::output("Revertendo: {$name}", 'info');

                try {
                    $migration = self::resolve($files[$name]);
                    $migration->down();

                    self::db()->delete(self::$table, ['migration' => $name]);

                    self::output("Revertido: {$name}", 'success');
                    $count++;

                } catch (Exception $e) {
                    self::output("Erro em {$name}: " . $e->getMessage(), 'error');
                    throw $e;
                }
            }
        }

        self::output("Total: {$count} migrations revertidas.", 'info');

        return $count;
    }

    /**
     * Reverter todas as migrations
     */
    public static function reset() {
        $batch = self::getLastBatch();
        return self::rollback($batch);
    }

    /**
     * Reset e migrate novamente
     */
    public static function refresh() {
        self::reset();
        return self::migrate();
    }

    /**
     * Obter status das migrations
     *
     * @return array
     */
    public static function status() {
        $files = self::getMigrationFiles();
        $ran = self::getRan();

        $status = [];

        foreach ($files as $name => $file) {
            $status[] = [
                'migration' => $name,
                'status' => in_array($name, $ran) ? 'Ran' : 'Pending'
            ];
        }

        return $status;
    }

    /**
     * Criar nova migration
     *
     * @param string $name Nome da migration
     * @param string|null $table Tabela (para template)
     * @param bool $create Se é criação de tabela
     * @return string Caminho do arquivo criado
     */
    public static function create($name, $table = null, $create = true) {
        $path = self::getPath();

        // Gerar nome do arquivo
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $path . $filename;

        // Gerar nome da classe
        $className = self::studly($name);

        // Detectar tabela do nome se não fornecido
        if ($table === null) {
            if (preg_match('/create_(\w+)_table/', $name, $matches)) {
                $table = $matches[1];
                $create = true;
            } elseif (preg_match('/add_\w+_to_(\w+)/', $name, $matches)) {
                $table = $matches[1];
                $create = false;
            }
        }

        // Template
        if ($create && $table) {
            $template = self::getCreateTableTemplate($className, $table);
        } elseif ($table) {
            $template = self::getAlterTableTemplate($className, $table);
        } else {
            $template = self::getBlankTemplate($className);
        }

        file_put_contents($filepath, $template);

        self::output("Migration criada: {$filename}", 'success');

        return $filepath;
    }

    /**
     * Resolver migration (carregar e instanciar)
     */
    protected static function resolve($file) {
        require_once $file;

        // Extrair nome da classe do arquivo
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $content, $matches)) {
            $className = $matches[1];
            return new $className();
        }

        throw new Exception("Classe de migration não encontrada em: {$file}");
    }

    /**
     * Converter para StudlyCase
     */
    protected static function studly($value) {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * Template para criar tabela
     */
    protected static function getCreateTableTemplate($className, $table) {
        return <<<PHP
<?php

class {$className} extends Migration {

    public function up() {
        \$this->create('{$table}', function(\$table) {
            \$table->uuid('id')->primary();
            // Adicione suas colunas aqui
            \$table->timestamps();
        });
    }

    public function down() {
        \$this->drop('{$table}');
    }
}
PHP;
    }

    /**
     * Template para alterar tabela
     */
    protected static function getAlterTableTemplate($className, $table) {
        return <<<PHP
<?php

class {$className} extends Migration {

    public function up() {
        \$this->table('{$table}', function(\$table) {
            // Adicione suas alterações aqui
            // \$table->string('new_column');
        });
    }

    public function down() {
        \$this->table('{$table}', function(\$table) {
            // Reverta suas alterações aqui
            // \$table->dropColumn('new_column');
        });
    }
}
PHP;
    }

    /**
     * Template em branco
     */
    protected static function getBlankTemplate($className) {
        return <<<PHP
<?php

class {$className} extends Migration {

    public function up() {
        // Execute sua migration aqui
    }

    public function down() {
        // Reverta sua migration aqui
    }
}
PHP;
    }
}
