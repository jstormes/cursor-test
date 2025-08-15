<?php

declare(strict_types=1);

namespace App\Tests\Application\Services;

use App\Application\Services\TreeService;
use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\UnitOfWork;
use Tests\TestCase;

class TreeServiceTest extends TestCase
{
    private TreeService $service;
    private TreeRepository $mockTreeRepository;
    private TreeNodeRepository $mockNodeRepository;
    private UnitOfWork $mockUnitOfWork;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTreeRepository = $this->createMock(TreeRepository::class);
        $this->mockNodeRepository = $this->createMock(TreeNodeRepository::class);
        $this->mockUnitOfWork = $this->createMock(UnitOfWork::class);

        $this->service = new TreeService(
            $this->mockTreeRepository,
            $this->mockNodeRepository,
            $this->mockUnitOfWork
        );
    }

    public function testCreateTreeWithNodes(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ],
            [
                'type' => 'ButtonNode',
                'name' => 'Button',
                'parent_id' => null,
                'sort_order' => 1,
                'tree_id' => 1,
                'type_data' => [
                    'button_text' => 'Click Me',
                    'button_action' => 'doSomething()'
                ]
            ]
        ];

        $expectedTree = new Tree(null, 'Test Tree', 'A test tree');

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Tree $tree) {
                return $tree->getName() === 'Test Tree' && $tree->getDescription() === 'A test tree';
            }));

        $this->mockNodeRepository->expects($this->exactly(2))
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node instanceof SimpleNode || $node instanceof ButtonNode;
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Test Tree', $result->getName());
    }

    public function testCreateTreeWithNodesRollbackOnException(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);
    }

    public function testCreateTreeWithNodesWithNullDescription(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Tree $tree) {
                return $tree->getName() === 'Test Tree' && $tree->getDescription() === null;
            }));

        $this->mockNodeRepository->expects($this->once())
            ->method('save');

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Test Tree', null, $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Test Tree', $result->getName());
    }

    public function testCreateTreeWithNodesWithEmptyNodesArray(): void
    {
        $nodes = [];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save');

        $this->mockNodeRepository->expects($this->never())
            ->method('save');

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Test Tree', $result->getName());
    }

    public function testCreateTreeWithNodesWithMissingTreeId(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0
                // Missing tree_id
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save');

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tree ID is required for node creation');

        $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);
    }

    public function testCreateTreeWithNodesWithNullTreeIdAfterSave(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0
                // Missing tree_id
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Tree $tree) {
                // Simulate tree still having null ID after save
                return $tree;
            });

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tree ID is required for node creation');

        $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);
    }

    public function testCreateTreeWithNodesWithUnknownNodeType(): void
    {
        $nodes = [
            [
                'type' => 'UnknownNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save');

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown node type: UnknownNode');

        $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);
    }

    public function testCreateTreeWithNodesWithNodeException(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save');

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Node save error'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Node save error');

        $this->service->createTreeWithNodes('Test Tree', 'A test tree', $nodes);
    }

    public function testMoveNode(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 1, 0);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node->getParentId() === 2;
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 2);
    }

    public function testMoveNodeNotFound(): void
    {
        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Node with ID 999 not found');

        $this->service->moveNode(999, 2);
    }

    public function testMoveButtonNode(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, 1, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node->getParentId() === 3 && $node instanceof ButtonNode;
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 3);
    }

    public function testMoveNodeWithException(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 1, 0);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Save error'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Save error');

        $this->service->moveNode(1, 2);
    }

    public function testMoveNodeWithUnknownType(): void
    {
        // Create a mock node that returns an unknown type
        $node = $this->createMock(AbstractTreeNode::class);
        $node->method('getId')->willReturn(1);
        $node->method('getName')->willReturn('Test Node');
        $node->method('getTreeId')->willReturn(1);
        $node->method('getParentId')->willReturn(1);
        $node->method('getSortOrder')->willReturn(0);
        $node->method('getType')->willReturn('UnknownType');
        $node->method('getTypeData')->willReturn([]);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown node type: UnknownType');

        $this->service->moveNode(1, 2);
    }

    public function testDeleteTreeWithNodes(): void
    {
        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('deleteByTreeId')
            ->with(1);

        $this->mockTreeRepository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->deleteTreeWithNodes(1);
    }

    public function testDeleteTreeWithNodesWithException(): void
    {
        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('deleteByTreeId')
            ->with(1)
            ->willThrowException(new \Exception('Delete error'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delete error');

        $this->service->deleteTreeWithNodes(1);
    }

    public function testDeleteTreeWithNodesWithTreeDeleteException(): void
    {
        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('deleteByTreeId')
            ->with(1);

        $this->mockTreeRepository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willThrowException(new \Exception('Tree delete error'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tree delete error');

        $this->service->deleteTreeWithNodes(1);
    }

    public function testGetTreeStructure(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree');
        $nodes = [
            new SimpleNode(1, 'Root', 1, null, 0),
            new ButtonNode(2, 'Button', 1, 1, 0, [
                'button_text' => 'Click',
                'button_action' => 'action()'
            ])
        ];

        $this->mockTreeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->mockNodeRepository->expects($this->once())
            ->method('findTreeStructure')
            ->with(1)
            ->willReturn($nodes);

        $result = $this->service->getTreeStructure(1);

        $this->assertEquals($nodes, $result);
    }

    public function testGetTreeStructureTreeNotFound(): void
    {
        $this->mockTreeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tree with ID 999 not found');

        $this->service->getTreeStructure(999);
    }

    public function testGetTreeStructureWithEmptyNodes(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree');

        $this->mockTreeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->mockNodeRepository->expects($this->once())
            ->method('findTreeStructure')
            ->with(1)
            ->willReturn([]);

        $result = $this->service->getTreeStructure(1);

        $this->assertEquals([], $result);
    }

    public function testCreateTreeWithNodesWithComplexNodeData(): void
    {
        $nodes = [
            [
                'type' => 'SimpleNode',
                'name' => 'Root Node',
                'parent_id' => null,
                'sort_order' => 0,
                'tree_id' => 1
            ],
            [
                'type' => 'ButtonNode',
                'name' => 'Action Button',
                'parent_id' => null,
                'sort_order' => 1,
                'tree_id' => 1,
                'type_data' => [
                    'button_text' => 'Click "Me" & <Test>',
                    'button_action' => 'alert("Hello & World")'
                ]
            ],
            [
                'type' => 'ButtonNode',
                'name' => 'Unicode Button',
                'parent_id' => null,
                'sort_order' => 2,
                'tree_id' => 1,
                'type_data' => [
                    'button_text' => 'Click émoji 🎯',
                    'button_action' => 'customAction("émoji")'
                ]
            ]
        ];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockTreeRepository->expects($this->once())
            ->method('save');

        $this->mockNodeRepository->expects($this->exactly(3))
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node instanceof SimpleNode || $node instanceof ButtonNode;
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Complex Tree', 'A complex tree', $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Complex Tree', $result->getName());
    }

    public function testMoveNodeToSameParent(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 2, 0);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node->getParentId() === 2; // Same parent
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 2);
    }

    public function testMoveNodeToRoot(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 2, 0);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (AbstractTreeNode $node) {
                return $node->getParentId() === 0; // Root level (using 0 instead of null)
            }));

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 0);
    }
}
