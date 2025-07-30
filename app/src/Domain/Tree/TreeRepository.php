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
     * Find a tree by name
     */
    public function findByName(string $name): ?Tree;

    /**
     * Find all trees
     */
    public function findAll(): array;

    /**
     * Find all active trees
     */
    public function findActive(): array;

    /**
     * Save a tree (insert or update)
     */
    public function save(Tree $tree): void;

    /**
     * Delete a tree by ID (hard delete)
     */
    public function delete(int $id): void;

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
} 