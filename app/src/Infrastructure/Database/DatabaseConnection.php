<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDOStatement;

interface DatabaseConnection
{
    /**
     * Execute a query and return a statement
     */
    public function query(string $sql, array $params = []): PDOStatement;

    /**
     * Execute a statement and return the number of affected rows
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Get the last inserted ID
     */
    public function lastInsertId(): string;

    /**
     * Begin a transaction
     */
    public function beginTransaction(): void;

    /**
     * Commit a transaction
     */
    public function commit(): void;

    /**
     * Rollback a transaction
     */
    public function rollback(): void;

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool;
} 