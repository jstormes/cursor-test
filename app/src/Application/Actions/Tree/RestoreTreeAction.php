<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class RestoreTreeAction extends Action
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
            $request = $this->request;
            $method = $request->getMethod();

            if ($method === 'GET') {
                return $this->showConfirmationForm();
            } elseif ($method === 'POST') {
                return $this->handleRestoration();
            }

            return $this->response->withStatus(405);
        } catch (\Exception $e) {
            $this->logger->error('Error in restore tree action: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function showConfirmationForm(): Response
    {
        $treeId = (int) $this->resolveArg('id');
        $tree = $this->treeRepository->findById($treeId);

        if (!$tree) {
            return $this->generateTreeNotFoundHTML($treeId);
        }

        if ($tree->isActive()) {
            return $this->generateAlreadyActiveHTML($tree);
        }

        $html = $this->generateConfirmationHTML($tree);
        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function handleRestoration(): Response
    {
        $treeId = (int) $this->resolveArg('id');
        $tree = $this->treeRepository->findById($treeId);

        if (!$tree) {
            return $this->generateTreeNotFoundHTML($treeId);
        }

        if ($tree->isActive()) {
            return $this->generateAlreadyActiveHTML($tree);
        }

        // Perform restore
        $this->treeRepository->restore($treeId);

        return $this->generateSuccessHTML($tree);
    }

    private function generateConfirmationHTML(\App\Domain\Tree\Tree $tree): string
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $description = htmlspecialchars($tree->getDescription() ?: 'No description');

        $css = $this->getCSS();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Tree - {$treeName}</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Restore Tree</h1>
            <p class="description">Are you sure you want to restore this tree?</p>
        </div>
        
        <div class="confirmation-container">
            <div class="tree-info">
                <h3>Tree Details:</h3>
                <p><strong>Name:</strong> {$treeName}</p>
                <p><strong>Description:</strong> {$description}</p>
                <p><strong>Tree ID:</strong> {$treeId}</p>
                <p><strong>Created:</strong> {$tree->getCreatedAt()->format('M j, Y g:i A')}</p>
                <p><strong>Status:</strong> <span style="color: #dc3545;">Deleted</span></p>
            </div>
            
            <div class="info">
                <h3>ℹ️ Information</h3>
                <p>This action will:</p>
                <ul>
                    <li>Make the tree visible in the main trees list</li>
                    <li>Restore all tree nodes and data</li>
                    <li>Allow normal tree operations again</li>
                    <li>Keep all existing tree structure intact</li>
                </ul>
            </div>
            
            <div class="form-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-success">🔄 Restore Tree</button>
                </form>
                <a href="/trees/deleted" class="btn btn-secondary">Cancel</a>
                <a href="/trees" class="btn btn-secondary">← Back to Trees</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function generateSuccessHTML(\App\Domain\Tree\Tree $tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Restored Successfully</title>
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
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border: 1px solid #c3e6cb;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 0 10px; 
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tree Restored Successfully!</h1>
        <div class="success">
            <p>The tree "{$treeName}" has been restored and is now visible in the main trees list.</p>
        </div>
        
        <p><strong>What happens next?</strong></p>
        <ul style="text-align: left; max-width: 400px; margin: 20px auto;">
            <li>The tree is now visible in the main trees list</li>
            <li>All tree nodes and data are fully accessible</li>
            <li>You can view, edit, and manage the tree normally</li>
            <li>The tree can be deleted again if needed</li>
        </ul>
        
        <div style="margin-top: 30px;">
            <a href="/tree/{$treeId}" class="btn btn-primary">View Tree</a>
            <a href="/trees" class="btn btn-secondary">← Back to Trees List</a>
        </div>
    </div>
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
        <h1>Tree Not Found</h1>
        <p class="error">Tree with ID {$treeId} was not found in the database.</p>
        <a href="/trees/deleted" class="btn">← Back to Deleted Trees</a>
    </div>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateAlreadyActiveHTML(\App\Domain\Tree\Tree $tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Already Active</title>
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
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border: 1px solid #bee5eb;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s ease;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tree Already Active</h1>
        <div class="info">
            <p>The tree "{$treeName}" is already active and visible in the main trees list.</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/tree/{$treeId}" class="btn btn-primary">View Tree</a>
            <a href="/trees" class="btn btn-secondary">← Back to Trees List</a>
        </div>
    </div>
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
    <title>Error Restoring Tree</title>
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
        <h1>Error Restoring Tree</h1>
        <p class="error">{$this->escapeHtml($errorMessage)}</p>
        <a href="/trees/deleted" class="btn">← Back to Deleted Trees</a>
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

.confirmation-container {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

.tree-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.tree-info h3 {
    margin-bottom: 15px;
    color: #333;
}

.tree-info p {
    margin: 8px 0;
    color: #666;
}

.info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info h3 {
    margin-bottom: 15px;
    color: #0c5460;
}

.info ul {
    margin-left: 20px;
}

.info li {
    margin: 5px 0;
}

.form-actions {
    text-align: center;
    margin-top: 30px;
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
    margin: 0 10px;
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    
    .form-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .btn {
        width: 200px;
    }
}
CSS;
    }
}
