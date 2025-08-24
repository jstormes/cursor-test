<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Application\Services\TreeService;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

final class BulkNodeSortApiAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository,
        private TreeService $treeService
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');

            // Verify tree exists
            $tree = $this->treeRepository->findById($treeId);
            if (!$tree) {
                return $this->respondWithData([
                    'error' => 'Tree not found',
                    'treeId' => $treeId
                ], 404);
            }

            // Parse request body
            $parsedBody = $this->request->getParsedBody();
            if (!is_array($parsedBody) || !isset($parsedBody['updates']) || !is_array($parsedBody['updates'])) {
                throw new HttpBadRequestException($this->request, 'Invalid JSON body. Expected {"updates": [...]}');
            }

            $updates = $parsedBody['updates'];
            if (empty($updates)) {
                throw new HttpBadRequestException($this->request, 'Updates array cannot be empty');
            }

            // Validate each update entry
            $validatedUpdates = [];
            foreach ($updates as $update) {
                if (!is_array($update) || !isset($update['nodeId']) || !isset($update['sortOrder'])) {
                    throw new HttpBadRequestException($this->request, 'Each update must have nodeId and sortOrder');
                }

                $nodeId = (int) $update['nodeId'];
                $sortOrder = (int) $update['sortOrder'];

                if ($sortOrder < 0) {
                    throw new HttpBadRequestException($this->request, 'Sort order must be non-negative');
                }

                // Verify node exists and belongs to the tree
                $node = $this->treeNodeRepository->findById($nodeId);
                if (!$node || $node->getTreeId() !== $treeId) {
                    return $this->respondWithData([
                        'error' => 'Node not found in specified tree',
                        'treeId' => $treeId,
                        'nodeId' => $nodeId
                    ], 404);
                }

                $validatedUpdates[] = [
                    'nodeId' => $nodeId,
                    'sortOrder' => $sortOrder
                ];
            }

            // Perform bulk update
            $this->treeService->bulkUpdateSortOrders($validatedUpdates);

            // Return success response
            return $this->respondWithData([
                'success' => true,
                'message' => 'Bulk sort order update completed successfully',
                'updatedCount' => count($validatedUpdates)
            ]);

        } catch (HttpBadRequestException $e) {
            return $this->respondWithData([
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Error in BulkNodeSortApiAction: ' . $e->getMessage());
            return $this->respondWithData([
                'error' => 'Internal server error'
            ], 500);
        }
    }
}