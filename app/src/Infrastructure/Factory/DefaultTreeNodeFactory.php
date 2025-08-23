<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\InvalidTreeOperationException;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\TreeNodeFactory;

final class DefaultTreeNodeFactory implements TreeNodeFactory
{
    #[\Override]
    public function createFromData(array $nodeData, int $treeId): AbstractTreeNode
    {
        // Validate required field
        if (!isset($nodeData['name'])) {
            throw new \InvalidArgumentException('Missing required field: name');
        }

        $type = $nodeData['type'] ?? 'SimpleNode';
        $name = $nodeData['name'];
        $parentId = $nodeData['parent_id'] ?? null;
        $sortOrder = $nodeData['sort_order'] ?? 0;
        $typeData = $nodeData['type_data'] ?? [];

        return match ($type) {
            'SimpleNode' => new SimpleNode(
                null,
                $name,
                $treeId,
                $parentId,
                $sortOrder
            ),
            'ButtonNode' => new ButtonNode(
                null,
                $name,
                $treeId,
                $parentId,
                $sortOrder,
                $typeData
            ),
            default => throw InvalidTreeOperationException::unknownNodeType($type)
        };
    }

    #[\Override]
    public function createWithNewParent(AbstractTreeNode $node, int $newParentId): AbstractTreeNode
    {
        return match ($node->getType()) {
            'SimpleNode' => new SimpleNode(
                $node->getId(),
                $node->getName(),
                $node->getTreeId(),
                $newParentId,
                $node->getSortOrder()
            ),
            'ButtonNode' => new ButtonNode(
                $node->getId(),
                $node->getName(),
                $node->getTreeId(),
                $newParentId,
                $node->getSortOrder(),
                $node->getTypeData()
            ),
            default => throw InvalidTreeOperationException::unknownNodeType($node->getType())
        };
    }
}
