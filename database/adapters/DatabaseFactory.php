<?php
/**
 * Database Factory
 * Cria a instância correta do adapter baseado na config
 */

class DatabaseFactory {

    /**
     * Criar adapter de banco de dados
     *
     * @param string $type Tipo (mysql, supabase, none)
     * @param array $config Configurações
     * @return DatabaseInterface
     */
    public static function create($type, $config = []) {

        // Carregar interface e adapters
        require_once __DIR__ . '/DatabaseInterface.php';

        switch (strtolower($type)) {

            case 'mysql':
                require_once __DIR__ . '/MySQLAdapter.php';

                $adapter = new MySQLAdapter(
                    $config['host'] ?? 'localhost',
                    $config['database'] ?? '',
                    $config['username'] ?? '',
                    $config['password'] ?? ''
                );

                $adapter->connect();
                return $adapter;

            case 'supabase':
                require_once __DIR__ . '/SupabaseAdapter.php';

                $adapter = new SupabaseAdapter(
                    $config['url'] ?? '',
                    $config['key'] ?? ''
                );

                $adapter->connect();
                return $adapter;

            case 'none':
                require_once __DIR__ . '/NoneAdapter.php';
                return new NoneAdapter();

            default:
                throw new Exception("Database type '{$type}' not supported");
        }
    }
}
