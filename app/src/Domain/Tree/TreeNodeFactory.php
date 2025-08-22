<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeNodeFactory
{
    /**
     * @param array<string, mixed> $nodeData
     */
    public function createFromData(array $nodeData, int $treeId): AbstractTreeNode;

    public function createWithNewParent(AbstractTreeNode $node, int $newParentId): AbstractTreeNode;
}
