<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Factory;

use App\Infrastructure\Factory\DefaultTreeNodeFactory;
use App\Domain\Tree\TreeNodeFactory;
use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\InvalidTreeOperationException;
use Tests\TestCase;

class DefaultTreeNodeFactoryTest extends TestCase
{
    private DefaultTreeNodeFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DefaultTreeNodeFactory();
    }

    public function testImplementsTreeNodeFactory(): void
    {
        $this->assertInstanceOf(TreeNodeFactory::class, $this->factory);
    }

    public function testCreateFromDataCreatesSimpleNode(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Test Simple Node',
            'parent_id' => null,
            'sort_order' => 0
        ];
        $treeId = 1;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertInstanceOf(AbstractTreeNode::class, $node);
        $this->assertEquals('Test Simple Node', $node->getName());
        $this->assertEquals($treeId, $node->getTreeId());
        $this->assertNull($node->getParentId());
        $this->assertEquals(0, $node->getSortOrder());
        $this->assertNull($node->getId()); // New node should have null ID
    }

    public function testCreateFromDataCreatesSimpleNodeWithDefaultType(): void
    {
        $nodeData = [
            'name' => 'Default Type Node'
        ];
        $treeId = 2;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertEquals('Default Type Node', $node->getName());
        $this->assertEquals($treeId, $node->getTreeId());
        $this->assertNull($node->getParentId());
        $this->assertEquals(0, $node->getSortOrder());
    }

    public function testCreateFromDataCreatesSimpleNodeWithParent(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Child Node',
            'parent_id' => 5,
            'sort_order' => 3
        ];
        $treeId = 10;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertEquals('Child Node', $node->getName());
        $this->assertEquals($treeId, $node->getTreeId());
        $this->assertEquals(5, $node->getParentId());
        $this->assertEquals(3, $node->getSortOrder());
    }

    public function testCreateFromDataCreatesButtonNode(): void
    {
        $nodeData = [
            'type' => 'ButtonNode',
            'name' => 'Test Button',
            'parent_id' => 2,
            'sort_order' => 1,
            'type_data' => [
                'button_text' => 'Click Me',
                'button_action' => 'https://example.com'
            ]
        ];
        $treeId = 3;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(ButtonNode::class, $node);
        $this->assertInstanceOf(AbstractTreeNode::class, $node);
        $this->assertEquals('Test Button', $node->getName());
        $this->assertEquals($treeId, $node->getTreeId());
        $this->assertEquals(2, $node->getParentId());
        $this->assertEquals(1, $node->getSortOrder());
        
        $typeData = $node->getTypeData();
        $this->assertEquals('Click Me', $typeData['button_text']);
        $this->assertEquals('https://example.com', $typeData['button_action']);
    }

    public function testCreateFromDataCreatesButtonNodeWithEmptyTypeData(): void
    {
        $nodeData = [
            'type' => 'ButtonNode',
            'name' => 'Button Without Data'
        ];
        $treeId = 4;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(ButtonNode::class, $node);
        $this->assertEquals('Button Without Data', $node->getName());
        $this->assertEquals($treeId, $node->getTreeId());
        
        // ButtonNode uses default values when no type_data is provided
        $expectedTypeData = [
            'button_text' => 'Test Btn',
            'button_action' => ''
        ];
        $this->assertEquals($expectedTypeData, $node->getTypeData());
    }

    public function testCreateFromDataThrowsExceptionForUnknownType(): void
    {
        $nodeData = [
            'type' => 'UnknownNodeType',
            'name' => 'Unknown Node'
        ];
        $treeId = 5;

        $this->expectException(InvalidTreeOperationException::class);
        $this->expectExceptionMessage('Unknown node type: UnknownNodeType');

        $this->factory->createFromData($nodeData, $treeId);
    }

    public function testCreateFromDataRequiresName(): void
    {
        $nodeData = [
            'type' => 'SimpleNode'
        ];
        $treeId = 6;

        // PHP 8+ will show a warning but not throw TypeError
        // The actual error is "Undefined array key"
        $this->expectWarning();
        $this->expectWarningMessage('Undefined array key "name"');
        
        $this->factory->createFromData($nodeData, $treeId);
    }

    public function testCreateFromDataHandlesStringParentId(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Test Node',
            'parent_id' => '10' // String instead of int
        ];
        $treeId = 7;

        // This will throw TypeError because AbstractTreeNode expects int
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument #4 ($parentId) must be of type ?int');

        $this->factory->createFromData($nodeData, $treeId);
    }

    public function testCreateFromDataHandlesStringSortOrder(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Test Node',
            'sort_order' => '5' // String instead of int
        ];
        $treeId = 8;

        // This will throw TypeError because AbstractTreeNode expects int
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument #5 ($sortOrder) must be of type int');

        $this->factory->createFromData($nodeData, $treeId);
    }

    public function testCreateWithNewParentSimpleNode(): void
    {
        $originalNode = new SimpleNode(10, 'Original Node', 1, 5, 2);
        $newParentId = 15;

        $newNode = $this->factory->createWithNewParent($originalNode, $newParentId);

        $this->assertInstanceOf(SimpleNode::class, $newNode);
        $this->assertEquals($originalNode->getId(), $newNode->getId());
        $this->assertEquals($originalNode->getName(), $newNode->getName());
        $this->assertEquals($originalNode->getTreeId(), $newNode->getTreeId());
        $this->assertEquals($newParentId, $newNode->getParentId());
        $this->assertEquals($originalNode->getSortOrder(), $newNode->getSortOrder());
        
        // Verify original node is unchanged
        $this->assertEquals(5, $originalNode->getParentId());
    }

    public function testCreateWithNewParentButtonNode(): void
    {
        $typeData = ['button_text' => 'Test Button', 'button_action' => '/test'];
        $originalNode = new ButtonNode(20, 'Button Node', 2, 10, 3, $typeData);
        $newParentId = 25;

        $newNode = $this->factory->createWithNewParent($originalNode, $newParentId);

        $this->assertInstanceOf(ButtonNode::class, $newNode);
        $this->assertEquals($originalNode->getId(), $newNode->getId());
        $this->assertEquals($originalNode->getName(), $newNode->getName());
        $this->assertEquals($originalNode->getTreeId(), $newNode->getTreeId());
        $this->assertEquals($newParentId, $newNode->getParentId());
        $this->assertEquals($originalNode->getSortOrder(), $newNode->getSortOrder());
        $this->assertEquals($typeData, $newNode->getTypeData());
        
        // Verify original node is unchanged
        $this->assertEquals(10, $originalNode->getParentId());
    }

    public function testCreateWithNewParentThrowsExceptionForUnknownType(): void
    {
        // Create a mock node with unknown type
        $unknownNode = $this->createMock(AbstractTreeNode::class);
        $unknownNode->method('getType')->willReturn('UnknownType');
        $unknownNode->method('getId')->willReturn(1);
        $unknownNode->method('getName')->willReturn('Unknown');
        $unknownNode->method('getTreeId')->willReturn(1);
        $unknownNode->method('getSortOrder')->willReturn(0);

        $this->expectException(InvalidTreeOperationException::class);
        $this->expectExceptionMessage('Unknown node type: UnknownType');

        $this->factory->createWithNewParent($unknownNode, 10);
    }

    public function testCreateWithNewParentPreservesAllProperties(): void
    {
        $originalNode = new SimpleNode(100, 'Complex Node Name', 50, 75, 99);
        $newParentId = 200;

        $newNode = $this->factory->createWithNewParent($originalNode, $newParentId);

        // Test that all properties except parent_id are preserved exactly
        $this->assertEquals(100, $newNode->getId());
        $this->assertEquals('Complex Node Name', $newNode->getName());
        $this->assertEquals(50, $newNode->getTreeId());
        $this->assertEquals(99, $newNode->getSortOrder());
        $this->assertEquals($newParentId, $newNode->getParentId());
    }

    public function testCreateWithNewParentButtonNodePreservesTypeData(): void
    {
        $complexTypeData = [
            'button_text' => 'Complex Button Text',
            'button_action' => 'https://complex-url.com/path?param=value'
        ];
        
        $originalNode = new ButtonNode(30, 'Complex Button', 3, 15, 7, $complexTypeData);
        $newParentId = 45;

        $newNode = $this->factory->createWithNewParent($originalNode, $newParentId);

        $this->assertEquals($complexTypeData, $newNode->getTypeData());
        $this->assertEquals('Complex Button Text', $newNode->getTypeData()['button_text']);
        $this->assertEquals('https://complex-url.com/path?param=value', $newNode->getTypeData()['button_action']);
    }

    public function testFactoryIsStateless(): void
    {
        // Creating nodes should not affect each other
        $nodeData1 = ['type' => 'SimpleNode', 'name' => 'Node 1'];
        $nodeData2 = ['type' => 'ButtonNode', 'name' => 'Node 2', 'type_data' => ['button_text' => 'Test', 'button_action' => '/test']];

        $node1 = $this->factory->createFromData($nodeData1, 1);
        $node2 = $this->factory->createFromData($nodeData2, 2);

        $this->assertInstanceOf(SimpleNode::class, $node1);
        $this->assertInstanceOf(ButtonNode::class, $node2);
        $this->assertEquals('Node 1', $node1->getName());
        $this->assertEquals('Node 2', $node2->getName());
        $this->assertEquals(1, $node1->getTreeId());
        $this->assertEquals(2, $node2->getTreeId());
    }

    public function testCreateFromDataWithComplexTypeData(): void
    {
        // ButtonNode only uses button_text and button_action
        $complexTypeData = [
            'button_text' => 'Complex Modal Button',
            'button_action' => '/complex/path#myModal'
        ];

        $nodeData = [
            'type' => 'ButtonNode',
            'name' => 'Complex Button',
            'type_data' => $complexTypeData
        ];

        $node = $this->factory->createFromData($nodeData, 10);

        $this->assertInstanceOf(ButtonNode::class, $node);
        $typeData = $node->getTypeData();
        $this->assertEquals('Complex Modal Button', $typeData['button_text']);
        $this->assertEquals('/complex/path#myModal', $typeData['button_action']);
    }

    public function testCreateFromDataHandlesNullValues(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Null Parent Node',
            'parent_id' => null,
            'sort_order' => null
        ];
        $treeId = 15;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertNull($node->getParentId());
        // sort_order defaults to 0 when null is passed
        $this->assertEquals(0, $node->getSortOrder());
    }

    public function testCreateFromDataWithZeroValues(): void
    {
        $nodeData = [
            'type' => 'SimpleNode',
            'name' => 'Zero Values Node',
            'parent_id' => 0,
            'sort_order' => 0
        ];
        $treeId = 20;

        $node = $this->factory->createFromData($nodeData, $treeId);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertEquals(0, $node->getParentId());
        $this->assertEquals(0, $node->getSortOrder());
    }

    public function testFactoryClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(DefaultTreeNodeFactory::class);
        $this->assertTrue($reflection->isFinal(), 'DefaultTreeNodeFactory should be a final class');
    }

    public function testNodeTypeMatchExpressionHandlesCaseExactly(): void
    {
        // Test that the match expression is case-sensitive
        $nodeData = [
            'type' => 'simplenode', // lowercase
            'name' => 'Test Node'
        ];
        $treeId = 25;

        $this->expectException(InvalidTreeOperationException::class);
        $this->expectExceptionMessage('Unknown node type: simplenode');

        $this->factory->createFromData($nodeData, $treeId);
    }

    public function testCreateFromDataPreservesDataIntegrity(): void
    {
        $nodeData = [
            'type' => 'ButtonNode',
            'name' => 'Data Integrity Test',
            'parent_id' => 123,
            'sort_order' => 456,
            'type_data' => [
                'button_text' => 'Important Button',
                'button_action' => 'preserved_action'
            ]
        ];
        $treeId = 30;

        $node = $this->factory->createFromData($nodeData, $treeId);

        // Verify no data corruption or loss
        $this->assertEquals('Data Integrity Test', $node->getName());
        $this->assertEquals(123, $node->getParentId());
        $this->assertEquals(456, $node->getSortOrder());
        $this->assertEquals($treeId, $node->getTreeId());
        $this->assertEquals('Important Button', $node->getTypeData()['button_text']);
        $this->assertEquals('preserved_action', $node->getTypeData()['button_action']);
    }
}