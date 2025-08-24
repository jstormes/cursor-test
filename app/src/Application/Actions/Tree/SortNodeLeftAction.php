<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Application\Services\TreeService;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

final class SortNodeLeftAction extends Action
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
        $treeId = (int) $this->resolveArg('treeId');
        $nodeId = (int) $this->resolveArg('nodeId');

        try {
            $tree = $this->treeRepository->findById($treeId);
            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            $node = $this->treeNodeRepository->findById($nodeId);
            if (!$node || $node->getTreeId() !== $treeId) {
                return $this->generateNodeNotFoundHTML($treeId, $nodeId);
            }

            // Perform sort left operation
            $this->treeService->sortNodeLeft($nodeId);

            // Redirect back to tree view
            $redirectUrl = "/tree/{$treeId}";
            return $this->response
                ->withStatus(302)
                ->withHeader('Location', $redirectUrl);

        } catch (\Exception $e) {
            $this->logger->error('Error sorting node left: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage(), 'Error Sorting Node', "/tree/{$treeId}");
        }
    }
}