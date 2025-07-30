<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\SimpleNode;
use PHPUnit\Framework\TestCase;

class SimpleNodeTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $node = new SimpleNode('Test Node');
        
        $this->assertEquals('Test Node', $node->getName());
        $this->assertFalse($node->hasChildren());
        $this->assertEmpty($node->getChildren());
        $this->assertEquals('simple', $node->getType());
    }

    public function testAddChild(): void
    {
        $parent = new SimpleNode('Parent');
        $child = new SimpleNode('Child');
        
        $parent->addChild($child);
        
        $this->assertTrue($parent->hasChildren());
        $this->assertCount(1, $parent->getChildren());
        $this->assertSame($child, $parent->getChildren()[0]);
    }

    public function testAddMultipleChildren(): void
    {
        $parent = new SimpleNode('Parent');
        $child1 = new SimpleNode('Child 1');
        $child2 = new SimpleNode('Child 2');
        
        $parent->addChild($child1);
        $parent->addChild($child2);
        
        $this->assertTrue($parent->hasChildren());
        $this->assertCount(2, $parent->getChildren());
        $this->assertSame($child1, $parent->getChildren()[0]);
        $this->assertSame($child2, $parent->getChildren()[1]);
    }

    public function testEmptyChildrenArray(): void
    {
        $node = new SimpleNode('Test');
        
        $this->assertEmpty($node->getChildren());
        $this->assertFalse($node->hasChildren());
    }

    public function testHasChildrenAfterAdding(): void
    {
        $parent = new SimpleNode('Parent');
        $child = new SimpleNode('Child');
        
        $this->assertFalse($parent->hasChildren());
        
        $parent->addChild($child);
        $this->assertTrue($parent->hasChildren());
        
        $this->assertCount(1, $parent->getChildren());
    }
} 