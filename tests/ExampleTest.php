<?php
/**
 * ExampleTest
 * Exemplo de testes unitários do AEGIS
 *
 * Execute com: php aegis test ExampleTest
 */

class ExampleTest extends TestCase {

    /**
     * Desabilitar transações para este teste de exemplo
     */
    protected $useTransaction = false;

    // ===================
    // VALIDATOR TESTS
    // ===================

    public function testValidatorRequired() {
        $data = ['name' => 'John'];
        $validator = Validator::make($data, ['name' => 'required']);

        $this->assertTrue($validator->passes());
    }

    public function testValidatorRequiredFails() {
        $data = ['name' => ''];
        $validator = Validator::make($data, ['name' => 'required']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors());
    }

    public function testValidatorEmail() {
        $valid = Validator::make(['email' => 'test@example.com'], ['email' => 'email']);
        $invalid = Validator::make(['email' => 'invalid-email'], ['email' => 'email']);

        $this->assertTrue($valid->passes());
        $this->assertTrue($invalid->fails());
    }

    public function testValidatorMinLength() {
        $valid = Validator::make(['password' => 'secret123'], ['password' => 'min:6']);
        $invalid = Validator::make(['password' => 'abc'], ['password' => 'min:6']);

        $this->assertTrue($valid->passes());
        $this->assertTrue($invalid->fails());
    }

    public function testValidatorMultipleRules() {
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret123'
        ];

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $this->assertTrue($validator->passes());
    }

    // ===================
    // SECURITY TESTS
    // ===================

    public function testSecurityHashPassword() {
        $password = 'secret123';
        $hash = Security::hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(Security::verifyPassword($password, $hash));
    }

    public function testSecurityVerifyPasswordFails() {
        $hash = Security::hashPassword('correct-password');

        $this->assertFalse(Security::verifyPassword('wrong-password', $hash));
    }

    public function testSecurityGenerateUUID() {
        $uuid = Security::generateUUID();

        $this->assertNotNull($uuid);
        $this->assertEquals(36, strlen($uuid));
        $this->assertMatchesRegex('/^[a-f0-9-]{36}$/', $uuid);
    }

    public function testSecurityGenerateToken() {
        $token = Security::generateToken(32);

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token)); // hex = 2x bytes
    }

    // ===================
    // JWT TESTS
    // ===================

    public function testJwtEncodeDecode() {
        // Configurar secret para teste
        JWT::configure(['secret' => 'test-secret-key-for-testing']);

        $payload = [
            'sub' => '123',
            'email' => 'test@example.com'
        ];

        $token = JWT::encode($payload);
        $decoded = JWT::decode($token);

        $this->assertEquals('123', $decoded['sub']);
        $this->assertEquals('test@example.com', $decoded['email']);
    }

    public function testJwtExpiration() {
        JWT::configure(['secret' => 'test-secret-key']);

        // Token com 1 segundo de TTL
        $token = JWT::encode(['sub' => '1'], 1);

        // Deve estar válido agora
        $this->assertTrue(JWT::check($token));

        // Simular expiração (não podemos esperar em testes)
        // Então apenas verificamos que o tempo restante é calculado
        $remaining = JWT::getTimeRemaining($token);
        $this->assertNotNull($remaining);
    }

    public function testJwtInvalidToken() {
        JWT::configure(['secret' => 'test-secret']);

        $this->assertThrows(Exception::class, function() {
            JWT::decode('invalid.token.here');
        });
    }

    // ===================
    // CACHE TESTS
    // ===================

    public function testCacheSetGet() {
        Cache::set('test_key', 'test_value', 60);

        $this->assertTrue(Cache::has('test_key'));
        $this->assertEquals('test_value', Cache::get('test_key'));

        // Cleanup
        Cache::delete('test_key');
    }

    public function testCacheRemember() {
        $calls = 0;

        $value = Cache::remember('remember_test', 60, function() use (&$calls) {
            $calls++;
            return 'computed_value';
        });

        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $calls);

        // Segunda chamada não deve executar callback
        $value2 = Cache::remember('remember_test', 60, function() use (&$calls) {
            $calls++;
            return 'new_value';
        });

        $this->assertEquals('computed_value', $value2);
        $this->assertEquals(1, $calls); // Ainda 1

        // Cleanup
        Cache::delete('remember_test');
    }

    // ===================
    // EVENT TESTS
    // ===================

    public function testEventFire() {
        $called = false;
        $receivedData = null;

        Event::on('test.event', function($data) use (&$called, &$receivedData) {
            $called = true;
            $receivedData = $data;
        });

        Event::fire('test.event', ['message' => 'Hello']);

        $this->assertTrue($called);
        $this->assertEquals('Hello', $receivedData['message']);
    }

    public function testEventFilter() {
        Event::on('test.filter', function($value) {
            return $value * 2;
        });

        Event::on('test.filter', function($value) {
            return $value + 10;
        });

        $result = Event::filter('test.filter', 5);

        // 5 * 2 = 10, 10 + 10 = 20
        $this->assertEquals(20, $result);
    }

    // ===================
    // QUERY BUILDER TESTS (com Mock)
    // ===================

    public function testQueryBuilderWithMock() {
        // Criar mock database
        $mockDb = new MockDatabase();
        $mockDb->addTable('users', [
            ['id' => '1', 'name' => 'John', 'email' => 'john@test.com', 'ativo' => 1],
            ['id' => '2', 'name' => 'Jane', 'email' => 'jane@test.com', 'ativo' => 1],
            ['id' => '3', 'name' => 'Bob', 'email' => 'bob@test.com', 'ativo' => 0]
        ]);

        // Testar select
        $users = $mockDb->select('users', ['ativo' => 1]);
        $this->assertCount(2, $users);

        // Testar selectOne
        $john = $mockDb->selectOne('users', ['name' => 'John']);
        $this->assertEquals('john@test.com', $john['email']);

        // Testar insert
        $newId = $mockDb->insert('users', [
            'id' => '4',
            'name' => 'New User',
            'email' => 'new@test.com',
            'ativo' => 1
        ]);
        $this->assertEquals('4', $newId);
        $this->assertEquals(4, $mockDb->count('users'));

        // Testar update
        $mockDb->update('users', ['name' => 'John Doe'], ['id' => '1']);
        $updated = $mockDb->selectOne('users', ['id' => '1']);
        $this->assertEquals('John Doe', $updated['name']);

        // Testar delete
        $mockDb->delete('users', ['id' => '3']);
        $this->assertEquals(3, $mockDb->count('users'));
        $this->assertFalse($mockDb->exists('users', ['id' => '3']));
    }

    // ===================
    // ASSERTION EXAMPLES
    // ===================

    public function testAssertContains() {
        $array = ['apple', 'banana', 'orange'];

        $this->assertContains('banana', $array);
        $this->assertNotContains('grape', $array);
    }

    public function testAssertContainsString() {
        $string = 'Hello World';

        $this->assertContains('World', $string);
        $this->assertNotContains('Goodbye', $string);
    }

    public function testAssertEmpty() {
        $this->assertEmpty([]);
        $this->assertEmpty('');
        $this->assertNotEmpty(['item']);
        $this->assertNotEmpty('text');
    }

    public function testAssertInstanceOf() {
        $validator = Validator::make([], []);

        $this->assertInstanceOf(Validator::class, $validator);
    }
}
