<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use App\Infrastructure\Rendering\CssProviderInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreeByIdAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository,
        private CssProviderInterface $cssProvider
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            // Get the tree ID from the route parameters
            $treeId = (int) $this->resolveArg('id');

            // Get the specific tree from the database
            $tree = $this->treeRepository->findById($treeId);

            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            // Get all nodes for this tree
            $nodes = $this->treeNodeRepository->findByTreeId($treeId);

            if (empty($nodes)) {
                return $this->generateNoNodesHTML($tree);
            }

            // Build the tree structure from database nodes
            $rootNodes = $this->buildTreeFromNodes($nodes);

            if (empty($rootNodes)) {
                return $this->generateNoRootNodesHTML($tree);
            }

            // Generate HTML using the renderer
            $renderer = new HtmlTreeNodeRenderer(true);
            $treeHtml = '<div class="tree"><ul>';

            // Add top-level add icon
            $treeHtml .= '<li><div class="tree-node-no-box"><a href="/tree/' . $treeId . '/add-node" class="add-icon">+</a></div>';
            $treeHtml .= '<ul>';

            foreach ($rootNodes as $rootNode) {
                $treeHtml .= '<li>' . $renderer->render($rootNode) . '</li>';
            }

            $treeHtml .= '</ul></li>';
            $treeHtml .= '</ul></div>';

            $html = $this->generateHTML($treeHtml, $tree);

            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error loading tree by ID: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage(), 'Error Loading Tree');
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

    private function generateHTML(string $treeHtml, \App\Domain\Tree\Tree $tree): string
    {
        $mainCSS = $this->cssProvider->getMainCSS();
        $treeCSS = $this->cssProvider->getTreeCSS('edit');
        $css = $mainCSS . "\n\n" . $treeCSS;

        $treeName = htmlspecialchars($tree->getName());
        $treeDescription = htmlspecialchars($tree->getDescription() ?: 'No description available');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Structure - {$treeName}</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="header">
        <h1>Tree Structure: {$treeName}</h1>
        <p class="description">{$treeDescription}</p>
        <div class="tree-info">
            <span class="tree-id">Tree ID: {$tree->getId()}</span>
            <span class="created">Created: {$tree->getCreatedAt()->format('M j, Y g:i A')}</span>
        </div>
    </div>
    
    <div class="navigation">
        <a href="/trees" class="btn btn-secondary">← Back to Trees List</a>
        <a href="/tree/{$tree->getId()}/add-node" class="btn btn-primary">➕ Add Node</a>
        <a href="/tree/{$tree->getId()}/json" class="btn btn-secondary">View JSON</a>
    </div>
    
    {$treeHtml}
</body>
</html>
HTML;
    }


    private function generateNoNodesHTML(\App\Domain\Tree\Tree $tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $mainCSS = $this->cssProvider->getMainCSS();
        $treeCSS = $this->cssProvider->getTreeCSS('edit');
        $css = $mainCSS . "\n\n" . $treeCSS;

        $treeHtml = '<div class="tree"><ul>';
        $treeHtml .= '<li><div class="tree-node-no-box"><a href="/tree/' . $treeId . '/add-node" class="add-icon">+</a></div></li>';
        $treeHtml .= '</ul></div>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Structure - {$treeName}</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="header">
        <h1>Tree Structure: {$treeName}</h1>
        <p class="description">Empty tree - add your first node</p>
        <div class="tree-info">
            <span class="tree-id">Tree ID: {$tree->getId()}</span>
            <span class="created">Created: {$tree->getCreatedAt()->format('M j, Y g:i A')}</span>
        </div>
    </div>
    
    <div class="navigation">
        <a href="/trees" class="btn btn-secondary">← Back to Trees List</a>
        <a href="/tree/{$tree->getId()}/add-node" class="btn btn-primary">➕ Add Node</a>
        <a href="/tree/{$tree->getId()}/json" class="btn btn-secondary">View JSON</a>
    </div>
    
    {$treeHtml}
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateNoRootNodesHTML(\App\Domain\Tree\Tree $tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Tree Structure - {$treeName}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .message { color: #666; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 0 10px; }
    </style>
</head>
<body>
    <h1>Invalid Tree Structure: {$treeName}</h1>
    <p class="message">This tree has nodes but no root nodes found.</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }
}
