<?php
declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class RestoreTreeJsonAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        try {
            $request = $this->request;
            $method = $request->getMethod();
            
            if ($method !== 'POST') {
                return $this->respondWithError('Method not allowed. Must be POST.', 405);
            }

            $treeId = (int) $this->resolveArg('id');
            $tree = $this->treeRepository->findById($treeId);
            
            if (!$tree) {
                return $this->respondWithError("Tree with ID {$treeId} was not found.", 404);
            }
            
            if ($tree->isActive()) {
                return $this->respondWithError("Tree '{$tree->getName()}' is already active.", 400);
            }

            // Perform restore
            $this->treeRepository->restore($treeId);
            
            return $this->respondWithSuccess($tree);
            
        } catch (\Exception $e) {
            $this->logger->error('Error in restore tree JSON action: ' . $e->getMessage());
            return $this->respondWithError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function respondWithSuccess($tree): Response
    {
        $data = [
            'success' => true,
            'message' => "Tree '{$tree->getName()}' has been successfully restored.",
            'tree' => [
                'id' => $tree->getId(),
                'name' => $tree->getName(),
                'description' => $tree->getDescription(),
                'is_active' => true,
                'created_at' => $tree->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $tree->getUpdatedAt()->format('Y-m-d H:i:s')
            ],
            'links' => [
                'view_tree' => "/tree/{$tree->getId()}/json",
                'back_to_trees' => '/trees/json',
                'view_deleted_trees' => '/trees/deleted/json'
            ]
        ];

        $this->response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    private function respondWithError(string $message, int $statusCode = 400): Response
    {
        $data = [
            'success' => false,
            'error' => [
                'message' => $message,
                'status_code' => $statusCode
            ]
        ];

        $this->response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
} 