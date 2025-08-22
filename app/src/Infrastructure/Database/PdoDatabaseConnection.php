<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOStatement;
use PDOException;

class PdoDatabaseConnection implements DatabaseConnection
{
    protected PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );

        $this->pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    #[\Override]
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    #[\Override]
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    #[\Override]
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    #[\Override]
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    #[\Override]
    public function commit(): void
    {
        $this->pdo->commit();
    }

    #[\Override]
    public function rollback(): void
    {
        $this->pdo->rollback();
    }

    #[\Override]
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
