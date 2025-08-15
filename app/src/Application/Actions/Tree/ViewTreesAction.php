<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewTreesAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        try {
            // Get all active trees from the repository
            $trees = $this->treeRepository->findActive();

            // Generate HTML for the trees list
            $html = $this->generateHTML($trees);

            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            // Log the error (in a real app, you'd use a proper logger)
            error_log("Error fetching trees: " . $e->getMessage());

            $errorHtml = $this->generateErrorHTML($e->getMessage());
            $this->response->getBody()->write($errorHtml);
            return $this->response->withHeader('Content-Type', 'text/html');
        }
    }

    private function generateHTML(array $trees): string
    {
        $css = $this->getCSS();
        $treesHtml = $this->generateTreesList($trees);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trees List</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Trees List</h1>
            <p>Showing all active trees from the database.</p>
            <div class="header-actions">
                <a href="/tree/add" class="btn btn-primary">➕ Add New Tree</a>
                <a href="/trees/deleted" class="btn btn-secondary">🗑️ View Deleted Trees</a>
            </div>
        </div>
        
        <div class="stats">
            <p>Total active trees found: <strong>{$this->countTrees($trees)}</strong></p>
        </div>
        
        {$treesHtml}
        
        <div class="actions">
            <a href="/tree" class="btn btn-primary">View Tree Structure</a>
            <a href="/tree/json" class="btn btn-secondary">View JSON Data</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateTreesList(array $trees): string
    {
        if (empty($trees)) {
            return '<div class="no-trees"><p>No active trees found in the database.</p></div>';
        }

        $html = '<div class="trees-list">';

        foreach ($trees as $tree) {
            $html .= $this->generateTreeCard($tree);
        }

        $html .= '</div>';
        return $html;
    }

    private function generateTreeCard($tree): string
    {
        $description = $tree->getDescription() ?: 'No description available';
        $createdAt = $tree->getCreatedAt()->format('M j, Y g:i A');
        $updatedAt = $tree->getUpdatedAt()->format('M j, Y g:i A');

        return <<<HTML
<div class="tree-card">
    <div class="tree-header">
        <h3>{$this->escapeHtml($tree->getName())}</h3>
        <span class="tree-id">ID: {$tree->getId()}</span>
    </div>
    <div class="tree-content">
        <p class="description">{$this->escapeHtml($description)}</p>
        <div class="tree-meta">
            <span class="created">Created: {$createdAt}</span>
            <span class="updated">Updated: {$updatedAt}</span>
        </div>
    </div>
    <div class="tree-actions">
        <a href="/tree/{$tree->getId()}/view" class="btn btn-small btn-info">👁️ View Tree</a>
        <a href="/tree/{$tree->getId()}" class="btn btn-small btn-primary">✏️ Edit Tree</a>
        <a href="/tree/{$tree->getId()}/json" class="btn btn-small btn-secondary">JSON</a>
        <a href="/tree/{$tree->getId()}/delete" class="btn btn-small btn-danger">🗑️ Delete</a>
    </div>
</div>
HTML;
    }

    private function generateErrorHTML(string $errorMessage): string
    {
        $css = $this->getCSS();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Trees List</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <h1>Error Loading Trees</h1>
        <div class="error-message">
            <p>Sorry, there was an error loading the trees list:</p>
            <p class="error-details">{$this->escapeHtml($errorMessage)}</p>
        </div>
        <div class="actions">
            <a href="/" class="btn btn-primary">Go Home</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function countTrees(array $trees): int
    {
        return count($trees);
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
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
    margin: 0;
}

.header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: 300;
}

.header p {
    margin: 0 0 20px 0;
    font-size: 1.1em;
    opacity: 0.9;
}

.header-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.header-actions .btn {
    padding: 12px 24px;
    font-size: 1em;
    font-weight: 600;
}

.stats {
    background: #f8f9fa;
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
}

.stats p {
    margin: 0;
    color: #495057;
    font-size: 1.1em;
}

.trees-list {
    padding: 30px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.tree-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.tree-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.tree-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tree-header h3 {
    margin: 0;
    font-size: 1.3em;
    font-weight: 500;
}

.tree-id {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
}

.tree-content {
    padding: 20px;
}

.description {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 15px;
    font-size: 1em;
}

.tree-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-size: 0.9em;
    color: #6c757d;
}

.tree-meta span {
    display: flex;
    align-items: center;
}

.tree-meta span::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.created::before {
    background: #28a745;
}

.updated::before {
    background: #ffc107;
}

.tree-actions {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.9em;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
}

.btn-small {
    padding: 6px 12px;
    font-size: 0.8em;
}

.actions {
    padding: 30px;
    text-align: center;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.actions .btn {
    margin: 0 10px;
    padding: 12px 24px;
    font-size: 1em;
}

.no-trees {
    padding: 60px 30px;
    text-align: center;
    color: #6c757d;
}

.no-trees p {
    font-size: 1.2em;
    margin: 0;
}

.error-message {
    padding: 40px 30px;
    text-align: center;
    color: #dc3545;
}

.error-message p {
    margin-bottom: 15px;
    font-size: 1.1em;
}

.error-details {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    padding: 15px;
    color: #721c24;
    font-family: monospace;
    font-size: 0.9em;
    word-break: break-word;
}

@media (max-width: 768px) {
    .trees-list {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .container {
        margin: 10px;
        border-radius: 5px;
    }
    
    h1 {
        font-size: 2em;
        padding: 20px;
    }
    
    .tree-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .tree-actions {
        flex-direction: column;
    }
    
    .tree-actions .btn {
        width: 100%;
        text-align: center;
    }
}
CSS;
    }
}
