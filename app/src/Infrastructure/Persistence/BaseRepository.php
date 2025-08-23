<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Database\DatabaseConnection;

abstract class BaseRepository
{
    public function __construct(
        protected DatabaseConnection $connection
    ) {
    }

    protected function findByField(string $table, string $field, mixed $value, string $columns = '*'): ?array
    {
        $sql = "SELECT {$columns} FROM {$table} WHERE {$field} = ?";
        $statement = $this->connection->query($sql, [$value]);
        $data = $statement->fetch();

        return $data ?: null;
    }

    protected function findAllByField(string $table, string $field, mixed $value, string $columns = '*', string $orderBy = 'id'): array
    {
        $sql = "SELECT {$columns} FROM {$table} WHERE {$field} = ? ORDER BY {$orderBy}";
        $statement = $this->connection->query($sql, [$value]);
        
        return $statement->fetchAll();
    }

    protected function findAllRecords(string $table, string $columns = '*', string $orderBy = 'id'): array
    {
        $sql = "SELECT {$columns} FROM {$table} ORDER BY {$orderBy}";
        $statement = $this->connection->query($sql);
        
        return $statement->fetchAll();
    }

    protected function exists(string $table, string $field, mixed $value): bool
    {
        $sql = "SELECT 1 FROM {$table} WHERE {$field} = ? LIMIT 1";
        $statement = $this->connection->query($sql, [$value]);
        
        return $statement->fetch() !== false;
    }

    protected function count(string $table, ?string $whereClause = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        
        $statement = $this->connection->query($sql, $params);
        $result = $statement->fetch();
        
        return (int) $result['count'];
    }

    protected function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->connection->execute($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }

    protected function update(string $table, array $data, string $idField, mixed $idValue): void
    {
        $setClause = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$idField} = ?";
        
        $params = array_values($data);
        $params[] = $idValue;
        
        $this->connection->execute($sql, $params);
    }

    protected function deleteById(string $table, string $idField, mixed $idValue): void
    {
        $sql = "DELETE FROM {$table} WHERE {$idField} = ?";
        $this->connection->execute($sql, [$idValue]);
    }

    protected function softDeleteRecord(string $table, string $idField, mixed $idValue, string $isActiveField = 'is_active'): void
    {
        $sql = "UPDATE {$table} SET {$isActiveField} = 0, updated_at = NOW() WHERE {$idField} = ?";
        $this->connection->execute($sql, [$idValue]);
    }

    protected function restoreRecord(string $table, string $idField, mixed $idValue, string $isActiveField = 'is_active'): void
    {
        $sql = "UPDATE {$table} SET {$isActiveField} = 1, updated_at = NOW() WHERE {$idField} = ?";
        $this->connection->execute($sql, [$idValue]);
    }

    protected function buildWhereClause(array $criteria): array
    {
        if (empty($criteria)) {
            return ['', []];
        }

        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $conditions[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        return [implode(' AND ', $conditions), $params];
    }
}