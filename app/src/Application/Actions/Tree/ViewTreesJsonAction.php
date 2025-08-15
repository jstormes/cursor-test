<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreesJsonAction extends Action
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

            $activeTrees = $this->treeRepository->findActive();
            return $this->respondWithSuccess($activeTrees);
        } catch (\Exception $e) {
            $this->logger->error('Error in view trees JSON action: ' . $e->getMessage());
            return $this->respondWithError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function respondWithSuccess(array $activeTrees): Response
    {
        $treesData = [];
        foreach ($activeTrees as $tree) {
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
            'message' => 'Active trees retrieved successfully.',
            'stats' => [
                'total_active_trees' => count($activeTrees)
            ],
            'trees' => $treesData,
            'links' => [
                'view_deleted_trees' => '/trees/deleted/json',
                'view_trees_html' => '/trees',
                'add_new_tree' => '/tree/add'
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
