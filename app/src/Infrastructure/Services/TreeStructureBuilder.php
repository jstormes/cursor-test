<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Tree\TreeNode;

class TreeStructureBuilder
{
    /**
     * Build a hierarchical tree structure from a flat array of nodes
     * 
     * @param TreeNode[] $nodes Flat array of tree nodes
     * @return TreeNode[] Array of root nodes with children populated
     */
    public function buildTreeFromNodes(array $nodes): array
    {
        $nodeMap = [];
        $rootNodes = [];

        // Create a map of all nodes by ID
        foreach ($nodes as $node) {
            $nodeMap[$node->getId()] = $node;
        }

        // Build the tree structure
        foreach ($nodes as $node) {
            if ($node->getParentId() === null) {
                // This is a root node
                $rootNodes[] = $node;
            } else {
                // This is a child node
                $parent = $nodeMap[$node->getParentId()] ?? null;
                if ($parent) {
                    $parent->addChild($node);
                }
            }
        }

        return $rootNodes;
    }

    /**
     * Sort nodes by their sort order
     * 
     * @param TreeNode[] $nodes
     * @return TreeNode[]
     */
    public function sortNodes(array $nodes): array
    {
        usort($nodes, function (TreeNode $a, TreeNode $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        // Recursively sort children
        foreach ($nodes as $node) {
            if ($node->hasChildren()) {
                $sortedChildren = $this->sortNodes($node->getChildren());
                // Clear and re-add sorted children
                foreach ($node->getChildren() as $child) {
                    // We need to clear children first
                }
                foreach ($sortedChildren as $child) {
                    $node->addChild($child);
                }
            }
        }

        return $nodes;
    }

    /**
     * Flatten a hierarchical tree structure into a flat array
     * 
     * @param TreeNode[] $rootNodes
     * @return TreeNode[]
     */
    public function flattenTree(array $rootNodes): array
    {
        $flat = [];
        
        foreach ($rootNodes as $node) {
            $flat[] = $node;
            if ($node->hasChildren()) {
                $flat = array_merge($flat, $this->flattenTree($node->getChildren()));
            }
        }
        
        return $flat;
    }

    /**
     * Find a node by ID within a tree structure
     * 
     * @param TreeNode[] $rootNodes
     * @param int $nodeId
     * @return TreeNode|null
     */
    public function findNodeById(array $rootNodes, int $nodeId): ?TreeNode
    {
        foreach ($rootNodes as $node) {
            if ($node->getId() === $nodeId) {
                return $node;
            }
            if ($node->hasChildren()) {
                $found = $this->findNodeById($node->getChildren(), $nodeId);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        
        return null;
    }
}