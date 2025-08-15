<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\UnitOfWork;

class TreeService
{
    public function __construct(
        private TreeRepository $treeRepository,
        private TreeNodeRepository $nodeRepository,
        private UnitOfWork $unitOfWork
    ) {
    }

    /**
     * Create a new tree with initial nodes
     */
    public function createTreeWithNodes(string $name, ?string $description, array $nodes): Tree
    {
        $this->unitOfWork->beginTransaction();

        try {
            // Create the tree
            $tree = new Tree(null, $name, $description);
            $this->treeRepository->save($tree);

            // Add nodes to the tree
            foreach ($nodes as $nodeData) {
                $treeId = $nodeData['tree_id'] ?? $tree->getId();
                if ($treeId === null) {
                    throw new \InvalidArgumentException('Tree ID is required for node creation');
                }
                $node = $this->createNodeFromData($nodeData, $treeId);
                $this->nodeRepository->save($node);
            }

            $this->unitOfWork->commit();
            return $tree;
        } catch (\Exception $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }

    /**
     * Move a node to a new parent
     */
    public function moveNode(int $nodeId, int $newParentId): void
    {
        $this->unitOfWork->beginTransaction();

        try {
            $node = $this->nodeRepository->findById($nodeId);
            if (!$node) {
                throw new \InvalidArgumentException("Node with ID {$nodeId} not found");
            }

            // Update the node's parent
            $node = $this->createNodeWithNewParent($node, $newParentId);
            $this->nodeRepository->save($node);

            $this->unitOfWork->commit();
        } catch (\Exception $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }

    /**
     * Delete a tree and all its nodes
     */
    public function deleteTreeWithNodes(int $treeId): void
    {
        $this->unitOfWork->beginTransaction();

        try {
            // Delete all nodes first
            $this->nodeRepository->deleteByTreeId($treeId);

            // Delete the tree
            $this->treeRepository->delete($treeId);

            $this->unitOfWork->commit();
        } catch (\Exception $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }

    /**
     * Get a complete tree structure
     */
    public function getTreeStructure(int $treeId): array
    {
        $tree = $this->treeRepository->findById($treeId);
        if (!$tree) {
            throw new \InvalidArgumentException("Tree with ID {$treeId} not found");
        }

        return $this->nodeRepository->findTreeStructure($treeId);
    }

    /**
     * Create a node from data array
     */
    private function createNodeFromData(array $nodeData, int $treeId): AbstractTreeNode
    {
        $type = $nodeData['type'] ?? 'SimpleNode';
        $name = $nodeData['name'];
        $parentId = $nodeData['parent_id'] ?? null;
        $sortOrder = $nodeData['sort_order'] ?? 0;
        $typeData = $nodeData['type_data'] ?? [];

        return match ($type) {
            'SimpleNode' => new \App\Domain\Tree\SimpleNode(
                null,
                $name,
                $treeId,
                $parentId,
                $sortOrder
            ),
            'ButtonNode' => new \App\Domain\Tree\ButtonNode(
                null,
                $name,
                $treeId,
                $parentId,
                $sortOrder,
                $typeData
            ),
            default => throw new \InvalidArgumentException("Unknown node type: {$type}")
        };
    }

    /**
     * Create a new node instance with updated parent
     */
    private function createNodeWithNewParent(AbstractTreeNode $node, int $newParentId): AbstractTreeNode
    {
        return match ($node->getType()) {
            'SimpleNode' => new \App\Domain\Tree\SimpleNode(
                $node->getId(),
                $node->getName(),
                $node->getTreeId(),
                $newParentId,
                $node->getSortOrder()
            ),
            'ButtonNode' => new \App\Domain\Tree\ButtonNode(
                $node->getId(),
                $node->getName(),
                $node->getTreeId(),
                $newParentId,
                $node->getSortOrder(),
                $node->getTypeData()
            ),
            default => throw new \InvalidArgumentException("Unknown node type: {$node->getType()}")
        };
    }
}
