<?php

declare(strict_types=1);

namespace App\Tests\Application\Services;

use App\Application\Exceptions\ValidationException;
use App\Application\Services\TreeService;
use App\Application\Validation\TreeNodeValidator;
use App\Application\Validation\TreeValidator;
use App\Application\Validation\ValidationResult;
use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\InvalidTreeOperationException;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeNodeFactory;
use App\Domain\Tree\TreeNodeNotFoundException;
use App\Domain\Tree\TreeNotFoundException;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Database\UnitOfWork;
use App\Infrastructure\Time\ClockInterface;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;

class TreeServiceTest extends TestCase
{
    private TreeService $service;
    private TreeRepository $mockTreeRepository;
    private TreeNodeRepository $mockNodeRepository;
    private UnitOfWork $mockUnitOfWork;
    private TreeNodeFactory $mockNodeFactory;
    private TreeValidator $mockTreeValidator;
    private TreeNodeValidator $mockNodeValidator;
    private ClockInterface $mockClock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTreeRepository = $this->createMock(TreeRepository::class);
        $this->mockNodeRepository = $this->createMock(TreeNodeRepository::class);
        $this->mockUnitOfWork = $this->createMock(UnitOfWork::class);
        $this->mockNodeFactory = $this->createMock(TreeNodeFactory::class);
        $this->mockTreeValidator = $this->createMock(TreeValidator::class);
        $this->mockNodeValidator = $this->createMock(TreeNodeValidator::class);
        $this->mockClock = $this->createMock(ClockInterface::class);

