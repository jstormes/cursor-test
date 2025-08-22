<?php

declare(strict_types=1);

namespace App\Domain\Tree;

final class InvalidTreeOperationException extends TreeException
{
    public static function treeIdRequired(): self
    {
        return new self('Tree ID is required for node creation');
    }

    public static function unknownNodeType(string $type): self
    {
        return new self("Unknown node type: {$type}");
    }
}
