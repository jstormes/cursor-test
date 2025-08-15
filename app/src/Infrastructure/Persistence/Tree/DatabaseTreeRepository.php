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
    ) {
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function findAll(): array
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY name';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findActive(): array
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = 1 ORDER BY name';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
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

            // Set the ID using reflection since there's no setId method
            $reflection = new \ReflectionClass($tree);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($tree, (int) $this->connection->lastInsertId());
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

    #[\Override]
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM trees WHERE id = ?';
        $this->connection->execute($sql, [$id]);
    }

    #[\Override]
    public function softDelete(int $id): void
    {
        $sql = 'UPDATE trees SET is_active = 0, updated_at = NOW() WHERE id = ?';
        $this->connection->execute($sql, [$id]);
    }

    #[\Override]
    public function restore(int $id): void
    {
        $sql = 'UPDATE trees SET is_active = 1, updated_at = NOW() WHERE id = ?';
        $this->connection->execute($sql, [$id]);
    }

    #[\Override]
    public function findDeleted(): array
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = 0 ORDER BY name';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function deleteByTreeId(int $treeId): void
    {
        // This method is for deleting nodes associated with a tree
        // For the tree repository, this might be handled by cascading deletes
        // or delegated to a node repository
        $sql = 'DELETE FROM tree_nodes WHERE tree_id = ?';
        $this->connection->execute($sql, [$treeId]);
    }

    #[\Override]
    public function findTreeStructure(int $treeId): ?Tree
    {
        // Find the tree with all its nodes loaded
        $tree = $this->findById($treeId);
        if (!$tree) {
            return null;
        }

        // Load nodes would typically be done here
        // For now, just return the tree without nodes
        return $tree;
    }
}
