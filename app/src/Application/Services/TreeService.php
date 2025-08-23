<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\ValidationException;
use App\Application\Validation\TreeNodeValidator;
use App\Application\Validation\TreeValidator;
use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\InvalidTreeOperationException;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeNodeFactory;
use App\Domain\Tree\TreeNotFoundException;
use App\Domain\Tree\TreeNodeNotFoundException;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\UnitOfWork;
use App\Infrastructure\Time\ClockInterface;

class TreeService
{
    public function __construct(
        private TreeRepository $treeRepository,
        private TreeNodeRepository $nodeRepository,
        private UnitOfWork $unitOfWork,
        private TreeNodeFactory $nodeFactory,
        private TreeValidator $treeValidator,
        private TreeNodeValidator $nodeValidator,
        private ClockInterface $clock
    ) {
    }

    /**
     * Create a new tree with initial nodes
     */
    public function createTreeWithNodes(string $name, ?string $description, array $nodes): Tree
    {
        // Validate tree data
        $treeData = ['name' => $name, 'description' => $description];
        $treeValidation = $this->treeValidator->validate($treeData);
        if (!$treeValidation->isValid()) {
            throw new ValidationException($treeValidation, 'Tree validation failed');
        }

        // Sanitize tree data
        $sanitizedTreeData = $this->treeValidator->sanitize($treeData);

        $this->unitOfWork->beginTransaction();

        try {
            // Create the tree and register it as new
            $tree = new Tree(null, $sanitizedTreeData['name'], $sanitizedTreeData['description'] ?? null, null, null, true, $this->clock);
            $this->unitOfWork->registerNew($tree);

            // Add nodes to the tree and register them as new
            foreach ($nodes as $nodeData) {
                // Validate and sanitize node data
                $nodeValidation = $this->nodeValidator->validate($nodeData);
                if (!$nodeValidation->isValid()) {
                    throw new ValidationException($nodeValidation, 'Node validation failed');
                }
                $sanitizedNodeData = $this->nodeValidator->sanitize($nodeData);

                $treeId = $sanitizedNodeData['tree_id'] ?? $tree->getId();
                if ($treeId === null) {
                    throw InvalidTreeOperationException::treeIdRequired();
                }
                $node = $this->nodeFactory->createFromData($sanitizedNodeData, $treeId);
                $this->unitOfWork->registerNew($node);
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
                throw new TreeNodeNotFoundException($nodeId);
            }

            // Update the node's parent and register it as dirty
            $node = $this->nodeFactory->createWithNewParent($node, $newParentId);
            $this->unitOfWork->registerDirty($node);

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
            // Find and register all nodes for deletion
            $nodes = $this->nodeRepository->findByTreeId($treeId);
            foreach ($nodes as $node) {
                $this->unitOfWork->registerDeleted($node);
            }

            // Find and register the tree for deletion
            $tree = $this->treeRepository->findById($treeId);
            if ($tree) {
                $this->unitOfWork->registerDeleted($tree);
            }

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
            throw new TreeNotFoundException($treeId);
        }

        return $this->nodeRepository->findTreeStructure($treeId);
    }
}
