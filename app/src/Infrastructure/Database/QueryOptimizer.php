<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

class QueryOptimizer
{
    public function optimizeTreeQuery(string $sql): string
    {
        // Add indexes hints and optimizations for tree queries
        $sql = $this->addIndexHints($sql);
        $sql = $this->optimizeJoins($sql);
        return $sql;
    }

    private function addIndexHints(string $sql): string|null
    {
        // Add index hints for common tree operations
        $patterns = [
            '/FROM\s+trees\s+/i' => 'FROM trees USE INDEX (idx_trees_active, idx_trees_created_at) ',
            '/FROM\s+tree_nodes\s+/i' => 'FROM tree_nodes USE INDEX (idx_tree_nodes_tree_id, idx_tree_nodes_parent_id) ',
            '/(?<!LEFT\s)JOIN\s+tree_nodes\s+/i' => 'JOIN tree_nodes USE INDEX (idx_tree_nodes_tree_id, idx_tree_nodes_parent_id) ',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $sql = preg_replace($pattern, $replacement, $sql);
        }

        return $sql;
    }

    private function optimizeJoins(string $sql): string
    {
        // Optimize common join patterns by adding FORCE INDEX hints
        // This specifically targets LEFT JOIN patterns that might not be caught by the general pattern matching
        $sql = preg_replace(
            '/LEFT JOIN tree_nodes(\s+\w+)?\s+ON/i',
            'LEFT JOIN tree_nodes$1 FORCE INDEX (idx_tree_nodes_tree_id) ON',
            $sql
        );

        return $sql;
    }

    public function buildBatchInsertQuery(string $table, array $columns, array $rows): string
    {
        if (empty($rows)) {
            throw new \InvalidArgumentException('No rows to insert');
        }

        $columnList = implode(', ', $columns);
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $valuesList = str_repeat($placeholders . ',', count($rows) - 1) . $placeholders;

        return "INSERT INTO {$table} ({$columnList}) VALUES {$valuesList}";
    }
}
