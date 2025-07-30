<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\TreeNode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViewTreeJsonAction extends Action
{
    protected function action(): Response
    {
        // Build the tree structure using the same hierarchy as HTML view
        $main = new ButtonNode('Main', 'Test Btn'); // Main has a button
        
        $sub1 = new SimpleNode('Sub-1');
        $sub2 = new SimpleNode('Sub-2');
        
        $sub21 = new SimpleNode('Sub-2-1');
        $sub22 = new SimpleNode('Sub-2-2');
        
        // Add children to Sub-2
        $sub2->addChild($sub21);
        $sub2->addChild($sub22);
        
        // Add children to Main
        $main->addChild($sub1);
        $main->addChild($sub2);
        
        // Convert tree to JSON structure
        $treeData = $this->convertTreeToArray($main);
        
        $response = [
            'success' => true,
            'message' => 'Tree structure retrieved successfully',
            'data' => [
                'tree' => $treeData,
                'total_nodes' => $this->countNodes($main),
                'total_levels' => $this->getMaxDepth($main)
            ]
        ];
        
        $this->response->getBody()->write(json_encode($response, JSON_PRETTY_PRINT));
        return $this->response->withHeader('Content-Type', 'application/json');
    }
    
    private function convertTreeToArray(TreeNode $node): array
    {
        $nodeData = [
            'id' => $this->generateNodeId($node),
            'name' => $node->getName(),
            'type' => $node->getType(),
            'has_children' => $node->hasChildren(),
            'children_count' => count($node->getChildren())
        ];
        
        // Add button-specific data if it's a ButtonNode
        if ($node instanceof ButtonNode) {
            $nodeData['button'] = [
                'text' => $node->getButtonText(),
                'action' => $node->getButtonAction()
            ];
        }
        
        // Add children recursively
        if ($node->hasChildren()) {
            $nodeData['children'] = [];
            foreach ($node->getChildren() as $child) {
                $nodeData['children'][] = $this->convertTreeToArray($child);
            }
        }
        
        return $nodeData;
    }
    
    private function generateNodeId(TreeNode $node): string
    {
        // Simple ID generation based on node name and type
        return strtolower(str_replace([' ', '-'], ['_', '_'], $node->getName())) . '_' . $node->getType();
    }
    
    private function countNodes(TreeNode $node): int
    {
        $count = 1; // Count this node
        foreach ($node->getChildren() as $child) {
            $count += $this->countNodes($child);
        }
        return $count;
    }
    
    private function getMaxDepth(TreeNode $node, int $currentDepth = 0): int
    {
        if (!$node->hasChildren()) {
            return $currentDepth;
        }
        
        $maxDepth = $currentDepth;
        foreach ($node->getChildren() as $child) {
            $childDepth = $this->getMaxDepth($child, $currentDepth + 1);
            $maxDepth = max($maxDepth, $childDepth);
        }
        
        return $maxDepth;
    }
} 