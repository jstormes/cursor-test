<?php

declare(strict_types=1);

namespace App\Domain\Tree;

final class TreeNodeNotFoundException extends TreeException
{
    public function __construct(int $nodeId)
    {
        parent::__construct("Tree node with ID {$nodeId} not found");
    }
}
