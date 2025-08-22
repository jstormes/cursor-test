<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Tree\Tree;
use App\Domain\Tree\AbstractTreeNode;

class DatabaseUnitOfWork implements UnitOfWork
{
    private array $newEntities = [];
    private array $dirtyEntities = [];
    private array $deletedEntities = [];

    public function __construct(
        private DatabaseConnection $connection,
        private ?TreeDataMapper $treeMapper = null,
        private ?TreeNodeDataMapper $nodeMapper = null
    ) {
        // Initialize mappers if not provided
        $this->treeMapper ??= new TreeDataMapper();
        $this->nodeMapper ??= new TreeNodeDataMapper();
    }

    #[\Override]
    public function registerNew(object $entity): void
    {
        $this->newEntities[] = $entity;
    }

    #[\Override]
    public function registerDirty(object $entity): void
    {
        $this->dirtyEntities[] = $entity;
    }

    #[\Override]
    public function registerDeleted(object $entity): void
    {
        $this->deletedEntities[] = $entity;
    }

    #[\Override]
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    #[\Override]
    public function commit(): void
    {
        try {
            // Process new entities
            foreach ($this->newEntities as $entity) {
                $this->processNewEntity($entity);
            }

            // Process dirty entities
            foreach ($this->dirtyEntities as $entity) {
                $this->processDirtyEntity($entity);
            }

            // Process deleted entities
            foreach ($this->deletedEntities as $entity) {
                $this->processDeletedEntity($entity);
            }

            $this->connection->commit();
            $this->clear();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    #[\Override]
    public function rollback(): void
    {
        $this->connection->rollback();
        $this->clear();
    }

    #[\Override]
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    private function processNewEntity(object $entity): void
    {
        match (true) {
            $entity instanceof Tree => $this->insertTree($entity),
            $entity instanceof AbstractTreeNode => $this->insertTreeNode($entity),
            default => throw new \InvalidArgumentException('Unsupported entity type: ' . get_class($entity))
        };
    }

    private function processDirtyEntity(object $entity): void
    {
        match (true) {
            $entity instanceof Tree => $this->updateTree($entity),
            $entity instanceof AbstractTreeNode => $this->updateTreeNode($entity),
            default => throw new \InvalidArgumentException('Unsupported entity type: ' . get_class($entity))
        };
    }

    private function processDeletedEntity(object $entity): void
    {
        match (true) {
            $entity instanceof Tree => $this->deleteTree($entity),
            $entity instanceof AbstractTreeNode => $this->deleteTreeNode($entity),
            default => throw new \InvalidArgumentException('Unsupported entity type: ' . get_class($entity))
        };
    }

    private function insertTree(Tree $tree): void
    {
        $data = $this->treeMapper->mapToArray($tree);
        $sql = 'INSERT INTO trees (name, description, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?)';
        $this->connection->execute($sql, [
            $data['name'],
            $data['description'],
            $data['created_at'],
            $data['updated_at'],
            $data['is_active']
        ]);

        // Set the ID using the setter method
        $tree->setId((int) $this->connection->lastInsertId());
    }

    private function updateTree(Tree $tree): void
    {
        $data = $this->treeMapper->mapToArray($tree);
        $sql = 'UPDATE trees SET name = ?, description = ?, updated_at = ?, is_active = ? WHERE id = ?';
        $this->connection->execute($sql, [
            $data['name'],
            $data['description'],
            $data['updated_at'],
            $data['is_active'],
            $data['id']
        ]);
    }

    private function deleteTree(Tree $tree): void
    {
        if ($tree->getId() === null) {
            throw new \InvalidArgumentException('Cannot delete tree without ID');
        }
        $sql = 'DELETE FROM trees WHERE id = ?';
        $this->connection->execute($sql, [$tree->getId()]);
    }

    private function insertTreeNode(AbstractTreeNode $node): void
    {
        $data = $this->nodeMapper->mapToArray($node);
        $sql = 'INSERT INTO tree_nodes (name, tree_id, parent_id, sort_order, type_class, type_data) VALUES (?, ?, ?, ?, ?, ?)';
        $this->connection->execute($sql, [
            $data['name'],
            $data['tree_id'],
            $data['parent_id'],
            $data['sort_order'],
            $data['type_class'],
            $data['type_data']
        ]);

        // Set the ID using the setter method
        $node->setId((int) $this->connection->lastInsertId());
    }

    private function updateTreeNode(AbstractTreeNode $node): void
    {
        $data = $this->nodeMapper->mapToArray($node);
        $sql = 'UPDATE tree_nodes SET name = ?, tree_id = ?, parent_id = ?, sort_order = ?, type_class = ?, type_data = ? WHERE id = ?';
        $this->connection->execute($sql, [
            $data['name'],
            $data['tree_id'],
            $data['parent_id'],
            $data['sort_order'],
            $data['type_class'],
            $data['type_data'],
            $data['id']
        ]);
    }

    private function deleteTreeNode(AbstractTreeNode $node): void
    {
        if ($node->getId() === null) {
            throw new \InvalidArgumentException('Cannot delete tree node without ID');
        }
        $sql = 'DELETE FROM tree_nodes WHERE id = ?';
        $this->connection->execute($sql, [$node->getId()]);
    }


    private function clear(): void
    {
        $this->newEntities = [];
        $this->dirtyEntities = [];
        $this->deletedEntities = [];
    }
}
