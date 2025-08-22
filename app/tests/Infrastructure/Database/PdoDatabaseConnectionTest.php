<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Infrastructure\Database\PdoDatabaseConnection;
use Tests\TestCase;
use PDO;
use PDOStatement;

class PdoDatabaseConnectionTest extends TestCase
{
    private PdoDatabaseConnection $connection;

    protected function setUp(): void
    {
        // Use SQLite in-memory for fast integration testing
        $config = [
            'host' => 'localhost',  // Not used for SQLite
            'database' => ':memory:',
            'username' => '',
            'password' => '',
        ];

        // Create a testable connection using SQLite
        $this->connection = new class($config) extends PdoDatabaseConnection {
            public function __construct(array $config)
            {
                // Override to use SQLite instead of MySQL for testing
                $this->pdo = new PDO('sqlite::memory:', '', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
        };

        // Create test table
        $this->connection->execute('
            CREATE TABLE test_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                value INTEGER
            )
        ');
    }

    public function testQueryReturnsStatement(): void
    {
        $statement = $this->connection->query('SELECT * FROM test_table');
        
        $this->assertInstanceOf(PDOStatement::class, $statement);
        $this->assertIsArray($statement->fetchAll());
    }

    public function testExecuteReturnsRowCount(): void
    {
        $rowCount = $this->connection->execute(
            'INSERT INTO test_table (name, value) VALUES (?, ?)',
            ['test', 123]
        );
        
        $this->assertEquals(1, $rowCount);
    }

    public function testLastInsertIdReturnsString(): void
    {
        $this->connection->execute(
            'INSERT INTO test_table (name, value) VALUES (?, ?)',
            ['test', 123]
        );
        
        $lastId = $this->connection->lastInsertId();
        
        $this->assertIsString($lastId);
        $this->assertEquals('1', $lastId);
    }

    public function testTransactionMethods(): void
    {
        // Test basic transaction operations
        $this->assertFalse($this->connection->inTransaction());
        
        $this->connection->beginTransaction();
        $this->assertTrue($this->connection->inTransaction());
        
        $this->connection->execute(
            'INSERT INTO test_table (name, value) VALUES (?, ?)',
            ['transaction_test', 456]
        );
        
        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());
        
        // Verify data was committed
        $statement = $this->connection->query(
            'SELECT COUNT(*) as count FROM test_table WHERE name = ?',
            ['transaction_test']
        );
        $result = $statement->fetch();
        $this->assertEquals(1, $result['count']);
    }
}
