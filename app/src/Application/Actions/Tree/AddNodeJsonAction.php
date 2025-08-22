<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use DateTime;

final class AddNodeJsonAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');
            $tree = $this->treeRepository->findById($treeId);

            if (!$tree) {
                return $this->respondWithError('Tree not found', [
                    'tree_id' => $treeId,
                    'message' => "Tree with ID {$treeId} was not found in the database"
                ]);
            }

            $parsedBody = $this->request->getParsedBody();

            if (!$parsedBody) {
                return $this->respondWithError('Invalid JSON data provided');
            }

            // Validate required fields
            $name = trim($parsedBody['name'] ?? '');
            if (empty($name)) {
                return $this->respondWithError('Node name is required');
            }

            if (strlen($name) > 255) {
                return $this->respondWithError('Node name must be 255 characters or less');
            }

            $parentId = !empty($parsedBody['parent_id']) ? (int) $parsedBody['parent_id'] : null;
            $nodeType = $parsedBody['node_type'] ?? 'SimpleNode';
            $sortOrder = (int) ($parsedBody['sort_order'] ?? 0);

            // Validate parent node if specified
            if ($parentId !== null) {
                $parentNode = $this->treeNodeRepository->findById($parentId);
                if (!$parentNode || $parentNode->getTreeId() !== $treeId) {
                    return $this->respondWithError('Invalid parent node selected');
                }
            }

            // Create node based on type
            if ($nodeType === 'ButtonNode') {
                $buttonText = trim($parsedBody['button_text'] ?? '');
                $buttonAction = trim($parsedBody['button_action'] ?? '');

                if (empty($buttonText)) {
                    return $this->respondWithError('Button text is required for ButtonNode');
                }

                if (strlen($buttonText) > 100) {
                    return $this->respondWithError('Button text must be 100 characters or less');
                }

                if (strlen($buttonAction) > 255) {
                    return $this->respondWithError('Button action must be 255 characters or less');
                }

                $node = new ButtonNode(
                    null,
                    $name,
                    $treeId,
                    $parentId,
                    $sortOrder
                );

                // Set button properties
                $node->setButtonText($buttonText);
                if (!empty($buttonAction)) {
                    $node->setButtonAction($buttonAction);
                }
            } else {
                $node = new SimpleNode(
                    null,
                    $name,
                    $treeId,
                    $parentId,
                    $sortOrder
                );
            }

            // Save the node
            $this->treeNodeRepository->save($node);

            // Return success response with node details
            $response = [
                'success' => true,
                'message' => 'Node created successfully',
                'node' => [
                    'id' => $node->getId(),
                    'name' => $node->getName(),
                    'type' => $node->getType(),
                    'tree_id' => $node->getTreeId(),
                    'parent_id' => $node->getParentId(),
                    'sort_order' => $node->getSortOrder(),
                    'type_data' => $node->getTypeData()
                ],
                'tree' => [
                    'id' => $tree->getId(),
                    'name' => $tree->getName(),
                    'description' => $tree->getDescription()
                ],
                'links' => [
                    'view_tree' => "/tree/{$treeId}",
                    'view_tree_json' => "/tree/{$treeId}/json",
                    'add_another_node' => "/tree/{$treeId}/add-node"
                ]
            ];

            $json = json_encode($response, JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new \RuntimeException('Failed to encode JSON response');
            }
            $this->response->getBody()->write($json);
            return $this->response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error('Error creating node via JSON: ' . $e->getMessage());
            return $this->respondWithError('An error occurred while creating the node: ' . $e->getMessage());
        }
    }

    private function respondWithError(string $message, array $details = []): Response
    {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'details' => $details
            ]
        ];

        $json = json_encode($response, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON response');
        }
        $this->response->getBody()->write($json);
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}
