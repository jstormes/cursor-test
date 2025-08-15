<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreeAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeNodeRepository $treeNodeRepository
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
                return $this->generateNoTreesHTML();
            }

            $tree = $trees[0]; // Use the first active tree
            $treeId = $tree->getId();

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
            foreach ($rootNodes as $rootNode) {
                $treeHtml .= '<li>' . $renderer->render($rootNode) . '</li>';
            }
            $treeHtml .= '</ul></div>';

            $html = $this->generateHTML($treeHtml, $tree);

            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error loading tree: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
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

    private function generateHTML(string $treeHtml, $tree): string
    {
        $css = $this->getCSS();
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
        <a href="/tree/json" class="btn btn-primary">View JSON</a>
    </div>
    
    {$treeHtml}
</body>
</html>
HTML;
    }

    private function generateNoTreesHTML(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Trees Available</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .message { color: #666; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>No Trees Available</h1>
    <p class="message">No active trees found in the database.</p>
    <a href="/trees" class="btn">Go to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateNoNodesHTML($tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empty Tree - {$treeName}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .message { color: #666; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 0 10px; }
    </style>
</head>
<body>
    <h1>Empty Tree: {$treeName}</h1>
    <p class="message">This tree has no nodes yet.</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateNoRootNodesHTML($tree): Response
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

    private function generateErrorHTML(string $errorMessage): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Loading Tree</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Error Loading Tree</h1>
    <p class="error">{$this->escapeHtml($errorMessage)}</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private function getCSS(): string
    {
        return <<<CSS
.header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    margin: 20px;
}

.header h1 {
    margin: 0 0 10px 0;
    font-size: 2em;
}

.description {
    margin: 0 0 15px 0;
    font-size: 1.1em;
    opacity: 0.9;
}

.tree-info {
    display: flex;
    justify-content: center;
    gap: 20px;
    font-size: 0.9em;
    opacity: 0.8;
}

.navigation {
    text-align: center;
    margin: 20px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 10px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

.tree ul {
    padding-top: 20px; position: relative;
    
    transition: all 0.5s;
    -webkit-transition: all 0.5s;
    -moz-transition: all 0.5s;
}

.tree li {
    float: left; text-align: center;
    list-style-type: none;
    position: relative;
    padding: 20px 5px 0 5px;
    
    transition: all 0.5s;
    -webkit-transition: all 0.5s;
    -moz-transition: all 0.5s;
}

.tree li::before, .tree li::after{
    content: '';
    position: absolute; top: 0; right: 50%;
    border-top: 1px solid #ccc;
    width: 50%; height: 20px;
}
.tree li::after{
    right: auto; left: 50%;
    border-left: 1px solid #ccc;
}

.tree li:only-child::after, .tree li:only-child::before {
    display: none;
}

.tree li:only-child{ padding-top: 0;}

.tree li:first-child::before, .tree li:last-child::after{
    border: 0 none;
}
.tree li:last-child::before{
    border-right: 1px solid #ccc;
    border-radius: 0 5px 0 0;
    -webkit-border-radius: 0 5px 0 0;
    -moz-border-radius: 0 5px 0 0;
}
.tree li:first-child::after{
    border-radius: 5px 0 0 0;
    -webkit-border-radius: 5px 0 0 0;
    -moz-border-radius: 5px 0 0 0;
}
.tree ul ul::before{
    content: '';
    position: absolute; top: 0; left: 50%;
    border-left: 1px solid #ccc;
    width: 0; height: 20px;
}
.tree li div{
    border: 1px solid #1e3a8a;
    padding: 15px 10px 15px 10px;
    text-decoration: none;
    color: #1e3a8a;
    background-color: #ffffff;
    font-family: arial, verdana, tahoma;
    font-size: 11px;
    display: inline-block;
    position: relative;
    
    border-radius: 5px;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    
    transition: all 0.5s;
    -webkit-transition: all 0.5s;
    -moz-transition: all 0.5s;
}

.tree li div .add-icon {
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #1e3a8a;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    pointer-events: auto;
    z-index: 10;
    transition: all 0.3s;
    -webkit-transition: all 0.3s;
    -moz-transition: all 0.3s;
}

.tree li div .add-icon:hover {
    background-color: #0f172a;
    transform: translateX(-50%) scale(1.1);
}

.tree li div .remove-icon {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    border-radius: 5px;
    background-color: #dc3545;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    pointer-events: auto;
    z-index: 10;
    transition: all 0.3s;
    -webkit-transition: all 0.3s;
    -moz-transition: all 0.3s;
}

.tree li div .remove-icon:hover {
    background-color: #a71e2a;
    transform: translateX(-50%) scale(1.1);
}
.tree li div:hover, .tree li div:hover+ul li div {
    background: #1e3a8a; color: #ffffff; border: 1px solid #1e3a8a;
}

.tree li div:hover .add-icon {
    background-color: #ffffff;
    color: #1e3a8a;
}

.tree li div:hover .remove-icon {
    background-color: #ffffff;
    color: #dc3545;
}

.tree li div input[type="checkbox"] {
    margin: 0 4px 0 0;
    transform: scale(1.1);
    accent-color: #1e3a8a;
    vertical-align: middle;
}

.tree li div button {
    margin-top: 8px;
    padding: 4px 8px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.tree li div button:hover {
    background-color: #5a6268;
}


.tree li div:hover+ul li::after, 
.tree li div:hover+ul li::before, 
.tree li div:hover+ul::before, 
.tree li div:hover+ul ul::before{
    border-color:  #94a0b4;
}

body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .tree-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .header h1 {
        font-size: 1.5em;
    }
}
CSS;
    }
}
