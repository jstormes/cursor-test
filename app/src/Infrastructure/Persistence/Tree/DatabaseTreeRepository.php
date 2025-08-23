<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Persistence\BaseRepository;

class DatabaseTreeRepository extends BaseRepository implements TreeRepository
{
    private const TABLE = 'trees';
    private const COLUMNS = 'id, name, description, created_at, updated_at, is_active';

    public function __construct(
        DatabaseConnection $connection,
        private TreeDataMapper $dataMapper
    ) {
        parent::__construct($connection);
    }

    #[\Override]
    public function findById(int $id): ?Tree
    {
        $data = $this->findByField(self::TABLE, 'id', $id, self::COLUMNS);

        if (!$data) {
            return null;
        }

        return $this->dataMapper->mapToEntity($data);
    }

    #[\Override]
    public function findByName(string $name): ?Tree
    {
        $data = $this->findByField(self::TABLE, 'name', $name, self::COLUMNS);

        if (!$data) {
            return null;
        }

        return $this->dataMapper->mapToEntity($data);
    }

    #[\Override]
    public function findAll(): array
    {
        $data = $this->findAllRecords(self::TABLE, self::COLUMNS, 'name');

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function findActive(): array
    {
        $data = $this->findAllByField(self::TABLE, 'is_active', 1, self::COLUMNS, 'name');

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function save(Tree $tree): void
    {
        $data = $this->dataMapper->mapToArray($tree);

        if ($tree->getId() === null) {
            // Insert - exclude ID from data
            $insertData = [
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'is_active' => $data['is_active']
            ];
            
            $newId = $this->insert(self::TABLE, $insertData);

            // Set the ID using the setId method
            $tree->setId($newId);
        } else {
            // Update - exclude ID from data
            $updateData = [
                'name' => $data['name'],
                'description' => $data['description'],
                'updated_at' => $data['updated_at'],
                'is_active' => $data['is_active']
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
    public function softDelete(int $id): void
    {
        $this->softDeleteRecord(self::TABLE, 'id', $id, 'is_active');
    }

    #[\Override]
    public function restore(int $id): void
    {
        $this->restoreRecord(self::TABLE, 'id', $id, 'is_active');
    }

    #[\Override]
    public function findDeleted(): array
    {
        $data = $this->findAllByField(self::TABLE, 'is_active', 0, self::COLUMNS, 'name');

        return $this->dataMapper->mapToEntities($data);
    }

    #[\Override]
    public function deleteByTreeId(int $treeId): void
    {
        // This method is for deleting nodes associated with a tree
        // For the tree repository, this might be handled by cascading deletes
        // or delegated to a node repository
        $this->deleteById('tree_nodes', 'tree_id', $treeId);
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
