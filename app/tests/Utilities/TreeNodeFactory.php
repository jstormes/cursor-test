<?php

declare(strict_types=1);

namespace App\Tests\Utilities;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\SimpleNode;

class TreeNodeFactory
{
    public static function createSimple(
        ?int $id = null,
        int $treeId = 1,
        ?int $parentId = null,
        string $name = 'Test Node',
        int $sortOrder = 1,
        string $content = 'Test content'
    ): SimpleNode {
        return new SimpleNode($id, $treeId, $parentId, $name, $sortOrder, $content);
    }

    public static function createButton(
        ?int $id = null,
        int $treeId = 1,
        ?int $parentId = null,
        string $name = 'Test Button',
        int $sortOrder = 1,
        string $text = 'Click me',
        string $action = '#'
    ): ButtonNode {
        return new ButtonNode($id, $treeId, $parentId, $name, $sortOrder, $text, $action);
    }

    public static function createHierarchy(int $treeId = 1, int $depth = 2, int $childrenPerLevel = 2): array
    {
        $nodes = [];
        $nodeId = 1;

        // Create root nodes
        for ($i = 1; $i <= $childrenPerLevel; $i++) {
            $rootNode = self::createSimple($nodeId++, $treeId, null, "Root Node $i", $i, "Root content $i");
            $nodes[] = $rootNode;

            // Create children if depth > 1
            if ($depth > 1) {
                for ($j = 1; $j <= $childrenPerLevel; $j++) {
                    $childNode = self::createSimple($nodeId++, $treeId, $rootNode->getId(), "Child Node $i-$j", $j, "Child content $i-$j");
                    $nodes[] = $childNode;
                    $rootNode->addChild($childNode);

                    // Create grandchildren if depth > 2
                    if ($depth > 2) {
                        for ($k = 1; $k <= $childrenPerLevel; $k++) {
                            $grandchildNode = self::createSimple($nodeId++, $treeId, $childNode->getId(), "Grandchild Node $i-$j-$k", $k, "Grandchild content $i-$j-$k");
                            $nodes[] = $grandchildNode;
                            $childNode->addChild($grandchildNode);
                        }
                    }
                }
            }
        }

        return $nodes;
    }

    public static function createMixed(int $treeId = 1): array
    {
        return [
            self::createSimple(1, $treeId, null, 'Simple Root', 1, 'Simple content'),
            self::createButton(2, $treeId, null, 'Button Root', 2, 'Click me', '#action'),
            self::createSimple(3, $treeId, 1, 'Simple Child', 1, 'Child content'),
            self::createButton(4, $treeId, 2, 'Button Child', 1, 'Child button', '#child-action')
        ];
    }
}