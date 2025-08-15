<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeRepository
{
    /**
     * Find a tree by ID
     */
    public function findById(int $id): ?Tree;

    /**
     * Find all active trees
     */
    public function findActive(): array;

    /**
     * Save a tree (insert or update)
     */
    public function save(Tree $tree): void;

    /**
     * Soft delete a tree by ID
     */
    public function softDelete(int $id): void;

    /**
     * Restore a soft-deleted tree by ID
     */
    public function restore(int $id): void;

    /**
     * Find all deleted trees
     */
    public function findDeleted(): array;

    /**
     * Delete all nodes associated with a tree
     */
    public function deleteByTreeId(int $treeId): void;

    /**
     * Find tree structure with all nodes
     */
    public function findTreeStructure(int $treeId): ?Tree;

    /**
     * Find a tree by name
     */
    public function findByName(string $name): ?Tree;

    /**
     * Find all trees
     */
    public function findAll(): array;

    /**
     * Delete a tree by ID
     */
    public function delete(int $id): void;
}
