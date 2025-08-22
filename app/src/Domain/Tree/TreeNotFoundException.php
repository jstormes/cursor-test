<?php

declare(strict_types=1);

namespace App\Domain\Tree;

final class TreeNotFoundException extends TreeException
{
    public function __construct(int $treeId)
    {
        parent::__construct("Tree with ID {$treeId} not found");
    }
}
