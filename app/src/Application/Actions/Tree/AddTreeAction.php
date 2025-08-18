<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Application\Validation\TreeValidator;
use App\Application\Exceptions\ValidationException;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use DateTime;

class AddTreeAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private TreeRepository $treeRepository,
        private TreeValidator $validator
    ) {
        parent::__construct($logger);
    }

    #[\Override]
    protected function action(): Response
    {
        $request = $this->request;
        $method = $request->getMethod();

        if ($method === 'GET') {
            return $this->showForm();
        } elseif ($method === 'POST') {
            return $this->handleFormSubmission();
        }

        return $this->response->withStatus(405);
    }

    private function showForm(): Response
    {
        try {
            $html = $this->generateFormHTML();
            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error showing add tree form: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function handleFormSubmission(): Response
    {
        try {
            $parsedBody = $this->request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            // Validate input data
            $validationResult = $this->validator->validate($data);
            if (!$validationResult->isValid()) {
                $errors = [];
                foreach ($validationResult->getErrors() as $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                return $this->showFormWithError(implode('. ', $errors));
            }

            // Sanitize input data
            $sanitizedData = $this->validator->sanitize($data);

            // Check if tree name already exists
            $existingTrees = $this->treeRepository->findActive();
            foreach ($existingTrees as $existingTree) {
                if (strtolower($existingTree->getName()) === strtolower($sanitizedData['name'])) {
                    return $this->showFormWithError('A tree with this name already exists');
                }
            }

            // Create the tree
            $tree = new Tree(
                null,
                $sanitizedData['name'],
                $sanitizedData['description'] ?? null
            );

            // Save the tree
            $this->treeRepository->save($tree);

            return $this->generateSuccessHTML($tree);
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->getValidationErrors() as $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            return $this->showFormWithError(implode('. ', $errors));
        } catch (\Exception $e) {
            $this->logger->error('Error creating tree: ' . $e->getMessage());
            return $this->generateErrorHTML('An error occurred while creating the tree. Please try again.');
        }
    }

    private function generateFormHTML(string $error = '', array $formData = []): string
    {
        $name = htmlspecialchars($formData['name'] ?? '');
        $description = htmlspecialchars($formData['description'] ?? '');
        $errorHtml = $error ? "<div class='error-message'>{$this->escapeHtml($error)}</div>" : '';

        $css = $this->getCSS();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Tree</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add New Tree</h1>
            <p class="description">Create a new tree structure</p>
        </div>
        
        <div class="form-container">
            {$errorHtml}
            
            <form method="POST" class="tree-form">
                <div class="form-group">
                    <label for="name">Tree Name *</label>
                    <input type="text" id="name" name="name" value="{$name}" required 
                           placeholder="Enter tree name" maxlength="255">
                    <small>Required. Maximum 255 characters.</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter tree description (optional)" 
                              maxlength="1000" rows="4">{$description}</textarea>
                    <small>Optional. Maximum 1000 characters.</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Tree</button>
                    <a href="/trees" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function showFormWithError(string $error): Response
    {
        $parsedBody = $this->request->getParsedBody();
        $formData = is_array($parsedBody) ? $parsedBody : [];
        $html = $this->generateFormHTML($error, $formData);
        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    private function generateSuccessHTML(Tree $tree): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $description = htmlspecialchars($tree->getDescription() ?: 'No description');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Created Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .tree-info { background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <h1>Tree Created Successfully!</h1>
    <div class="success">
        <p>The tree "{$treeName}" has been created successfully.</p>
    </div>
    
    <div class="tree-info">
        <h3>Tree Details:</h3>
        <p><strong>Name:</strong> {$treeName}</p>
        <p><strong>Description:</strong> {$description}</p>
        <p><strong>Tree ID:</strong> {$treeId}</p>
    </div>
    
    <a href="/tree/{$treeId}" class="btn btn-primary">View Tree</a>
    <a href="/tree/{$treeId}/add-node" class="btn btn-secondary">Add First Node</a>
    <a href="/trees" class="btn btn-secondary">Back to Trees</a>
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
    <title>Error Creating Tree</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Error Creating Tree</h1>
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

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.tree-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group small {
    color: #666;
    font-size: 12px;
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
