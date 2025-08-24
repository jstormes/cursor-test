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

final class NodeSortApiAction extends Action
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
            $nodeId = (int) $this->resolveArg('nodeId');

            // Verify tree exists
            $tree = $this->treeRepository->findById($treeId);
            if (!$tree) {
                return $this->respondWithData([
                    'error' => 'Tree not found',
                    'treeId' => $treeId
                ], 404);
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

            // Parse request body
            $parsedBody = $this->request->getParsedBody();
            if (!is_array($parsedBody)) {
                throw new HttpBadRequestException($this->request, 'Invalid JSON body');
            }

            // Handle direction-based sorting
            if (isset($parsedBody['direction'])) {
                $direction = trim((string) $parsedBody['direction']);
                
                switch ($direction) {
                    case 'left':
                        $this->treeService->sortNodeLeft($nodeId);
                        break;
                    case 'right':
                        $this->treeService->sortNodeRight($nodeId);
                        break;
                    default:
                        throw new HttpBadRequestException($this->request, 'Invalid direction. Must be "left" or "right"');
                }
            } elseif (isset($parsedBody['sortOrder'])) {
                // Handle direct sort order update
                $sortOrder = (int) $parsedBody['sortOrder'];
                if ($sortOrder < 0) {
                    throw new HttpBadRequestException($this->request, 'Sort order must be non-negative');
                }
                
                $this->treeService->updateNodeSortOrder($nodeId, $sortOrder);
            } else {
                throw new HttpBadRequestException($this->request, 'Either "direction" or "sortOrder" must be specified');
            }

            // Return updated node data
            $updatedNode = $this->treeNodeRepository->findById($nodeId);
            
            return $this->respondWithData([
                'success' => true,
                'message' => 'Node sort order updated successfully',
                'node' => [
                    'id' => $updatedNode->getId(),
                    'name' => $updatedNode->getName(),
                    'treeId' => $updatedNode->getTreeId(),
                    'parentId' => $updatedNode->getParentId(),
                    'sortOrder' => $updatedNode->getSortOrder(),
                    'type' => $updatedNode->getType()
                ]
            ]);

        } catch (HttpBadRequestException $e) {
            return $this->respondWithData([
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Error in NodeSortApiAction: ' . $e->getMessage());
            return $this->respondWithData([
                'error' => 'Internal server error'
            ], 500);
        }
    }
}