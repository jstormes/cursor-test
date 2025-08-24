<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeNodeRepository
{
    /**
     * Find a tree node by ID
     */
    public function findById(int $id): ?AbstractTreeNode;

    /**
     * Find all nodes for a specific tree
     */
    public function findByTreeId(int $treeId): array;

    /**
     * Find children of a specific parent node
     */
    public function findChildren(int $parentId): array;

    /**
     * Save a tree node (insert or update)
     */
    public function save(AbstractTreeNode $node): void;

    /**
     * Delete a tree node by ID
     */
    public function delete(int $id): void;

    /**
     * Delete all nodes for a specific tree
     */
    public function deleteByTreeId(int $treeId): void;

    /**
     * Find tree structure for a specific tree
     */
    public function findTreeStructure(int $treeId): array;

    /**
     * Find the previous sibling of a node (same parent, lower sort_order)
     */
    public function findPreviousSibling(int $nodeId): ?AbstractTreeNode;

    /**
     * Find the next sibling of a node (same parent, higher sort_order)
     */
    public function findNextSibling(int $nodeId): ?AbstractTreeNode;
}
