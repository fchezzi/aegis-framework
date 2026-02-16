<?php
/**
 * RefreshDatabase Trait
 * Reseta o banco de dados entre testes usando migrations
 *
 * @example
 * class UserTest extends TestCase {
 *     use RefreshDatabase;
 *
 *     public function testSomething() {
 *         // Banco está limpo e com schema atualizado
 *     }
 * }
 */

trait RefreshDatabase {

    /**
     * Se já migrou nesta execução
     */
    protected static $migrated = false;

    /**
     * Setup do banco antes dos testes
     */
    protected function setUpDatabase() {
        if (!static::$migrated) {
            $this->migrateDatabase();
            static::$migrated = true;
        }

        $this->beginTransaction();
    }

    /**
     * Teardown do banco após os testes
     */
    protected function tearDownDatabase() {
        $this->rollbackTransaction();
    }

    /**
     * Executar migrations
     */
    protected function migrateDatabase() {
        // Fresh migration (drop + migrate)
        if (class_exists('Migrator')) {
            Migrator::fresh();
        }
    }

    /**
     * Seed do banco com dados de teste
     */
    protected function seed($seeder = null) {
        if ($seeder && class_exists($seeder)) {
            $seederInstance = new $seeder();
            $seederInstance->run();
        }
    }
}
