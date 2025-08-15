<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database\Query;

use App\Infrastructure\Database\Query\TreeQuery;
use Tests\TestCase;

class TreeQueryTest extends TestCase
{
    private TreeQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = new TreeQuery();
    }

    public function testWithActiveTrue(): void
    {
        $result = $this->query->withActive(true);

        $this->assertSame($this->query, $result);
        $this->assertEquals(['is_active' => 1], $this->query->getFilters());
    }

    public function testWithActiveFalse(): void
    {
        $result = $this->query->withActive(false);

        $this->assertSame($this->query, $result);
        $this->assertEquals(['is_active' => 0], $this->query->getFilters());
    }

    public function testWithNameLike(): void
    {
        $result = $this->query->withNameLike('test');

        $this->assertSame($this->query, $result);
        $this->assertEquals(['name_like' => 'test'], $this->query->getFilters());
    }

    public function testWithDescriptionLike(): void
    {
        $result = $this->query->withDescriptionLike('description');

        $this->assertSame($this->query, $result);
        $this->assertEquals(['description_like' => 'description'], $this->query->getFilters());
    }

    public function testOrderByCreatedAt(): void
    {
        $result = $this->query->orderByCreatedAt('DESC');

        $this->assertSame($this->query, $result);
        $this->assertEquals(['created_at' => 'DESC'], $this->query->getOrderBy());
    }

    public function testOrderByName(): void
    {
        $result = $this->query->orderByName('ASC');

        $this->assertSame($this->query, $result);
        $this->assertEquals(['name' => 'ASC'], $this->query->getOrderBy());
    }

    public function testLimit(): void
    {
        $result = $this->query->limit(10);

        $this->assertSame($this->query, $result);
        $this->assertEquals(10, $this->query->getLimit());
    }

    public function testOffset(): void
    {
        $result = $this->query->offset(20);

        $this->assertSame($this->query, $result);
        $this->assertEquals(20, $this->query->getOffset());
    }

    public function testBuildSqlWithNoFilters(): void
    {
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees', $sql);
    }

    public function testBuildSqlWithActiveFilter(): void
    {
        $this->query->withActive(true);
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ?', $sql);
    }

    public function testBuildSqlWithNameLikeFilter(): void
    {
        $this->query->withNameLike('test');
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE name LIKE ?', $sql);
    }

    public function testBuildSqlWithMultipleFilters(): void
    {
        $this->query->withActive(true)->withNameLike('test');
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? AND name LIKE ?', $sql);
    }

    public function testBuildSqlWithOrderBy(): void
    {
        $this->query->orderByCreatedAt('DESC');
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY created_at DESC', $sql);
    }

    public function testBuildSqlWithMultipleOrderBy(): void
    {
        $this->query->orderByCreatedAt('DESC')->orderByName('ASC');
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees ORDER BY created_at DESC, name ASC', $sql);
    }

    public function testBuildSqlWithLimit(): void
    {
        $this->query->limit(10);
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees LIMIT ?', $sql);
    }

    public function testBuildSqlWithOffset(): void
    {
        $this->query->offset(20);
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees OFFSET ?', $sql);
    }

    public function testBuildSqlWithLimitAndOffset(): void
    {
        $this->query->limit(10)->offset(20);
        $sql = $this->query->buildSql();

        $this->assertEquals('SELECT id, name, description, created_at, updated_at, is_active FROM trees LIMIT ? OFFSET ?', $sql);
    }

    public function testBuildSqlWithAllOptions(): void
    {
        $this->query
            ->withActive(true)
            ->withNameLike('test')
            ->orderByCreatedAt('DESC')
            ->limit(10)
            ->offset(20);

        $sql = $this->query->buildSql();

        $this->assertEquals(
            'SELECT id, name, description, created_at, updated_at, is_active FROM trees WHERE is_active = ? AND name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            $sql
        );
    }
}
