<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tree;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Persistence\BaseRepository;
use DateTime;

class DatabaseTreeNodeRepository extends BaseRepository implements TreeNodeRepository
{
    private const TABLE = 'tree_nodes';
    private const COLUMNS = 'id, tree_id, parent_id, name, sort_order, type_class, type_data';

    public function __construct(
        DatabaseConnection $connection,
        private TreeNodeDataMapper $dataMapper
    ) {
        parent::__construct($connection);
    }

    #[\Override]
    public function findById(int $id): ?AbstractTreeNode
    {
        $data = $this->findByField(self::TABLE, 'id', $id, self::COLUMNS);

        if (!$data) {
            return null;
        }

        return $this->dataMapper->mapToEntity($data);
    }

    #[\Override]
    public function findByTreeId(int $treeId): array
    {
        $data = $this->findAllByField(self::TABLE, 'tree_id', $treeId, self::COLUMNS, 'sort_order');

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findChildren(int $parentId): array
    {
        $data = $this->findAllByField(self::TABLE, 'parent_id', $parentId, self::COLUMNS, 'sort_order');

        return $this->dataMapper->mapToEntities($data);
    }

    public function findRootNodes(int $treeId): array
    {
        $sql = 'SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data ' .
               'FROM tree_nodes WHERE tree_id = ? AND parent_id IS NULL ORDER BY sort_order';
        $statement = $this->connection->query($sql, [$treeId]);
        $data = $statement->fetchAll();

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findTreeStructure(int $treeId): array
    {
        $data = $this->findAllByField(self::TABLE, 'tree_id', $treeId, self::COLUMNS, 'sort_order');
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
            // Insert - exclude ID from data, add timestamps
            $insertData = [
                'tree_id' => $data['tree_id'],
                'parent_id' => $data['parent_id'],
                'name' => $data['name'],
                'sort_order' => $data['sort_order'],
                'type_class' => $data['type_class'],
                'type_data' => $data['type_data'],
                'created_at' => $now,
                'updated_at' => $now
            ];
            
            $this->insert(self::TABLE, $insertData);
        } else {
            // Update - exclude ID and created_at from data
            $updateData = [
                'tree_id' => $data['tree_id'],
                'parent_id' => $data['parent_id'],
                'name' => $data['name'],
                'sort_order' => $data['sort_order'],
                'type_class' => $data['type_class'],
                'type_data' => $data['type_data'],
                'updated_at' => $now
            ];
            
            $this->update(self::TABLE, $updateData, 'id', $data['id']);
        }
    }

    #[\Override]
    public function delete(int $id): void
    {
        $this->deleteById(self::TABLE, 'id', $id);
    }

    #[\Override]
    public function deleteByTreeId(int $treeId): void
    {
        $this->deleteById(self::TABLE, 'tree_id', $treeId);
    }
}
