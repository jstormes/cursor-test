<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Tests\Utilities\MockClock;
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

        $expectedTree = new Tree(1, 'Test Tree', 'A test tree', null, null, true, new MockClock());

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

    public function testFindByIdWithZeroId(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE id = ?', [0])
            ->willReturn($mockStatement);

        $result = $this->repository->findById(0);

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

        $expectedTree = new Tree(1, 'Test Tree', 'A test tree', null, null, true, new MockClock());

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntity')
            ->willReturn($expectedTree);

        $result = $this->repository->findByName('Test Tree');

        $this->assertEquals($expectedTree, $result);
    }

    public function testFindByNameNotFound(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE name = ?', ['Non Existent'])
            ->willReturn($mockStatement);

        $result = $this->repository->findByName('Non Existent');

        $this->assertNull($result);
    }

    public function testFindByNameWithEmptyString(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE name = ?', [''])
            ->willReturn($mockStatement);

        $result = $this->repository->findByName('');

        $this->assertNull($result);
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
            new Tree(1, 'Tree 1', 'First tree', null, null, true, new MockClock()),
            new Tree(2, 'Tree 2', 'Second tree', null, null, true, new MockClock())
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedTrees);

        $result = $this->repository->findAll();

        $this->assertEquals($expectedTrees, $result);
    }

    public function testFindAllWithEmptyResult(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY name')
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findAll();

        $this->assertEquals([], $result);
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
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? ORDER BY name', [1])
            ->willReturn($mockStatement);

        $expectedTrees = [
            new Tree(1, 'Active Tree', 'An active tree', null, null, true, new MockClock())
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedTrees);

        $result = $this->repository->findActive();

        $this->assertEquals($expectedTrees, $result);
    }

    public function testFindActiveWithNoActiveTrees(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? ORDER BY name', [1])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findActive();

        $this->assertEquals([], $result);
    }

    public function testFindDeleted(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Deleted Tree',
                    'description' => 'A deleted tree',
                    'created_at' => '2023-01-01 10:00:00',
                    'updated_at' => '2023-01-01 11:00:00',
                    'is_active' => 0
                ]
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? ORDER BY name', [0])
            ->willReturn($mockStatement);

        $expectedTrees = [
            new Tree(1, 'Deleted Tree', 'A deleted tree', null, null, true, new MockClock())
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedTrees);

        $result = $this->repository->findDeleted();

        $this->assertEquals($expectedTrees, $result);
    }

    public function testFindDeletedWithNoDeletedTrees(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? ORDER BY name', [0])
            ->willReturn($mockStatement);

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn([]);

        $result = $this->repository->findDeleted();

        $this->assertEquals([], $result);
    }

    public function testSaveInsert(): void
    {
        $tree = new Tree(null, 'New Tree', 'A new tree', null, null, true, new MockClock());

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
        $tree = new Tree(1, 'Updated Tree', 'An updated tree', null, null, true, new MockClock());

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

    public function testSaveWithZeroId(): void
    {
        $tree = new Tree(0, 'Tree with Zero ID', 'A tree with zero ID', null, null, true, new MockClock());

        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($tree)
            ->willReturn([
                'id' => 0,
                'name' => 'Tree with Zero ID',
                'description' => 'A tree with zero ID',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 11:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE trees SET name = ?, description = ?, updated_at = ?, is_active = ? WHERE id = ?',
                ['Tree with Zero ID', 'A tree with zero ID', '2023-01-01 11:00:00', 1, 0]
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

    public function testDeleteWithZeroId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM trees WHERE id = ?', [0]);

        $this->repository->delete(0);
    }

    public function testSoftDelete(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('UPDATE trees SET is_active = 0, updated_at = NOW() WHERE id = ?', [1]);

        $this->repository->softDelete(1);
    }

    public function testSoftDeleteWithZeroId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('UPDATE trees SET is_active = 0, updated_at = NOW() WHERE id = ?', [0]);

        $this->repository->softDelete(0);
    }

    public function testRestore(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('UPDATE trees SET is_active = 1, updated_at = NOW() WHERE id = ?', [1]);

        $this->repository->restore(1);
    }

    public function testRestoreWithZeroId(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with('UPDATE trees SET is_active = 1, updated_at = NOW() WHERE id = ?', [0]);

        $this->repository->restore(0);
    }

    public function testSaveWithNullDescription(): void
    {
        $tree = new Tree(null, 'Tree with Null Description', null, null, null, true, new MockClock());

        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($tree)
            ->willReturn([
                'id' => null,
                'name' => 'Tree with Null Description',
                'description' => null,
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 10:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO trees (name, description, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?)',
                ['Tree with Null Description', null, '2023-01-01 10:00:00', '2023-01-01 10:00:00', 1]
            );

        $this->mockConnection->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('6');

        $this->repository->save($tree);
    }

    public function testSaveWithEmptyName(): void
    {
        $tree = new Tree(null, '', 'Empty name tree', null, null, true, new MockClock());

        $this->mockDataMapper->expects($this->once())
            ->method('mapToArray')
            ->with($tree)
            ->willReturn([
                'id' => null,
                'name' => '',
                'description' => 'Empty name tree',
                'created_at' => '2023-01-01 10:00:00',
                'updated_at' => '2023-01-01 10:00:00',
                'is_active' => 1
            ]);

        $this->mockConnection->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO trees (name, description, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?)',
                ['', 'Empty name tree', '2023-01-01 10:00:00', '2023-01-01 10:00:00', 1]
            );

        $this->mockConnection->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('7');

        $this->repository->save($tree);
    }
}
