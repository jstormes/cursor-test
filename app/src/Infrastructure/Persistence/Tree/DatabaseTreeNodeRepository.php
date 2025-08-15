<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tree;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeNodeDataMapper;
use DateTime;

class DatabaseTreeNodeRepository implements TreeNodeRepository
{
    public function __construct(
        private DatabaseConnection $connection,
        private TreeNodeDataMapper $dataMapper
    ) {
    }

    #[\Override]
    public function findById(int $id): ?AbstractTreeNode
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE id = ?';
        $statement = $this->connection->query($sql, [$id]);
        $data = $statement->fetch();

        if (!$data) {
            return null;
        }

        return $this->dataMapper->mapToEntity($data);
    }

    #[\Override]
    public function findByTreeId(int $treeId): array
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order';
        $statement = $this->connection->query($sql, [$treeId]);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findChildren(int $parentId): array
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE parent_id = ? ORDER BY sort_order';
        $statement = $this->connection->query($sql, [$parentId]);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findRootNodes(int $treeId): array
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? AND parent_id IS NULL ORDER BY sort_order';
        $statement = $this->connection->query($sql, [$treeId]);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findTreeStructure(int $treeId): array
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order';
        $statement = $this->connection->query($sql, [$treeId]);
        $data = $statement->fetchAll();

        $nodes = $this->dataMapper->mapToEntities($data);

        // Build tree structure
        $nodeMap = [];
        $rootNodes = [];

        foreach ($nodes as $node) {
            $nodeMap[$node->getId()] = $node;
        }

        foreach ($nodes as $node) {
            if ($node->getParentId() === null) {
                $rootNodes[] = $node;
            } else {
                $parent = $nodeMap[$node->getParentId()] ?? null;
                if ($parent) {
                    $parent->addChild($node);
                }
            }
        }

        return $rootNodes;
    }

    #[\Override]
    public function save(AbstractTreeNode $node): void
    {
        $data = $this->dataMapper->mapToArray($node);
        $now = (new DateTime())->format('Y-m-d H:i:s');

        if ($node->getId() === null) {
            // Insert
            $sql = 'INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $this->connection->execute($sql, [
                $data['tree_id'],
                $data['parent_id'],
                $data['name'],
                $data['sort_order'],
                $data['type_class'],
                $data['type_data'],
                $now,
                $now
            ]);
        } else {
            // Update
            $sql = 'UPDATE tree_nodes SET tree_id = ?, parent_id = ?, name = ?, sort_order = ?, type_class = ?, type_data = ?, updated_at = ? WHERE id = ?';
            $this->connection->execute($sql, [
                $data['tree_id'],
                $data['parent_id'],
                $data['name'],
                $data['sort_order'],
                $data['type_class'],
                $data['type_data'],
                $now,
                $data['id']
            ]);
        }
    }

    #[\Override]
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM tree_nodes WHERE id = ?';
        $this->connection->execute($sql, [$id]);
    }

    #[\Override]
    public function deleteByTreeId(int $treeId): void
    {
        $sql = 'DELETE FROM tree_nodes WHERE tree_id = ?';
        $this->connection->execute($sql, [$treeId]);
    }
}
