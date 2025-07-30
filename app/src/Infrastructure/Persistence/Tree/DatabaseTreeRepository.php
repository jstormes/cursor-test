<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;

class DatabaseTreeRepository implements TreeRepository
{
    public function __construct(
        private DatabaseConnection $connection,
        private TreeDataMapper $dataMapper
    ) {}

    public function findById(int $id): ?Tree
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE id = ?';
        $statement = $this->connection->query($sql, [$id]);
        $data = $statement->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->dataMapper->mapToEntity($data);
    }

    public function findByName(string $name): ?Tree
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE name = ?';
        $statement = $this->connection->query($sql, [$name]);
        $data = $statement->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->dataMapper->mapToEntity($data);
    }

    public function findAll(): array
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY name';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();
        
        return $this->dataMapper->mapToEntities($data);
    }

    public function findActive(): array
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = 1 ORDER BY name';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();
        
        return $this->dataMapper->mapToEntities($data);
    }

    public function save(Tree $tree): void
    {
        $data = $this->dataMapper->mapToArray($tree);
        
        if ($tree->getId() === null) {
            // Insert
            $sql = 'INSERT INTO trees (name, description, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?)';
            $this->connection->execute($sql, [
                $data['name'],
                $data['description'],
                $data['created_at'],
                $data['updated_at'],
                $data['is_active']
            ]);
            
            // Set the ID
            $tree = new Tree(
                (int) $this->connection->lastInsertId(),
                $tree->getName(),
                $tree->getDescription(),
                $tree->getCreatedAt(),
                $tree->getUpdatedAt(),
                $tree->isActive()
            );
        } else {
            // Update
            $sql = 'UPDATE trees SET name = ?, description = ?, updated_at = ?, is_active = ? WHERE id = ?';
            $this->connection->execute($sql, [
                $data['name'],
                $data['description'],
                $data['updated_at'],
                $data['is_active'],
                $data['id']
            ]);
        }
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM trees WHERE id = ?';
        $this->connection->execute($sql, [$id]);
    }
} 