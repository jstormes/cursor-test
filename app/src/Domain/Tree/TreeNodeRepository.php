<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeNodeRepository
{
    /**
     * Find a tree node by ID
     */
    public function findById(int $id): ?TreeNode;

    /**
     * Find all nodes for a specific tree
     */
    public function findByTreeId(int $treeId): array;

    /**
     * Find children of a specific parent node
     */
    public function findChildren(int $parentId): array;

    /**
     * Find root nodes (nodes with no parent) for a specific tree
     */
    public function findRootNodes(int $treeId): array;

    /**
     * Find a complete tree structure
     */
    public function findTreeStructure(int $treeId): array;

    /**
     * Save a tree node (insert or update)
     */
    public function save(TreeNode $node): void;

    /**
     * Delete a tree node by ID
     */
    public function delete(int $id): void;

    /**
     * Delete all nodes for a specific tree
     */
    public function deleteByTreeId(int $treeId): void;
} 