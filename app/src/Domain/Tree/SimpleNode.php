<?php

declare(strict_types=1);

namespace App\Domain\Tree;

class SimpleNode extends AbstractTreeNode
{
    #[\Override]
    public function getType(): string
    {
        return 'SimpleNode';
    }

    #[\Override]
    public function accept(TreeNodeVisitor $visitor): string
    {
        return $visitor->visitSimpleNode($this);
    }

    #[\Override]
    public function getTypeData(): array
    {
        return [];
    }
}
