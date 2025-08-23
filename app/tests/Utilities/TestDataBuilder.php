<?php

declare(strict_types=1);

namespace App\Tests\Utilities;

use App\Domain\Tree\Tree;
use App\Domain\Tree\AbstractTreeNode;
use DateTime;

class TestDataBuilder
{
    public static function createTreeWithNodes(
        ?int $treeId = 1,
        string $treeName = 'Test Tree',
        int $nodeCount = 3
    ): array {
        $tree = TreeFactory::create($treeId, $treeName);
        $nodes = TreeNodeFactory::createHierarchy($treeId, 2, $nodeCount);
        
        return ['tree' => $tree, 'nodes' => $nodes];
    }

    public static function createCompleteTreeStructure(): array
    {
        $tree = TreeFactory::create(1, 'Complete Tree', 'A tree with various node types');
        $nodes = TreeNodeFactory::createMixed(1);
        
        return ['tree' => $tree, 'nodes' => $nodes];
    }

    public static function createMultipleTreesWithNodes(int $treeCount = 2): array
    {
        $data = [];
        for ($i = 1; $i <= $treeCount; $i++) {
            $data[] = self::createTreeWithNodes($i, "Tree $i", 2);
        }
        return $data;
    }

    public static function createTestDates(): array
    {
        return [
            'past' => new DateTime('2022-01-01 08:00:00'),
            'present' => new DateTime('2023-06-15 12:30:00'),
            'future' => new DateTime('2024-12-31 23:59:59')
        ];
    }

    public static function createValidationTestData(): array
    {
        return [
            'valid' => [
                'name' => 'Valid Tree Name',
                'description' => 'Valid description'
            ],
            'empty_name' => [
                'name' => '',
                'description' => 'Description for empty name'
            ],
            'long_name' => [
                'name' => str_repeat('A', 256),
                'description' => 'Description for long name'
            ],
            'special_chars' => [
                'name' => 'Tree with "quotes" & <tags>',
                'description' => 'Description with émojis 🌳'
            ]
        ];
    }
}