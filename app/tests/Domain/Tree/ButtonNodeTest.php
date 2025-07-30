<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\ButtonNode;
use PHPUnit\Framework\TestCase;

class ButtonNodeTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertEquals('Test Node', $node->getName());
        $this->assertEquals('ButtonNode', $node->getType());
        $this->assertEquals('Click Me', $node->getButtonText());
        $this->assertEquals('alert("test")', $node->getButtonAction());
        $this->assertFalse($node->hasChildren());
        $this->assertEmpty($node->getChildren());
    }

    public function testConstructorWithDefaults(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $this->assertEquals('Test Node', $node->getName());
        $this->assertEquals('ButtonNode', $node->getType());
        $this->assertEquals('Test Btn', $node->getButtonText());
        $this->assertEquals('', $node->getButtonAction());
    }

    public function testAddChild(): void
    {
        $parent = new ButtonNode(1, 'Parent', 1, null, 0, ['button_text' => 'Parent Button']);
        $child = new ButtonNode(2, 'Child', 1, 1, 0, ['button_text' => 'Child Button']);
        
        $parent->addChild($child);
        
        $this->assertTrue($parent->hasChildren());
        $this->assertCount(1, $parent->getChildren());
        $this->assertSame($child, $parent->getChildren()[0]);
    }

    public function testAddMultipleChildren(): void
    {
        $parent = new ButtonNode(1, 'Parent', 1, null, 0, ['button_text' => 'Parent Button']);
        $child1 = new ButtonNode(2, 'Child 1', 1, 1, 0, ['button_text' => 'Button 1']);
        $child2 = new ButtonNode(3, 'Child 2', 1, 1, 1, ['button_text' => 'Button 2']);
        
        $parent->addChild($child1);
        $parent->addChild($child2);
        
        $this->assertTrue($parent->hasChildren());
        $this->assertCount(2, $parent->getChildren());
        $this->assertSame($child1, $parent->getChildren()[0]);
        $this->assertSame($child2, $parent->getChildren()[1]);
    }

    public function testEmptyChildrenArray(): void
    {
        $node = new ButtonNode(1, 'Test', 1, null, 0, ['button_text' => 'Test Button']);
        
        $this->assertEmpty($node->getChildren());
        $this->assertFalse($node->hasChildren());
    }

    public function testHasChildrenAfterAdding(): void
    {
        $parent = new ButtonNode(1, 'Parent', 1, null, 0, ['button_text' => 'Parent Button']);
        $child = new ButtonNode(2, 'Child', 1, 1, 0, ['button_text' => 'Child Button']);
        
        $this->assertFalse($parent->hasChildren());
        
        $parent->addChild($child);
        $this->assertTrue($parent->hasChildren());
        
        $this->assertCount(1, $parent->getChildren());
    }
} 