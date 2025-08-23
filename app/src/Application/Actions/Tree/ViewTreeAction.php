<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\AbstractHtmlAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Rendering\HtmlRendererInterface;
use App\Infrastructure\Services\TreeStructureBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreeAction extends AbstractHtmlAction
{
    public function __construct(
        LoggerInterface $logger,
        HtmlRendererInterface $htmlRenderer,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository,
        private TreeStructureBuilder $treeStructureBuilder
    ) {
        parent::__construct($logger, $htmlRenderer);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            // Get the first active tree from the database
            $trees = $this->treeRepository->findActive();

            if (empty($trees)) {
                $html = $this->htmlRenderer->renderNoTrees();
                return $this->respondWithHtml($html);
            }

            $tree = $trees[0]; // Use the first active tree
            $treeId = $tree->getId();

            // Get all nodes for this tree
            $nodes = $this->treeNodeRepository->findByTreeId($treeId);

            if (empty($nodes)) {
                $html = $this->htmlRenderer->renderEmptyTree($tree);
                return $this->respondWithHtml($html);
            }

            // Build the tree structure from database nodes
            $rootNodes = $this->treeStructureBuilder->buildTreeFromNodes($nodes);

            if (empty($rootNodes)) {
                $html = $this->htmlRenderer->renderNoRootNodes($tree);
                return $this->respondWithHtml($html);
            }

            $html = $this->htmlRenderer->renderTreeView($tree, $rootNodes);
            return $this->respondWithHtml($html);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Error Loading Tree');
        }
    }
}