        $this->service = new TreeService(
            $this->mockTreeRepository,
            $this->mockNodeRepository,
            $this->mockUnitOfWork,
            $this->mockNodeFactory,
            $this->mockTreeValidator,
            $this->mockNodeValidator,
            $this->mockClock
        );
    }

    private function setupValidationMocks(bool $treeValid = true, bool $nodeValid = true, array $treeData = null, array $nodeData = null): void
    {
        $treeResult = new ValidationResult($treeValid);
        $nodeResult = new ValidationResult($nodeValid);

        $this->mockTreeValidator->method('validate')->willReturn($treeResult);
        $this->mockTreeValidator->method('sanitize')->willReturn($treeData ?? ['name' => 'Test Tree', 'description' => 'A test tree']);

        $this->mockNodeValidator->method('validate')->willReturn($nodeResult);
        $this->mockNodeValidator->method('sanitize')->willReturn($nodeData ?? ['type' => 'SimpleNode', 'name' => 'Test Node']);
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

        $expectedTree = new Tree(null, 'Test Tree', 'A test tree', null, null, true, new MockClock());

        // Mock validation
        $validResult = new ValidationResult(true);
        $this->mockTreeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockTreeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn(['name' => 'Test Tree', 'description' => 'A test tree']);

        $this->mockNodeValidator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn($validResult);
        $this->mockNodeValidator->expects($this->exactly(2))
            ->method('sanitize')
            ->willReturnOnConsecutiveCalls(
                $nodes[0],
                $nodes[1]
            );

        // Mock factory
        $mockSimpleNode = $this->createMock(SimpleNode::class);
        $mockButtonNode = $this->createMock(ButtonNode::class);
        $this->mockNodeFactory->expects($this->exactly(2))
            ->method('createFromData')
            ->willReturnOnConsecutiveCalls($mockSimpleNode, $mockButtonNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->exactly(3))
            ->method('registerNew')
            ->withConsecutive(
                [$this->isInstanceOf(Tree::class)],
                [$mockSimpleNode],
                [$mockButtonNode]
            );

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

        // Mock validation to pass
        $validResult = new ValidationResult(true);
        $this->mockTreeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockTreeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn(['name' => 'Test Tree', 'description' => 'A test tree']);

        $this->mockNodeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockNodeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn($nodes[0]);

        $mockSimpleNode = $this->createMock(SimpleNode::class);
        $this->mockNodeFactory->expects($this->once())
            ->method('createFromData')
            ->willReturn($mockSimpleNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit')
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

        // Mock validation to pass for tree with null description
        $validResult = new ValidationResult(true);
        $this->mockTreeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockTreeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn(['name' => 'Test Tree', 'description' => null]);

        $this->mockNodeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockNodeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn($nodes[0]);

        $mockSimpleNode = $this->createMock(SimpleNode::class);
        $this->mockNodeFactory->expects($this->once())
            ->method('createFromData')
            ->willReturn($mockSimpleNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->exactly(2))
            ->method('registerNew')
            ->withConsecutive(
                [$this->isInstanceOf(Tree::class)],
                [$mockSimpleNode]
            );

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Test Tree', null, $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Test Tree', $result->getName());
    }

    public function testCreateTreeWithNodesWithEmptyNodesArray(): void
    {
        $nodes = [];

        // Setup validation mocks
        $this->setupValidationMocks(
            true,
            true,
            ['name' => 'Test Tree', 'description' => 'A test tree'],
            null
        );

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->once())
            ->method('registerNew');

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

        // Setup validation mocks to pass validation but fail on missing tree_id
        $this->setupValidationMocks(
            true,
            true,
            ['name' => 'Test Tree', 'description' => 'A test tree'],
            ['type' => 'SimpleNode', 'name' => 'Root', 'parent_id' => null, 'sort_order' => 0]
        );

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(InvalidTreeOperationException::class);
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

        // Setup validation mocks to pass validation but fail on missing tree_id
        $this->setupValidationMocks(
            true,
            true,
            ['name' => 'Test Tree', 'description' => 'A test tree'],
            ['type' => 'SimpleNode', 'name' => 'Root', 'parent_id' => null, 'sort_order' => 0]
        );

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(InvalidTreeOperationException::class);
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

        // Setup validation mocks to pass validation but fail on unknown node type
        $this->setupValidationMocks(
            true,
            true,
            ['name' => 'Test Tree', 'description' => 'A test tree'],
            ['type' => 'UnknownNode', 'name' => 'Root', 'parent_id' => null, 'sort_order' => 0, 'tree_id' => 1]
        );

        // Mock the node factory to throw the exception
        $this->mockNodeFactory->expects($this->once())
            ->method('createFromData')
            ->willThrowException(InvalidTreeOperationException::unknownNodeType('UnknownNode'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(InvalidTreeOperationException::class);
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

        // Setup validation mocks to pass validation but fail on node creation
        $this->setupValidationMocks(
            true,
            true,
            ['name' => 'Test Tree', 'description' => 'A test tree'],
            ['type' => 'SimpleNode', 'name' => 'Root', 'parent_id' => null, 'sort_order' => 0, 'tree_id' => 1]
        );

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeFactory->expects($this->once())
            ->method('createFromData')
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
        $newNode = new SimpleNode(1, 'Test Node', 1, 2, 0); // Node with new parent

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
            ->with($node, 2)
            ->willReturn($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('registerDirty')
            ->with($newNode);

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

        $this->expectException(TreeNodeNotFoundException::class);
        $this->expectExceptionMessage('Tree node with ID 999 not found');

        $this->service->moveNode(999, 2);
    }

    public function testMoveButtonNode(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, 1, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        $newNode = new ButtonNode(1, 'Test Button', 1, 3, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
            ->with($node, 3)
            ->willReturn($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('registerDirty')
            ->with($newNode);

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

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
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

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
            ->willThrowException(InvalidTreeOperationException::unknownNodeType('UnknownType'));

        $this->mockUnitOfWork->expects($this->once())
            ->method('rollback');

        $this->expectException(InvalidTreeOperationException::class);
        $this->expectExceptionMessage('Unknown node type: UnknownType');

        $this->service->moveNode(1, 2);
    }

    public function testDeleteTreeWithNodes(): void
    {
        $nodes = [new SimpleNode(1, 'Node', 1, null, 0)];
        $tree = new Tree(1, 'Test Tree', 'A test tree', null, null, true, new MockClock());

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn($nodes);

        $this->mockTreeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->mockUnitOfWork->expects($this->exactly(2))
            ->method('registerDeleted')
            ->withConsecutive([$nodes[0]], [$tree]);

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->deleteTreeWithNodes(1);
    }

    public function testDeleteTreeWithNodesWithException(): void
    {
        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findByTreeId')
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
        $nodes = [new SimpleNode(1, 'Node', 1, null, 0)];

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn($nodes);

        $this->mockTreeRepository->expects($this->once())
            ->method('findById')
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
        $tree = new Tree(1, 'Test Tree', 'A test tree', null, null, true, new MockClock());
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

        $this->expectException(TreeNotFoundException::class);
        $this->expectExceptionMessage('Tree with ID 999 not found');

        $this->service->getTreeStructure(999);
    }

    public function testGetTreeStructureWithEmptyNodes(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree', null, null, true, new MockClock());

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

        // Setup validation mocks to pass validation
        $validResult = new ValidationResult(true);
        $this->mockTreeValidator->expects($this->once())
            ->method('validate')
            ->willReturn($validResult);
        $this->mockTreeValidator->expects($this->once())
            ->method('sanitize')
            ->willReturn(['name' => 'Complex Tree', 'description' => 'A complex tree']);

        $this->mockNodeValidator->expects($this->exactly(3))
            ->method('validate')
            ->willReturn($validResult);
        $this->mockNodeValidator->expects($this->exactly(3))
            ->method('sanitize')
            ->willReturnOnConsecutiveCalls(
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
            );

        // Mock nodes that would be created
        $simpleNode = new SimpleNode(1, 'Root Node', 1, null, 0);
        $buttonNode1 = new ButtonNode(2, 'Action Button', 1, null, 1, [
            'button_text' => 'Click "Me" & <Test>',
            'button_action' => 'alert("Hello & World")'
        ]);
        $buttonNode2 = new ButtonNode(3, 'Unicode Button', 1, null, 2, [
            'button_text' => 'Click émoji 🎯',
            'button_action' => 'customAction("émoji")'
        ]);

        $this->mockNodeFactory->expects($this->exactly(3))
            ->method('createFromData')
            ->willReturnOnConsecutiveCalls($simpleNode, $buttonNode1, $buttonNode2);

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockUnitOfWork->expects($this->exactly(4))
            ->method('registerNew'); // 1 tree + 3 nodes

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $result = $this->service->createTreeWithNodes('Complex Tree', 'A complex tree', $nodes);

        $this->assertInstanceOf(Tree::class, $result);
        $this->assertEquals('Complex Tree', $result->getName());
    }

    public function testMoveNodeToSameParent(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 2, 0);
        $newNode = new SimpleNode(1, 'Test Node', 1, 2, 0); // Same parent

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
            ->with($node, 2)
            ->willReturn($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('registerDirty')
            ->with($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 2);
    }

    public function testMoveNodeToRoot(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1, 2, 0);
        $newNode = new SimpleNode(1, 'Test Node', 1, 0, 0); // Moved to root

        $this->mockUnitOfWork->expects($this->once())
            ->method('beginTransaction');

        $this->mockNodeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($node);

        $this->mockNodeFactory->expects($this->once())
            ->method('createWithNewParent')
            ->with($node, 0)
            ->willReturn($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('registerDirty')
            ->with($newNode);

        $this->mockUnitOfWork->expects($this->once())
            ->method('commit');

        $this->service->moveNode(1, 0);
    }
}
