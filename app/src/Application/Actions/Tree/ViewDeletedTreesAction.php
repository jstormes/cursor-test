<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ViewDeletedTreesAction extends Action
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
            $deletedTrees = $this->treeRepository->findDeleted();
            $html = $this->generateHTML($deletedTrees);

            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error loading deleted trees: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function generateHTML(array $deletedTrees): string
    {
        $css = $this->getCSS();
        $treesHtml = $this->generateTreesList($deletedTrees);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Trees</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗑️ Deleted Trees</h1>
            <p>Manage your deleted trees. You can restore them or permanently delete them.</p>
        </div>
        
        <div class="navigation">
            <a href="/trees" class="btn btn-primary">← Back to Active Trees</a>
        </div>
        
        {$treesHtml}
    </div>
</body>
</html>
HTML;
    }

    private function generateTreesList(array $deletedTrees): string
    {
        if (empty($deletedTrees)) {
            return <<<HTML
        <div class="empty-state">
            <div class="empty-icon">🗑️</div>
            <h2>No Deleted Trees</h2>
            <p>There are no deleted trees to display.</p>
            <a href="/trees" class="btn btn-primary">← Back to Active Trees</a>
        </div>
HTML;
        }

        $treesHtml = '';
        foreach ($deletedTrees as $tree) {
            $treeName = htmlspecialchars($tree->getName());
            $treeId = $tree->getId();
            $description = htmlspecialchars($tree->getDescription() ?: 'No description');
            $createdAt = $tree->getCreatedAt()->format('M j, Y g:i A');
            $updatedAt = $tree->getUpdatedAt()->format('M j, Y g:i A');

            $treesHtml .= <<<HTML
        <div class="tree-card deleted">
            <div class="tree-info">
                <h3>{$treeName}</h3>
                <p class="description">{$description}</p>
                <div class="tree-meta">
                    <span class="tree-id">ID: {$treeId}</span>
                    <span class="created">Created: {$createdAt}</span>
                    <span class="deleted">Deleted: {$updatedAt}</span>
                </div>
            </div>
            
            <div class="tree-actions">
                <a href="/tree/{$treeId}/restore" class="btn btn-success">🔄 Restore</a>
                <a href="/tree/{$treeId}/delete" class="btn btn-danger">🗑️ Delete Permanently</a>
            </div>
        </div>
HTML;
        }

        return <<<HTML
        <div class="trees-list">
            <div class="stats">
                <p>Total deleted trees: <strong>{$this->countTrees($deletedTrees)}</strong></p>
            </div>
            
            {$treesHtml}
        </div>
HTML;
    }

    private function countTrees(array $trees): int
    {
        return count($trees);
    }

    private function generateErrorHTML(string $errorMessage): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Loading Deleted Trees</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            text-align: center; 
            padding: 50px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            color: #333;
        }
        .error { 
            color: #dc3545; 
            margin: 20px 0; 
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 0 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Error Loading Deleted Trees</h1>
        <p class="error">{$this->escapeHtml($errorMessage)}</p>
        <a href="/trees" class="btn">← Back to Trees List</a>
    </div>
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
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
    margin: 0;
    font-size: 1.1em;
    opacity: 0.9;
}

.navigation {
    padding: 20px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
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
    margin: 0 5px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.stats {
    padding: 20px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    text-align: center;
}

.stats p {
    margin: 0;
    color: #666;
    font-size: 1.1em;
}

.trees-list {
    padding: 20px;
}

.tree-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.tree-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.tree-card.deleted {
    border-left: 4px solid #dc3545;
    background: #fff5f5;
}

.tree-info {
    flex: 1;
}

.tree-info h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 1.3em;
}

.tree-info .description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
}

.tree-meta {
    display: flex;
    gap: 20px;
    font-size: 0.9em;
    color: #888;
}

.tree-meta span {
    display: inline-block;
}

.tree-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h2 {
    margin-bottom: 10px;
    color: #333;
}

.empty-state p {
    margin-bottom: 30px;
    font-size: 1.1em;
}

@media (max-width: 768px) {
    .container {
        margin: 10px;
        border-radius: 8px;
    }
    
    .header h1 {
        font-size: 2em;
    }
    
    .tree-card {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .tree-actions {
        justify-content: center;
    }
    
    .tree-meta {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn {
        width: 100%;
        margin: 5px 0;
    }
}
CSS;
    }
}
