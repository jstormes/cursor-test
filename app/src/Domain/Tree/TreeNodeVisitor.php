<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeNodeVisitor
{
    public function visitSimpleNode(SimpleNode $node): string;
    public function visitButtonNode(ButtonNode $node): string;
} 