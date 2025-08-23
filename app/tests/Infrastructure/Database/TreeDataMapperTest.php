<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\TreeDataMapper;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use DateTime;

class TreeDataMapperTest extends TestCase
{
    private TreeDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new TreeDataMapper(new MockClock());
    }

    public function testMapToEntity(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Tree',
            'description' => 'A test tree',
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 11:00:00',
            'is_active' => 1
        ];

        $tree = $this->mapper->mapToEntity($data);

        $this->assertInstanceOf(Tree::class, $tree);
        $this->assertEquals(1, $tree->getId());
        $this->assertEquals('Test Tree', $tree->getName());
        $this->assertEquals('A test tree', $tree->getDescription());
        $this->assertInstanceOf(DateTime::class, $tree->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $tree->getUpdatedAt());
        $this->assertTrue($tree->isActive());
    }

    public function testMapToEntityWithNullDescription(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Tree',
            'description' => null,
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 11:00:00',
            'is_active' => 0
        ];

        $tree = $this->mapper->mapToEntity($data);

        $this->assertInstanceOf(Tree::class, $tree);
        $this->assertNull($tree->getDescription());
        $this->assertFalse($tree->isActive());
    }

    public function testMapToArray(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Test Tree', 'A test tree', $createdAt, $updatedAt, true, new MockClock());

        $data = $this->mapper->mapToArray($tree);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Test Tree',
            'description' => 'A test tree',
            'created_at' => '2023-01-01 10:00:00',
            'updated_at' => '2023-01-01 11:00:00',
            'is_active' => 1
        ], $data);
    }

    public function testMapToArrayWithInvalidEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of Tree');

        $this->mapper->mapToArray(new \stdClass());
    }

    public function testMapToEntities(): void
    {
        $data = [
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
        ];

        $trees = $this->mapper->mapToEntities($data);

        $this->assertCount(2, $trees);
        $this->assertInstanceOf(Tree::class, $trees[0]);
        $this->assertInstanceOf(Tree::class, $trees[1]);
        $this->assertEquals('Tree 1', $trees[0]->getName());
        $this->assertEquals('Tree 2', $trees[1]->getName());
        $this->assertTrue($trees[0]->isActive());
        $this->assertFalse($trees[1]->isActive());
    }
}
