<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Rendering\HtmlRendererInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreeAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository,
        private HtmlRendererInterface $htmlRenderer
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            // Get the first active tree from the database
            $trees = $this->treeRepository->findActive();

            if (empty($trees)) {
                $html = $this->htmlRenderer->renderNoTrees();
                $this->response->getBody()->write($html);
                return $this->response->withHeader('Content-Type', 'text/html');
            }

            $tree = $trees[0]; // Use the first active tree
            $treeId = $tree->getId();

            // Get all nodes for this tree
            $nodes = $this->treeNodeRepository->findByTreeId($treeId);

            if (empty($nodes)) {
                $html = $this->htmlRenderer->renderEmptyTree($tree);
                $this->response->getBody()->write($html);
                return $this->response->withHeader('Content-Type', 'text/html');
            }

            // Build the tree structure from database nodes
            $rootNodes = $this->buildTreeFromNodes($nodes);

            if (empty($rootNodes)) {
                $html = $this->htmlRenderer->renderNoRootNodes($tree);
                $this->response->getBody()->write($html);
                return $this->response->withHeader('Content-Type', 'text/html');
            }

            $html = $this->htmlRenderer->renderTreeView($tree, $rootNodes);
            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error loading tree: ' . $e->getMessage());
            $html = $this->htmlRenderer->renderError($e->getMessage(), 'Error Loading Tree');
            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        }
    }

    private function buildTreeFromNodes(array $nodes): array
    {
        $nodeMap = [];
        $rootNodes = [];

        // Create a map of all nodes by ID
        foreach ($nodes as $node) {
            $nodeMap[$node->getId()] = $node;
        }

        // Build the tree structure
        foreach ($nodes as $node) {
            if ($node->getParentId() === null) {
                // This is a root node
                $rootNodes[] = $node;
            } else {
                // This is a child node
                $parent = $nodeMap[$node->getParentId()] ?? null;
                if ($parent) {
                    $parent->addChild($node);
                }
            }
        }

        return $rootNodes;
    }
}
