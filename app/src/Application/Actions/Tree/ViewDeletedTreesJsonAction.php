<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewDeletedTreesJsonAction extends Action
{
    public function __construct(LoggerInterface $logger, private TreeRepository $treeRepository)
    {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            $request = $this->request;
            $method = $request->getMethod();
            if ($method !== 'GET') {
                return $this->respondWithError('Method not allowed. Must be GET.', 405);
            }

            $deletedTrees = $this->treeRepository->findDeleted();
            return $this->respondWithSuccess($deletedTrees);
        } catch (\Exception $e) {
            $this->logger->error('Error in view deleted trees JSON action: ' . $e->getMessage());
            return $this->respondWithError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function respondWithSuccess(array $deletedTrees): Response
    {
        $treesData = [];
        foreach ($deletedTrees as $tree) {
            $treesData[] = [
                'id' => $tree->getId(),
                'name' => $tree->getName(),
                'description' => $tree->getDescription(),
                'is_active' => $tree->isActive(),
                'created_at' => $tree->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $tree->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $data = [
            'success' => true,
            'message' => 'Deleted trees retrieved successfully.',
            'stats' => [
                'total_deleted_trees' => count($deletedTrees)
            ],
            'trees' => $treesData,
            'links' => [
                'back_to_active_trees' => '/trees/json',
                'view_active_trees_html' => '/trees'
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
