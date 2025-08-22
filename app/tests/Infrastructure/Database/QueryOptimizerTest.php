<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Infrastructure\Database\QueryOptimizer;
use Tests\TestCase;

class QueryOptimizerTest extends TestCase
{
    private QueryOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new QueryOptimizer();
    }

    public function testOptimizeTreeQueryWithTreesTable(): void
    {
        $originalSql = 'SELECT * FROM trees WHERE is_active = 1';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_trees_active, idx_trees_created_at)', $optimizedSql);
        $this->assertStringContainsString('FROM trees USE INDEX', $optimizedSql);
        $this->assertStringContainsString('WHERE is_active = 1', $optimizedSql);
    }

    public function testOptimizeTreeQueryWithTreeNodesTable(): void
    {
        $originalSql = 'SELECT * FROM tree_nodes WHERE tree_id = 1';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_tree_nodes_tree_id, idx_tree_nodes_parent_id)', $optimizedSql);
        $this->assertStringContainsString('FROM tree_nodes USE INDEX', $optimizedSql);
        $this->assertStringContainsString('WHERE tree_id = 1', $optimizedSql);
    }

    public function testOptimizeTreeQueryWithBothTables(): void
    {
        $originalSql = 'SELECT t.*, tn.* FROM trees t JOIN tree_nodes tn ON t.id = tn.tree_id WHERE t.is_active = 1';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_trees_active, idx_trees_created_at)', $optimizedSql);
        $this->assertStringContainsString('USE INDEX (idx_tree_nodes_tree_id, idx_tree_nodes_parent_id)', $optimizedSql);
    }

    public function testOptimizeTreeQueryCaseInsensitive(): void
    {
        $originalSql = 'select * from TREES where is_active = 1';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_trees_active, idx_trees_created_at)', $optimizedSql);
    }

    public function testOptimizeTreeQueryWithComplexWhiteSpace(): void
    {
        $originalSql = "SELECT * FROM\ttrees   \nWHERE is_active = 1";
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_trees_active, idx_trees_created_at)', $optimizedSql);
    }

    public function testOptimizeTreeQueryWithoutMatchingTables(): void
    {
        $originalSql = 'SELECT * FROM users WHERE id = 1';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        // Should remain unchanged
        $this->assertEquals($originalSql, $optimizedSql);
        $this->assertStringNotContainsString('USE INDEX', $optimizedSql);
    }

    public function testOptimizeTreeQueryWithLeftJoin(): void
    {
        $originalSql = 'SELECT * FROM trees t LEFT JOIN tree_nodes tn ON t.id = tn.tree_id';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        $this->assertStringContainsString('USE INDEX (idx_trees_active, idx_trees_created_at)', $optimizedSql);
        $this->assertStringContainsString('LEFT JOIN tree_nodes tn FORCE INDEX (idx_tree_nodes_tree_id)', $optimizedSql);
        // FORCE INDEX is used for LEFT JOIN, not USE INDEX for this specific optimization
    }

    public function testOptimizeTreeQueryWithMultipleLeftJoins(): void
    {
        $originalSql = 'SELECT * FROM trees t ' .
                      'LEFT JOIN tree_nodes tn1 ON t.id = tn1.tree_id ' .
                      'LEFT JOIN tree_nodes tn2 ON tn1.id = tn2.parent_id';

        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        // Both LEFT JOINs should be optimized
        $leftJoinCount = substr_count($optimizedSql, 'FORCE INDEX (idx_tree_nodes_tree_id)');
        $this->assertEquals(2, $leftJoinCount);
    }

    public function testOptimizeTreeQueryPreservesOriginalQuery(): void
    {
        $originalSql = 'SELECT t.name, tn.content FROM trees t LEFT JOIN tree_nodes tn ON t.id = tn.tree_id WHERE t.is_active = 1 ORDER BY t.created_at';
        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        // Original structure should be preserved
        $this->assertStringContainsString('SELECT t.name, tn.content', $optimizedSql);
        $this->assertStringContainsString('WHERE t.is_active = 1', $optimizedSql);
        $this->assertStringContainsString('ORDER BY t.created_at', $optimizedSql);
    }

    public function testBuildBatchInsertQuerySimpleCase(): void
    {
        $table = 'trees';
        $columns = ['name', 'description', 'is_active'];
        $rows = [
            ['Tree 1', 'Description 1', true],
            ['Tree 2', 'Description 2', false]
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $expectedQuery = 'INSERT INTO trees (name, description, is_active) VALUES (?,?,?),(?,?,?)';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testBuildBatchInsertQuerySingleRow(): void
    {
        $table = 'tree_nodes';
        $columns = ['tree_id', 'parent_id', 'content'];
        $rows = [
            [1, null, 'Root node']
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $expectedQuery = 'INSERT INTO tree_nodes (tree_id, parent_id, content) VALUES (?,?,?)';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testBuildBatchInsertQueryMultipleRows(): void
    {
        $table = 'users';
        $columns = ['name', 'email'];
        $rows = [
            ['User 1', 'user1@example.com'],
            ['User 2', 'user2@example.com'],
            ['User 3', 'user3@example.com'],
            ['User 4', 'user4@example.com']
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $expectedQuery = 'INSERT INTO users (name, email) VALUES (?,?),(?,?),(?,?),(?,?)';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testBuildBatchInsertQueryWithSingleColumn(): void
    {
        $table = 'categories';
        $columns = ['name'];
        $rows = [
            ['Category 1'],
            ['Category 2']
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $expectedQuery = 'INSERT INTO categories (name) VALUES (?),(?)';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testBuildBatchInsertQueryWithManyColumns(): void
    {
        $table = 'complex_table';
        $columns = ['col1', 'col2', 'col3', 'col4', 'col5'];
        $rows = [
            [1, 2, 3, 4, 5],
            [6, 7, 8, 9, 10]
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $expectedQuery = 'INSERT INTO complex_table (col1, col2, col3, col4, col5) VALUES (?,?,?,?,?),(?,?,?,?,?)';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testBuildBatchInsertQueryThrowsExceptionForEmptyRows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No rows to insert');

        $table = 'test_table';
        $columns = ['name'];
        $rows = [];

        $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);
    }

    public function testBuildBatchInsertQueryHandlesSpecialTableNames(): void
    {
        $table = 'table_with_special_name123';
        $columns = ['field1', 'field2'];
        $rows = [
            ['value1', 'value2']
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $this->assertStringContainsString('INSERT INTO table_with_special_name123', $query);
    }

    public function testBuildBatchInsertQueryHandlesSpecialColumnNames(): void
    {
        $table = 'test_table';
        $columns = ['column_with_underscore', 'column123', 'ColumnWithCamelCase'];
        $rows = [
            ['value1', 'value2', 'value3']
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        $this->assertStringContainsString('(column_with_underscore, column123, ColumnWithCamelCase)', $query);
    }

    public function testQueryOptimizerIsStateless(): void
    {
        $sql1 = 'SELECT * FROM trees WHERE id = 1';
        $sql2 = 'SELECT * FROM tree_nodes WHERE tree_id = 2';

        $result1 = $this->optimizer->optimizeTreeQuery($sql1);
        $result2 = $this->optimizer->optimizeTreeQuery($sql2);

        // Each query should be optimized independently
        $this->assertStringContainsString('trees USE INDEX', $result1);
        $this->assertStringNotContainsString('tree_nodes USE INDEX', $result1);

        $this->assertStringContainsString('tree_nodes USE INDEX', $result2);
        $this->assertStringNotContainsString('trees USE INDEX', $result2);
    }

    public function testOptimizeTreeQueryHandlesEmptyString(): void
    {
        $result = $this->optimizer->optimizeTreeQuery('');
        $this->assertEquals('', $result);
    }

    public function testOptimizeTreeQueryHandlesWhitespaceOnly(): void
    {
        $result = $this->optimizer->optimizeTreeQuery('   ');
        $this->assertEquals('   ', $result);
    }

    public function testBatchInsertQueryCountsPlaceholdersCorrectly(): void
    {
        $table = 'test';
        $columns = ['a', 'b', 'c'];
        $rows = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9]
        ];

        $query = $this->optimizer->buildBatchInsertQuery($table, $columns, $rows);

        // Should have exactly 9 placeholders (3 columns × 3 rows)
        $placeholderCount = substr_count($query, '?');
        $this->assertEquals(9, $placeholderCount);

        // Should have exactly 2 commas between value groups
        $valueSeparators = substr_count($query, '),(');
        $this->assertEquals(2, $valueSeparators);
    }

    public function testIndexHintPatterns(): void
    {
        // Test various SQL patterns that should match the regex
        $testCases = [
            'FROM trees ' => true,
            'FROM trees WHERE' => true,
            'FROM TREES ' => true,
            'from trees ' => true,
            'FROM  trees  ' => true,
            'FROM trees_backup ' => false,
            'FROM tree_nodes ' => true,
            'FROM tree_nodes_backup ' => false,
            'FROM some_trees ' => false,
            'FROM tree_nodes WHERE' => true,
        ];

        foreach ($testCases as $sqlFragment => $shouldMatch) {
            $fullSql = "SELECT * {$sqlFragment}id = 1";
            $optimized = $this->optimizer->optimizeTreeQuery($fullSql);

            if ($shouldMatch) {
                $this->assertStringContainsString(
                    'USE INDEX',
                    $optimized,
                    "SQL fragment '{$sqlFragment}' should be optimized"
                );
            } else {
                $this->assertStringNotContainsString(
                    'USE INDEX',
                    $optimized,
                    "SQL fragment '{$sqlFragment}' should not be optimized"
                );
            }
        }
    }

    public function testJoinOptimizationPreservesQueryStructure(): void
    {
        $originalSql = 'SELECT t.name, tn.content, tn.created_at ' .
                      'FROM trees t ' .
                      'LEFT JOIN tree_nodes tn ON t.id = tn.tree_id ' .
                      'WHERE t.is_active = 1 ' .
                      'ORDER BY tn.created_at DESC ' .
                      'LIMIT 10';

        $optimizedSql = $this->optimizer->optimizeTreeQuery($originalSql);

        // Verify the query structure is preserved
        $this->assertStringContainsString('SELECT t.name, tn.content, tn.created_at', $optimizedSql);
        $this->assertStringContainsString('ON t.id = tn.tree_id', $optimizedSql);
        $this->assertStringContainsString('WHERE t.is_active = 1', $optimizedSql);
        $this->assertStringContainsString('ORDER BY tn.created_at DESC', $optimizedSql);
        $this->assertStringContainsString('LIMIT 10', $optimizedSql);

        // Verify optimizations were applied
        $this->assertStringContainsString('trees USE INDEX', $optimizedSql);
        $this->assertStringContainsString('FORCE INDEX', $optimizedSql);
        // LEFT JOIN tree_nodes should have FORCE INDEX, not USE INDEX for this query
        $this->assertStringContainsString('tree_nodes tn FORCE INDEX', $optimizedSql);
    }
}
