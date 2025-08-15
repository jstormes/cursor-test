<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use DateTime;

class AddTreeJsonAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            $parsedBody = $this->request->getParsedBody();

            if (!$parsedBody) {
                return $this->respondWithError('Invalid JSON data provided');
            }

            // Validate required fields
            $name = trim($parsedBody['name'] ?? '');
            if (empty($name)) {
                return $this->respondWithError('Tree name is required');
            }

            if (strlen($name) > 255) {
                return $this->respondWithError('Tree name must be 255 characters or less');
            }

            $description = trim($parsedBody['description'] ?? '');
            if (strlen($description) > 1000) {
                return $this->respondWithError('Description must be 1000 characters or less');
            }

            // Check if tree name already exists
            $existingTrees = $this->treeRepository->findActive();
            foreach ($existingTrees as $existingTree) {
                if (strtolower($existingTree->getName()) === strtolower($name)) {
                    return $this->respondWithError('A tree with this name already exists');
                }
            }

            // Create the tree
            $tree = new Tree(
                null,
                $name,
                $description ?: null
            );

            // Save the tree
            $this->treeRepository->save($tree);

            // Return success response with tree details
            $response = [
                'success' => true,
                'message' => 'Tree created successfully',
                'tree' => [
                    'id' => $tree->getId(),
                    'name' => $tree->getName(),
                    'description' => $tree->getDescription(),
                    'created_at' => $tree->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $tree->getUpdatedAt()->format('Y-m-d H:i:s')
                ],
                'links' => [
                    'view_tree' => "/tree/{$tree->getId()}",
                    'view_tree_json' => "/tree/{$tree->getId()}/json",
                    'add_node' => "/tree/{$tree->getId()}/add-node",
                    'add_node_json' => "/tree/{$tree->getId()}/add-node/json",
                    'view_trees' => "/trees"
                ]
            ];

            $this->response->getBody()->write(json_encode($response, JSON_PRETTY_PRINT));
            return $this->response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error('Error creating tree via JSON: ' . $e->getMessage());
            return $this->respondWithError('An error occurred while creating the tree: ' . $e->getMessage());
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

        $this->response->getBody()->write(json_encode($response, JSON_PRETTY_PRINT));
        return $this->response->withHeader('Content-Type', 'application/json');
    }
}
