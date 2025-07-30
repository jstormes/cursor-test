<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\SimpleNode;
use App\Infrastructure\Database\TreeNodeDataMapper;
use Tests\TestCase;

class TreeNodeDataMapperTest extends TestCase
{
    private TreeNodeDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new TreeNodeDataMapper();
    }

    public function testMapToEntitySimpleNode(): void
    {
        $data = [
            'id' => 1,
            'tree_id' => 1,
            'parent_id' => null,
            'name' => 'Root Node',
            'sort_order' => 0,
            'type_class' => 'SimpleNode',
            'type_data' => '{}'
        ];

        $node = $this->mapper->mapToEntity($data);

        $this->assertInstanceOf(SimpleNode::class, $node);
        $this->assertEquals(1, $node->getId());
        $this->assertEquals('Root Node', $node->getName());
        $this->assertEquals(1, $node->getTreeId());
        $this->assertNull($node->getParentId());
        $this->assertEquals(0, $node->getSortOrder());
        $this->assertEquals('SimpleNode', $node->getType());
    }

    public function testMapToEntityButtonNode(): void
    {
        $data = [
            'id' => 2,
            'tree_id' => 1,
            'parent_id' => 1,
            'name' => 'Button Node',
            'sort_order' => 1,
            'type_class' => 'ButtonNode',
            'type_data' => '{"button_text": "Click Me", "button_action": "doSomething()"}'
        ];

        $node = $this->mapper->mapToEntity($data);

        $this->assertInstanceOf(ButtonNode::class, $node);
        $this->assertEquals(2, $node->getId());
        $this->assertEquals('Button Node', $node->getName());
        $this->assertEquals(1, $node->getTreeId());
        $this->assertEquals(1, $node->getParentId());
        $this->assertEquals(1, $node->getSortOrder());
        $this->assertEquals('ButtonNode', $node->getType());
        $this->assertEquals('Click Me', $node->getButtonText());
        $this->assertEquals('doSomething()', $node->getButtonAction());
    }

    public function testMapToEntityWithInvalidType(): void
    {
        $data = [
            'id' => 1,
            'tree_id' => 1,
            'parent_id' => null,
            'name' => 'Invalid Node',
            'sort_order' => 0,
            'type_class' => 'InvalidType',
            'type_data' => '{}'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown node type: InvalidType');

        $this->mapper->mapToEntity($data);
    }

    public function testMapToArraySimpleNode(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, null, 0);

        $data = $this->mapper->mapToArray($node);

        $this->assertEquals([
            'id' => 1,
            'tree_id' => 1,
            'parent_id' => null,
            'name' => 'Test Node',
            'sort_order' => 0,
            'type_class' => 'SimpleNode',
            'type_data' => '[]'
        ], $data);
    }

    public function testMapToArrayButtonNode(): void
    {
        $node = new ButtonNode(2, 'Button Node', 1, 1, 1, [
            'button_text' => 'Click Me',
            'button_action' => 'doSomething()'
        ]);

        $data = $this->mapper->mapToArray($node);

        $this->assertEquals([
            'id' => 2,
            'tree_id' => 1,
            'parent_id' => 1,
            'name' => 'Button Node',
            'sort_order' => 1,
            'type_class' => 'ButtonNode',
            'type_data' => '{"button_text":"Click Me","button_action":"doSomething()"}'
        ], $data);
    }

    public function testMapToArrayWithInvalidEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of AbstractTreeNode');

        $this->mapper->mapToArray(new \stdClass());
    }

    public function testMapToEntities(): void
    {
        $data = [
            [
                'id' => 1,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'Root',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ],
            [
                'id' => 2,
                'tree_id' => 1,
                'parent_id' => 1,
                'name' => 'Child',
                'sort_order' => 0,
                'type_class' => 'ButtonNode',
                'type_data' => '{"button_text": "Click", "button_action": "action()"}'
            ]
        ];

        $nodes = $this->mapper->mapToEntities($data);

        $this->assertCount(2, $nodes);
        $this->assertInstanceOf(SimpleNode::class, $nodes[0]);
        $this->assertInstanceOf(ButtonNode::class, $nodes[1]);
        $this->assertEquals('Root', $nodes[0]->getName());
        $this->assertEquals('Child', $nodes[1]->getName());
    }
} 