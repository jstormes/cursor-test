<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\SimpleNode;
use PHPUnit\Framework\TestCase;

class SimpleNodeTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1);

        $this->assertEquals('Test Node', $node->getName());
        $this->assertEquals('SimpleNode', $node->getType());
        $this->assertFalse($node->hasChildren());
        $this->assertEmpty($node->getChildren());
    }

    public function testAddChild(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child = new SimpleNode(2, 'Child', 1, 1);

        $parent->addChild($child);

        $this->assertTrue($parent->hasChildren());
        $this->assertCount(1, $parent->getChildren());
        $this->assertSame($child, $parent->getChildren()[0]);
    }

    public function testAddMultipleChildren(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child 2', 1, 1, 1);

        $parent->addChild($child1);
        $parent->addChild($child2);

        $this->assertTrue($parent->hasChildren());
        $this->assertCount(2, $parent->getChildren());
        $this->assertSame($child1, $parent->getChildren()[0]);
        $this->assertSame($child2, $parent->getChildren()[1]);
    }

    public function testEmptyChildrenArray(): void
    {
        $node = new SimpleNode(1, 'Test', 1);

        $this->assertEmpty($node->getChildren());
        $this->assertFalse($node->hasChildren());
    }

    public function testHasChildrenAfterAdding(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child = new SimpleNode(2, 'Child', 1, 1);

        $this->assertFalse($parent->hasChildren());

        $parent->addChild($child);
        $this->assertTrue($parent->hasChildren());

        $this->assertCount(1, $parent->getChildren());
    }
}
