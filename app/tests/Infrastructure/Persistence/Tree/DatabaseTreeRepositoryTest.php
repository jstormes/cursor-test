<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use Tests\TestCase;
use DateTime;
use PDOStatement;

class DatabaseTreeRepositoryTest extends TestCase
{
    private DatabaseTreeRepository $repository;
    private DatabaseConnection $mockConnection;
    private TreeDataMapper $mockDataMapper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConnection = $this->createMock(DatabaseConnection::class);
        $this->mockDataMapper = $this->createMock(TreeDataMapper::class);
        $this->repository = new DatabaseTreeRepository($this->mockConnection, $this->mockDataMapper);
    }

    public function testFindByIdSuccess(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 1,
                'name' => 'Test Tree',
                'description' => 'A test tree',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 11:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE id = ?', [1])
            ->willReturn($mockStatement);

        $expectedTree = new Tree(1, 'Test Tree', 'A test tree');

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntity')
            ->willReturn($expectedTree);

        $result = $this->repository->findById(1);

        $this->assertEquals($expectedTree, $result);
    }

    public function testFindByIdNotFound(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE id = ?', [999])
            ->willReturn($mockStatement);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function testFindByNameSuccess(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 1,
                'name' => 'Test Tree',
                'description' => 'A test tree',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 11:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE name = ?', ['Test Tree'])
            ->willReturn($mockStatement);

        $expectedTree = new Tree(1, 'Test Tree', 'A test tree');

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntity')
            ->willReturn($expectedTree);

        $result = $this->repository->findByName('Test Tree');

        $this->assertEquals($expectedTree, $result);
    }

    public function testFindAll(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Tree 1',
                    'description' => 'First tree',
                    'created_at' => '2023-01-01 10:00:00',
                    'updated_at' => '2023-01-01 11:00:00',
                    'is_active' => 1
                ],
                [
                    'id' => 2,
                    'name' => 'Tree 2',
                    'description' => 'Second tree',
                    'created_at' => '2023-01-02 10:00:00',
                    'updated_at' => '2023-01-02 11:00:00',
                    'is_active' => 0
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY name')
            ->willReturn($mockStatement);

        $expectedTrees = [
            new Tree(1, 'Tree 1', 'First tree'),
            new Tree(2, 'Tree 2', 'Second tree')
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedTrees);

        $result = $this->repository->findAll();

        $this->assertEquals($expectedTrees, $result);
    }

    public function testFindActive(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Active Tree',
                    'description' => 'An active tree',
                    'created_at' => '2023-01-01 10:00:00',
                    'updated_at' => '2023-01-01 11:00:00',
                    'is_active' => 1
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = 1 ORDER BY name')
            ->willReturn($mockStatement);

        $expectedTrees = [
            new Tree(1, 'Active Tree', 'An active tree')
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedTrees);

        $result = $this->repository->findActive();

        $this->assertEquals($expectedTrees, $result);
    }

    public function testSaveInsert(): void
    {
        $tree = new Tree(null, 'New Tree', 'A new tree');
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($tree)
            ->willReturn([
                'id' => null,
                'name' => 'New Tree',
                'description' => 'A new tree',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 10:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO trees (name, description, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?)',
                ['New Tree', 'A new tree', '2023-01-01 10:00:00', '2023-01-01 10:00:00', 1]
            );

        $this->mockConnection->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('5');

        $this->repository->save($tree);
    }

    public function testSaveUpdate(): void
    {
        $tree = new Tree(1, 'Updated Tree', 'An updated tree');
        
        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($tree)
            ->willReturn([
                'id' => 1,
                'name' => 'Updated Tree',
                'description' => 'An updated tree',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 11:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE trees SET name = ?, description = ?, updated_at = ?, is_active = ? WHERE id = ?',
                ['Updated Tree', 'An updated tree', '2023-01-01 11:00:00', 1, 1]
            );

        $this->repository->save($tree);
    }

    public function testDelete(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM trees WHERE id = ?', [1]);

        $this->repository->delete(1);
    }
} 