<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\AbstractTreeNode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class DeleteNodeAction extends Action
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
        $request = $this->request;
        $method = $request->getMethod();

        if ($method === 'GET') {
            return $this->showConfirmationForm();
        } elseif ($method === 'POST') {
            return $this->handleDeletion();
        }

        return $this->response->withStatus(405);
    }

    private function showConfirmationForm(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');
            $nodeId = (int) $this->resolveArg('nodeId');

            $tree = $this->treeRepository->findById($treeId);
            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            $node = $this->treeNodeRepository->findById($nodeId);
            if (!$node || $node->getTreeId() !== $treeId) {
                return $this->generateNodeNotFoundHTML($treeId, $nodeId);
            }

            // Get all descendants that will be deleted
            $descendants = $this->getAllDescendants($nodeId);

            $html = $this->generateConfirmationHTML($tree, $node, $descendants);
            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error showing delete confirmation: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function handleDeletion(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');
            $nodeId = (int) $this->resolveArg('nodeId');

            $tree = $this->treeRepository->findById($treeId);
            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            $node = $this->treeNodeRepository->findById($nodeId);
            if (!$node || $node->getTreeId() !== $treeId) {
                return $this->generateNodeNotFoundHTML($treeId, $nodeId);
            }

            // Delete the node and all its descendants
            $this->deleteNodeAndDescendants($nodeId);

            return $this->generateSuccessHTML($tree, $node);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting node: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function getAllDescendants(int $nodeId): array
    {
        $descendants = [];
        $directChildren = $this->treeNodeRepository->findChildren($nodeId);

        foreach ($directChildren as $child) {
            $descendants[] = $child;
            // Recursively get descendants of this child
            $childDescendants = $this->getAllDescendants($child->getId());
            $descendants = array_merge($descendants, $childDescendants);
        }

        return $descendants;
    }

    private function deleteNodeAndDescendants(int $nodeId): void
    {
        // First delete all descendants
        $descendants = $this->getAllDescendants($nodeId);
        foreach ($descendants as $descendant) {
            $this->treeNodeRepository->delete($descendant->getId());
        }

        // Then delete the node itself
        $this->treeNodeRepository->delete($nodeId);
    }

    private function generateConfirmationHTML(Tree $tree, AbstractTreeNode $node, array $descendants): string
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $nodeName = htmlspecialchars($node->getName());
        $node->getId();
        $descendantCount = count($descendants);

        $descendantsList = '';
        if (!empty($descendants)) {
            $descendantsList = '<h3>The following child nodes will also be deleted:</h3><ul class="descendants-list">';
            foreach ($descendants as $descendant) {
                $descendantsList .= '<li>' . htmlspecialchars($descendant->getName()) .
                                    ' (' . $descendant->getType() . ')</li>';
            }
            $descendantsList .= '</ul>';
        }

        $css = $this->getCSS();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Node - {$nodeName}</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Delete Node</h1>
            <p class="description">Confirm deletion of "{$nodeName}" from "{$treeName}"</p>
        </div>
        
        <div class="form-container">
            <div class="warning-message">
                <h2>⚠️ Warning: This action cannot be undone!</h2>
                <p>You are about to delete the node "<strong>{$nodeName}</strong>".</p>
                
                {$descendantsList}
                
                <p class="summary">
                    <strong>Total nodes to be deleted: {$descendantCount} child nodes + 1 main node = " . 
                    ($descendantCount + 1) . " nodes</strong>
                </p>
            </div>
            
            <div class="form-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </form>
                <a href="/tree/{$treeId}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateSuccessHTML(Tree $tree, AbstractTreeNode $node): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $nodeName = htmlspecialchars($node->getName());

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node Deleted Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn-primary { background: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>Node Deleted Successfully!</h1>
    <div class="success">
        <p>The node "{$nodeName}" and all its child nodes have been deleted from "{$treeName}".</p>
    </div>
    
    <a href="/tree/{$treeId}" class="btn btn-primary">Return to Tree</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateTreeNotFoundHTML(int $treeId): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Tree Not Found</h1>
    <p class="error">Tree with ID {$treeId} was not found in the database.</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateNodeNotFoundHTML(int $treeId, int $nodeId): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Node Not Found</h1>
    <p class="error">Node with ID {$nodeId} was not found in tree {$treeId}.</p>
    <a href="/tree/{$treeId}" class="btn">Return to Tree</a>
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
    <title>Error Deleting Node</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Error Deleting Node</h1>
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.header {
    text-align: center;
    margin-bottom: 30px;
    color: white;
}

.header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.description {
    font-size: 1.2em;
    opacity: 0.9;
    margin-bottom: 20px;
}

.form-container {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

.warning-message {
    background: #fff3cd;
    color: #856404;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #ffeeba;
}

.warning-message h2 {
    margin-bottom: 15px;
    font-size: 1.3em;
}

.warning-message h3 {
    margin: 15px 0 10px 0;
    font-size: 1.1em;
}

.descendants-list {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
    text-align: left;
}

.descendants-list li {
    margin: 5px 0;
    padding: 3px 0;
}

.summary {
    margin-top: 15px;
    font-weight: bold;
    color: #721c24;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    text-align: center;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .header h1 {
        font-size: 2em;
    }
    
    .form-container {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 200px;
    }
}
CSS;
    }
}
