<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\TreeNodeVisitor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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

    public function testConstructorWithNullId(): void
    {
        $node = new ButtonNode(null, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertNull($node->getId());
        $this->assertEquals('Test Node', $node->getName());
        $this->assertEquals('Click Me', $node->getButtonText());
    }

    public function testConstructorWithZeroId(): void
    {
        $node = new ButtonNode(0, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertEquals(0, $node->getId());
        $this->assertEquals('Test Node', $node->getName());
    }

    public function testConstructorWithEmptyName(): void
    {
        $node = new ButtonNode(1, '', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertEquals('', $node->getName());
        $this->assertEquals('Click Me', $node->getButtonText());
    }

    public function testConstructorWithEmptyTypeData(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, []);
        
        $this->assertEquals('Test Btn', $node->getButtonText());
        $this->assertEquals('', $node->getButtonAction());
    }

    public function testConstructorWithPartialTypeData(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Custom Text'
        ]);
        
        $this->assertEquals('Custom Text', $node->getButtonText());
        $this->assertEquals('', $node->getButtonAction());
    }

    public function testConstructorWithOnlyButtonAction(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_action' => 'customAction()'
        ]);
        
        $this->assertEquals('Test Btn', $node->getButtonText());
        $this->assertEquals('customAction()', $node->getButtonAction());
    }

    public function testConstructorWithNullParentId(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertNull($node->getParentId());
        $this->assertEquals(1, $node->getTreeId());
        $this->assertEquals(0, $node->getSortOrder());
    }

    public function testConstructorWithParentId(): void
    {
        $node = new ButtonNode(2, 'Child Node', 1, 1, 1, [
            'button_text' => 'Child Button',
            'button_action' => 'childAction()'
        ]);
        
        $this->assertEquals(1, $node->getParentId());
        $this->assertEquals(1, $node->getTreeId());
        $this->assertEquals(1, $node->getSortOrder());
    }

    public function testGetType(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $this->assertEquals('ButtonNode', $node->getType());
    }

    public function testGetButtonText(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Custom Button Text'
        ]);
        
        $this->assertEquals('Custom Button Text', $node->getButtonText());
    }

    public function testGetButtonAction(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_action' => 'customFunction()'
        ]);
        
        $this->assertEquals('customFunction()', $node->getButtonAction());
    }

    public function testSetButtonText(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Original Text'
        ]);
        
        $node->setButtonText('New Button Text');
        
        $this->assertEquals('New Button Text', $node->getButtonText());
    }

    public function testSetButtonTextWithEmptyString(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Original Text'
        ]);
        
        $node->setButtonText('');
        
        $this->assertEquals('', $node->getButtonText());
    }

    public function testSetButtonAction(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_action' => 'originalAction()'
        ]);
        
        $node->setButtonAction('newAction()');
        
        $this->assertEquals('newAction()', $node->getButtonAction());
    }

    public function testSetButtonActionWithEmptyString(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_action' => 'originalAction()'
        ]);
        
        $node->setButtonAction('');
        
        $this->assertEquals('', $node->getButtonAction());
    }

    public function testGetTypeData(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $typeData = $node->getTypeData();
        
        $expected = [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")',
        ];
        
        $this->assertEquals($expected, $typeData);
    }

    public function testGetTypeDataWithDefaults(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $typeData = $node->getTypeData();
        
        $expected = [
            'button_text' => 'Test Btn',
            'button_action' => '',
        ];
        
        $this->assertEquals($expected, $typeData);
    }

    public function testGetTypeDataAfterSetters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $node->setButtonText('Updated Text');
        $node->setButtonAction('updatedAction()');
        
        $typeData = $node->getTypeData();
        
        $expected = [
            'button_text' => 'Updated Text',
            'button_action' => 'updatedAction()',
        ];
        
        $this->assertEquals($expected, $typeData);
    }

    public function testAccept(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $visitor = $this->createMock(TreeNodeVisitor::class);
        $visitor->expects($this->once())
            ->method('visitButtonNode')
            ->with($node)
            ->willReturn('ButtonNode visited');
        
        $result = $node->accept($visitor);
        
        $this->assertEquals('ButtonNode visited', $result);
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

    public function testConstructorWithSpecialCharacters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click "Me" & <Test>',
            'button_action' => 'alert("Hello & World")'
        ]);
        
        $this->assertEquals('Click "Me" & <Test>', $node->getButtonText());
        $this->assertEquals('alert("Hello & World")', $node->getButtonAction());
    }

    public function testConstructorWithUnicodeCharacters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click émoji 🎯',
            'button_action' => 'customAction("émoji")'
        ]);
        
        $this->assertEquals('Click émoji 🎯', $node->getButtonText());
        $this->assertEquals('customAction("émoji")', $node->getButtonAction());
    }

    public function testConstructorWithLongText(): void
    {
        $longText = str_repeat('A', 1000);
        $longAction = str_repeat('action();', 100);
        
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => $longText,
            'button_action' => $longAction
        ]);
        
        $this->assertEquals($longText, $node->getButtonText());
        $this->assertEquals($longAction, $node->getButtonAction());
    }

    public function testSetButtonTextWithSpecialCharacters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $node->setButtonText('New "Button" & <Text>');
        
        $this->assertEquals('New "Button" & <Text>', $node->getButtonText());
    }

    public function testSetButtonActionWithSpecialCharacters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $node->setButtonAction('customAction("test" & "value")');
        
        $this->assertEquals('customAction("test" & "value")', $node->getButtonAction());
    }

    public function testInheritanceFromAbstractTreeNode(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        // Test inherited methods
        $this->assertEquals(1, $node->getId());
        $this->assertEquals('Test Node', $node->getName());
        $this->assertEquals(1, $node->getTreeId());
        $this->assertNull($node->getParentId());
        $this->assertEquals(0, $node->getSortOrder());
        $this->assertFalse($node->hasChildren());
        $this->assertEmpty($node->getChildren());
    }

    public function testMultipleSetters(): void
    {
        $node = new ButtonNode(1, 'Test Node', 1);
        
        $node->setButtonText('First Text');
        $node->setButtonAction('firstAction()');
        
        $this->assertEquals('First Text', $node->getButtonText());
        $this->assertEquals('firstAction()', $node->getButtonAction());
        
        $node->setButtonText('Second Text');
        $node->setButtonAction('secondAction()');
        
        $this->assertEquals('Second Text', $node->getButtonText());
        $this->assertEquals('secondAction()', $node->getButtonAction());
    }
} 