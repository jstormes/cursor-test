<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\Tree;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\SimpleNode;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeNodeRepository;
use Tests\TestCase;
use PDOStatement;

class DatabaseTreeNodeRepositoryTest extends TestCase
{
    private DatabaseTreeNodeRepository $repository;
    private DatabaseConnection $mockConnection;
    private TreeNodeDataMapper $mockDataMapper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConnection = $this->createMock(DatabaseConnection::class);
        $this->mockDataMapper = $this->createMock(TreeNodeDataMapper::class);
        $this->repository = new DatabaseTreeNodeRepository($this->mockConnection, $this->mockDataMapper);
    }

    public function testFindByIdSuccess(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 1,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'Root Node',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE id = ?', [1])
            ->willReturn($mockStatement);

        $expectedNode = new SimpleNode(1, 'Root Node', 1, null, 0);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntity')
            ->willReturn($expectedNode);

        $result = $this->repository->findById(1);

        $this->assertEquals($expectedNode, $result);
    }

    public function testFindByIdNotFound(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE id = ?', [999])
            ->willReturn($mockStatement);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function testFindByIdWithZeroId(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE id = ?', [0])
            ->willReturn($mockStatement);

        $result = $this->repository->findById(0);

        $this->assertNull($result);
    }

    public function testFindByTreeId(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'tree_id' => 1,
                    'parent_id' => null,
                    'name' => 'Root Node',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ],
                [
                    'id' => 2,
                    'tree_id' => 1,
                    'parent_id' => 1,
                    'name' => 'Child Node',
                    'sort_order' => 1,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $expectedNodes = [
            new SimpleNode(1, 'Root Node', 1, null, 0),
            new SimpleNode(2, 'Child Node', 1, 1, 1)
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedNodes);

        $result = $this->repository->findByTreeId(1);

        $this->assertEquals($expectedNodes, $result);
    }

    public function testFindByTreeIdWithEmptyResult(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [999])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findByTreeId(999);

        $this->assertEquals([], $result);
    }

    public function testFindChildren(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 2,
                    'tree_id' => 1,
                    'parent_id' => 1,
                    'name' => 'Child 1',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ],
                [
                    'id' => 3,
                    'tree_id' => 1,
                    'parent_id' => 1,
                    'name' => 'Child 2',
                    'sort_order' => 1,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE parent_id = ? ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $expectedNodes = [
            new SimpleNode(2, 'Child 1', 1, 1, 0),
            new SimpleNode(3, 'Child 2', 1, 1, 1)
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedNodes);

        $result = $this->repository->findChildren(1);

        $this->assertEquals($expectedNodes, $result);
    }

    public function testFindChildrenWithNoChildren(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE parent_id = ? ORDER BY sort_order', [5])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findChildren(5);

        $this->assertEquals([], $result);
    }

    public function testFindRootNodes(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'tree_id' => 1,
                    'parent_id' => null,
                    'name' => 'Root Node 1',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ],
                [
                    'id' => 2,
                    'tree_id' => 1,
                    'parent_id' => null,
                    'name' => 'Root Node 2',
                    'sort_order' => 1,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? AND parent_id IS NULL ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $expectedNodes = [
            new SimpleNode(1, 'Root Node 1', 1, null, 0),
            new SimpleNode(2, 'Root Node 2', 1, null, 1)
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedNodes);

        $result = $this->repository->findRootNodes(1);

        $this->assertEquals($expectedNodes, $result);
    }

    public function testFindRootNodesWithNoRootNodes(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? AND parent_id IS NULL ORDER BY sort_order', [999])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findRootNodes(999);

        $this->assertEquals([], $result);
    }

    public function testFindTreeStructure(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
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
                    'name' => 'Child 1',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ],
                [
                    'id' => 3,
                    'tree_id' => 1,
                    'parent_id' => 1,
                    'name' => 'Child 2',
                    'sort_order' => 1,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child 2', 1, 1, 1);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([$rootNode, $child1, $child2]);

        $result = $this->repository->findTreeStructure(1);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SimpleNode::class, $result[0]);
        $this->assertEquals('Root', $result[0]->getName());
        $this->assertCount(2, $result[0]->getChildren());
    }

    public function testFindTreeStructureWithEmptyTree(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [999])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findTreeStructure(999);

        $this->assertEquals([], $result);
    }

    public function testFindTreeStructureWithOrphanedNodes(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
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
                    'parent_id' => 999, // Non-existent parent
                    'name' => 'Orphaned',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $orphanedNode = new SimpleNode(2, 'Orphaned', 1, 999, 0);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([$rootNode, $orphanedNode]);

        $result = $this->repository->findTreeStructure(1);

        $this->assertEquals([$rootNode], $result);
        $this->assertCount(0, $result[0]->getChildren());
    }

    public function testSaveInsert(): void
    {
        $node = new SimpleNode(null, 'New Node', 1, null, 0);
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($node)
            ->willReturn([
                'id' => null,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'New Node',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                $this->callback(function ($params) {
                    return $params[0] === 1 && 
                           $params[1] === null && 
                           $params[2] === 'New Node' && 
                           $params[3] === 0 && 
                           $params[4] === 'SimpleNode' && 
                           $params[5] === '{}' &&
                           is_string($params[6]) && 
                           is_string($params[7]);
                })
            );

        $this->repository->save($node);
    }

    public function testSaveUpdate(): void
    {
        $node = new SimpleNode(1, 'Updated Node', 1, null, 1);
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($node)
            ->willReturn([
                'id' => 1,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'Updated Node',
                'sort_order' => 1,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE tree_nodes SET tree_id = ?, parent_id = ?, name = ?, sort_order = ?, type_class = ?, type_data = ?, updated_at = ? WHERE id = ?',
                $this->callback(function ($params) {
                    return $params[0] === 1 && 
                           $params[1] === null && 
                           $params[2] === 'Updated Node' && 
                           $params[3] === 1 && 
                           $params[4] === 'SimpleNode' && 
                           $params[5] === '{}' &&
                           is_string($params[6]) && 
                           $params[7] === 1;
                })
            );

        $this->repository->save($node);
    }

    public function testSaveWithZeroId(): void
    {
        $node = new SimpleNode(0, 'Node with Zero ID', 1, null, 0);
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($node)
            ->willReturn([
                'id' => 0,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'Node with Zero ID',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE tree_nodes SET tree_id = ?, parent_id = ?, name = ?, sort_order = ?, type_class = ?, type_data = ?, updated_at = ? WHERE id = ?',
                $this->callback(function ($params) {
                    return $params[0] === 1 && 
                           $params[1] === null && 
                           $params[2] === 'Node with Zero ID' && 
                           $params[3] === 0 && 
                           $params[4] === 'SimpleNode' && 
                           $params[5] === '{}' &&
                           is_string($params[6]) && 
                           $params[7] === 0;
                })
            );

        $this->repository->save($node);
    }

    public function testSaveWithEmptyName(): void
    {
        $node = new SimpleNode(null, '', 1, null, 0);
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($node)
            ->willReturn([
                'id' => null,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => '',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                $this->callback(function ($params) {
                    return $params[0] === 1 && 
                           $params[1] === null && 
                           $params[2] === '' && 
                           $params[3] === 0 && 
                           $params[4] === 'SimpleNode' && 
                           $params[5] === '{}' &&
                           is_string($params[6]) && 
                           is_string($params[7]);
                })
            );

        $this->repository->save($node);
    }

    public function testDelete(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM tree_nodes WHERE id = ?', [1]);

        $this->repository->delete(1);
    }

    public function testDeleteWithZeroId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM tree_nodes WHERE id = ?', [0]);

        $this->repository->delete(0);
    }

    public function testDeleteByTreeId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM tree_nodes WHERE tree_id = ?', [1]);

        $this->repository->deleteByTreeId(1);
    }

    public function testDeleteByTreeIdWithZeroId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM tree_nodes WHERE tree_id = ?', [0]);

        $this->repository->deleteByTreeId(0);
    }

    public function testSaveWithComplexTypeData(): void
    {
        $node = new SimpleNode(null, 'Complex Node', 1, null, 0);
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($node)
            ->willReturn([
                'id' => null,
                'tree_id' => 1,
                'parent_id' => null,
                'name' => 'Complex Node',
                'sort_order' => 0,
                'type_class' => 'SimpleNode',
                'type_data' => '{"key": "value", "nested": {"data": "test"}}'
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO tree_nodes (tree_id, parent_id, name, sort_order, type_class, type_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                $this->callback(function ($params) {
                    return $params[0] === 1 && 
                           $params[1] === null && 
                           $params[2] === 'Complex Node' && 
                           $params[3] === 0 && 
                           $params[4] === 'SimpleNode' && 
                           $params[5] === '{"key": "value", "nested": {"data": "test"}}' &&
                           is_string($params[6]) && 
                           is_string($params[7]);
                })
            );

        $this->repository->save($node);
    }

    public function testFindByTreeIdWithMultipleTrees(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'tree_id' => 1,
                    'parent_id' => null,
                    'name' => 'Tree 1 Root',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ],
                [
                    'id' => 2,
                    'tree_id' => 2,
                    'parent_id' => null,
                    'name' => 'Tree 2 Root',
                    'sort_order' => 0,
                    'type_class' => 'SimpleNode',
                    'type_data' => '{}'
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, tree_id, parent_id, name, sort_order, type_class, type_data FROM tree_nodes WHERE tree_id = ? ORDER BY sort_order', [1])
            ->willReturn($mockStatement);

        $expectedNodes = [
            new SimpleNode(1, 'Tree 1 Root', 1, null, 0)
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedNodes);

        $result = $this->repository->findByTreeId(1);

        $this->assertEquals($expectedNodes, $result);
    }
} 