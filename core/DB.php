<?php
/**
 * @doc Database
 * @title Sistema de Banco de Dados
 * @description
 * Abstração de banco de dados com suporte multi-driver:
 * - MySQL (local ou remoto)
 * - Supabase (PostgreSQL cloud)
 * - None (modo estático sem banco)
 *
 * Padrão Singleton para conexão única.
 * Usa Factory Pattern para criar adapters específicos.
 *
 * @example
 * // Conectar ao banco
 * $db = DB::connect();
 *
 * // SELECT
 * $users = $db->select('users', ['ativo' => 1], 'name ASC');
 *
 * // INSERT
 * $id = $db->insert('users', [
 *     'id' => Security::generateUUID(),
 *     'name' => 'João',
 *     'email' => 'joao@example.com'
 * ]);
 *
 * // UPDATE
 * $db->update('users', ['name' => 'João Silva'], ['id' => $id]);
 *
 * // DELETE
 * $db->delete('users', ['id' => $id]);
 */

/**
 * DB Helper
 * Singleton para conexão com banco de dados
 */

class DB {

    private static $instance = null;

    /**
     * Obter instância do banco
     */
    public static function connect() {
        if (self::$instance === null) {
            $dbType = defined('DB_TYPE') ? DB_TYPE : 'none';

            if ($dbType === 'none') {
                self::$instance = DatabaseFactory::create('none', []);
                return self::$instance;
            }

            $config = self::getConfig($dbType);
            self::$instance = DatabaseFactory::create($dbType, $config);

            // ✅ Sincronizar timezone entre PHP e Database
            self::syncTimezone($dbType);
        }

        return self::$instance;
    }

    /**
     * Sincronizar timezone entre PHP e Database
     * Garante que timestamps sejam consistentes
     */
    private static function syncTimezone($dbType) {
        $phpTz = date_default_timezone_get();

        if ($dbType === 'mysql') {
            try {
                // Converter timezone PHP para offset MySQL (+05:30, -08:00, +00:00, etc)
                $dt = new DateTime('now', new DateTimeZone($phpTz));
                $offset = $dt->format('P'); // Formato: +05:30 ou -08:00
                self::$instance->execute("SET time_zone = ?", [$offset]);
            } catch (Exception $e) {
                // Log erro mas não quebra a aplicação
                error_log("Aviso: Falha ao sincronizar timezone MySQL: " . $e->getMessage());
            }
        }
        // Supabase usa UTC interno, não precisa SET time_zone
    }

    /**
     * Obter configuração do banco
     */
    private static function getConfig($dbType) {
        if ($dbType === 'mysql') {
            return [
                'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'database' => defined('DB_NAME') ? DB_NAME : '',
                'username' => defined('DB_USER') ? DB_USER : '',
                'password' => defined('DB_PASS') ? DB_PASS : ''
            ];
        }

        if ($dbType === 'supabase') {
            return [
                'url' => defined('SUPABASE_URL') ? SUPABASE_URL : '',
                'key' => defined('SUPABASE_KEY') ? SUPABASE_KEY : ''
            ];
        }

        return [];
    }

    /**
     * Resetar conexão (útil para testes)
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Atalhos para métodos comuns
     */
    public static function select($table, $where = []) {
        return self::connect()->select($table, $where);
    }

    public static function insert($table, $data) {
        return self::connect()->insert($table, $data);
    }

    public static function update($table, $data, $where) {
        return self::connect()->update($table, $data, $where);
    }

    public static function delete($table, $where) {
        return self::connect()->delete($table, $where);
    }

    public static function query($sql, $params = []) {
        return self::connect()->query($sql, $params);
    }

    public static function execute($sql, $params = []) {
        return self::connect()->execute($sql, $params);
    }

    /**
     * Iniciar Query Builder para uma tabela
     *
     * @param string $table
     * @return QueryBuilder
     */
    public static function table($table) {
        return new QueryBuilder($table, self::connect());
    }
}
