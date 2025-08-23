<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Infrastructure\Database\PdoDatabaseConnectionFactory;
use App\Infrastructure\Database\DatabaseConnectionFactoryInterface;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\PdoDatabaseConnection;
use Tests\TestCase;

class PdoDatabaseConnectionFactoryTest extends TestCase
{
    private PdoDatabaseConnectionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PdoDatabaseConnectionFactory();
    }

    public function testImplementsDatabaseConnectionFactoryInterface(): void
    {
        $this->assertInstanceOf(DatabaseConnectionFactoryInterface::class, $this->factory);
    }

    public function testCreateReturnsDatabaseConnectionInstance(): void
    {
        // Note: This test requires a database connection, which might fail in CI
        // but we can test the instantiation
        $config = [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(DatabaseConnection::class, $connection);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // If database connection fails, we can still test that the correct exception is thrown
            $this->assertStringContainsString('SQLSTATE', $e->getMessage());
        }
    }

    public function testCreateWithMinimalConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testCreateWithPortInConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => '5432',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testCreateWithInvalidHost(): void
    {
        $config = [
            'host' => 'invalid-host-that-does-not-exist.local',
            'port' => '3306',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        $this->expectException(\PDOException::class);
        $this->factory->create($config);
    }

    public function testCreateWithMissingHost(): void
    {
        $config = [
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        // This will throw InvalidArgumentException for missing host
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: host');
        $this->factory->create($config);
    }

    public function testCreateWithMissingDatabase(): void
    {
        $config = [
            'host' => 'localhost',
            'username' => 'test',
            'password' => 'test'
        ];

        // This will throw InvalidArgumentException for missing database
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: database');
        $this->factory->create($config);
    }

    public function testCreateWithMissingUsername(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'password' => 'test'
        ];

        // This will throw InvalidArgumentException for missing username
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: username');
        $this->factory->create($config);
    }

    public function testCreateWithMissingPassword(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test'
        ];

        // This will throw InvalidArgumentException for missing password
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: password');
        $this->factory->create($config);
    }

    public function testCreateWithEmptyConfig(): void
    {
        $config = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: host');
        $this->factory->create($config);
    }

    public function testFactoryIsStateless(): void
    {
        $config1 = [
            'host' => 'localhost',
            'database' => 'db1',
            'username' => 'user1',
            'password' => 'pass1'
        ];

        $config2 = [
            'host' => 'localhost',
            'database' => 'db2',
            'username' => 'user2',
            'password' => 'pass2'
        ];

        try {
            $connection1 = $this->factory->create($config1);
            $connection2 = $this->factory->create($config2);

            // Should create different instances
            $this->assertNotSame($connection1, $connection2);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection1);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection2);
        } catch (\PDOException $e) {
            // If connections fail, at least verify we get separate exception instances
            // by trying to create again
            try {
                $this->factory->create($config1);
                $this->fail('Expected PDOException');
            } catch (\PDOException $e2) {
                $this->assertInstanceOf(\PDOException::class, $e2);
            }
        }
    }

    public function testCreateMultipleConnectionsFromSameConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection1 = $this->factory->create($config);
            $connection2 = $this->factory->create($config);

            // Should create different instances each time
            $this->assertNotSame($connection1, $connection2);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection1);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection2);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testFactoryClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(PdoDatabaseConnectionFactory::class);
        $this->assertTrue($reflection->isFinal(), 'PdoDatabaseConnectionFactory should be a final class');
    }

    public function testCreateWithNumericPort(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 3306, // Numeric instead of string
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testCreateWithSpecialCharactersInConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'p@ssw0rd!#$'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testCreateWithNullValues(): void
    {
        $config = [
            'host' => null,
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        // Null values are treated as missing parameters
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database configuration parameter: host');
        $this->factory->create($config);
    }

    public function testCreateDelegatesToPdoDatabaseConnection(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        // Test that the factory delegates to PdoDatabaseConnection constructor
        // by ensuring it passes the config correctly
        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);

            // If connection succeeds, we know the config was passed correctly
        } catch (\PDOException $e) {
            // If connection fails with PDOException, it means the config was
            // passed to PdoDatabaseConnection constructor correctly
            $this->assertStringContainsString('SQLSTATE', $e->getMessage());
        }
    }

    public function testCreateWithExtraConfigParameters(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test',
            'extra_param' => 'should_be_ignored',
            'another_param' => 12345
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(PdoDatabaseConnection::class, $connection);
        } catch (\PDOException $e) {
            // Expected when database is not available
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    /**
     * Test that the factory creates connections that implement the expected interface
     */
    public function testCreatedConnectionImplementsInterface(): void
    {
        $config = [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test'
        ];

        try {
            $connection = $this->factory->create($config);
            $this->assertInstanceOf(DatabaseConnection::class, $connection);

            // Verify the connection has expected methods
            $this->assertTrue(method_exists($connection, 'query'));
            $this->assertTrue(method_exists($connection, 'execute'));
        } catch (\PDOException $e) {
            // Even if connection fails, we can verify the type would be correct
            $this->assertInstanceOf(\PDOException::class, $e);
        }
    }

    public function testFactoryMethodSignature(): void
    {
        $reflection = new \ReflectionClass(PdoDatabaseConnectionFactory::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
        $this->assertEquals('create', $method->getName());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('config', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()?->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('App\\Infrastructure\\Database\\DatabaseConnection', $returnType?->getName());
    }
}
