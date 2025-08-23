<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\AbstractJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\TreeNode;
use App\Infrastructure\Services\TreeStructureBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreeByIdJsonAction extends AbstractJsonAction
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository,
        private TreeStructureBuilder $treeStructureBuilder
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            // Get the tree ID from the route parameters
            $treeId = (int) $this->resolveArg('id');

            // Get the specific tree from the database
            $tree = $this->treeRepository->findById($treeId);

            if (!$tree) {
                return $this->respondWithError('Tree not found', [
                    'tree_id' => $treeId,
                    'message' => "Tree with ID {$treeId} was not found in the database"
                ]);
            }

            // Get all nodes for this tree
            $nodes = $this->treeNodeRepository->findByTreeId($treeId);

            if (empty($nodes)) {
                return $this->respondWithError('No nodes found for this tree', [
                    'tree_id' => $treeId,
                    'tree_name' => $tree->getName()
                ]);
            }

            // Build the tree structure from database nodes
            $rootNodes = $this->treeStructureBuilder->buildTreeFromNodes($nodes);

            if (empty($rootNodes)) {
                return $this->respondWithError('Invalid tree structure - no root nodes found', [
                    'tree_id' => $treeId,
                    'tree_name' => $tree->getName(),
                    'total_nodes' => count($nodes)
                ]);
            }

            // Convert tree to JSON structure
            $treeData = [];
            foreach ($rootNodes as $rootNode) {
                $treeData[] = $this->convertTreeToArray($rootNode);
            }

            $response = [
                'success' => true,
                'message' => 'Tree structure retrieved successfully',
                'data' => [
                    'tree' => [
                        'id' => $tree->getId(),
                        'name' => $tree->getName(),
                        'description' => $tree->getDescription(),
                        'created_at' => $tree->getCreatedAt()->format('Y-m-d H:i:s'),
                        'updated_at' => $tree->getUpdatedAt()->format('Y-m-d H:i:s'),
                        'is_active' => $tree->isActive(),
                        'root_nodes' => $treeData
                    ],
                    'total_nodes' => $this->countNodes($rootNodes),
                    'total_levels' => $this->getMaxDepth($rootNodes),
                    'total_root_nodes' => count($rootNodes)
                ]
            ];

            return $this->respondWithJson($response);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Error loading tree structure');
        }
    }

    private function convertTreeToArray(TreeNode $node): array
    {
        $nodeData = [
            'id' => $node->getId(),
            'name' => $node->getName(),
            'type' => $node->getType(),
            'tree_id' => $node->getTreeId(),
            'parent_id' => $node->getParentId(),
            'sort_order' => $node->getSortOrder(),
            'has_children' => $node->hasChildren(),
            'children_count' => count($node->getChildren()),
            'type_data' => $node->getTypeData()
        ];

        // Add button-specific data if it's a ButtonNode
        if ($node->getType() === 'ButtonNode') {
            $typeData = $node->getTypeData();
            $nodeData['button'] = [
                'text' => $typeData['button_text'] ?? 'Click',
                'action' => $typeData['button_action'] ?? ''
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

    private function countNodes(array $rootNodes): int
    {
        $count = 0;
        foreach ($rootNodes as $node) {
            $count += $this->countNodesRecursive($node);
        }
        return $count;
    }

    private function countNodesRecursive(TreeNode $node): int
    {
        $count = 1; // Count this node
        foreach ($node->getChildren() as $child) {
            $count += $this->countNodesRecursive($child);
        }
        return $count;
    }

    private function getMaxDepth(array $rootNodes): int
    {
        $maxDepth = 0;
        foreach ($rootNodes as $node) {
            $depth = $this->getMaxDepthRecursive($node, 0);
            $maxDepth = max($maxDepth, $depth);
        }
        return $maxDepth;
    }

    private function getMaxDepthRecursive(TreeNode $node, int $currentDepth = 0): int
    {
        if (!$node->hasChildren()) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth;
        foreach ($node->getChildren() as $child) {
            $childDepth = $this->getMaxDepthRecursive($child, $currentDepth + 1);
            $maxDepth = max($maxDepth, $childDepth);
        }

        return $maxDepth;
    }

    private function respondWithError(string $message, array $additionalData = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => true
        ];

        if (!empty($additionalData)) {
            $response['data'] = $additionalData;
        }

        $this->response->getBody()->write(json_encode($response, JSON_PRETTY_PRINT));
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}
