<?php

declare(strict_types=1);

namespace App\Application\Actions\Tree;

use App\Application\Actions\Action;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use DateTime;

class AddNodeAction extends Action
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
            return $this->showForm();
        } elseif ($method === 'POST') {
            return $this->handleFormSubmission();
        }

        return $this->response->withStatus(405);
    }

    private function showForm(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');
            $tree = $this->treeRepository->findById($treeId);

            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            // Get parent_id from query parameters if provided
            $queryParams = $this->request->getQueryParams();
            $parentId = !empty($queryParams['parent_id']) ? (int) $queryParams['parent_id'] : null;

            // Prepare form data with pre-selected parent
            $formData = [];
            if ($parentId !== null) {
                $formData['parent_id'] = $parentId;
            }

            $html = $this->generateFormHTML($tree, '', $formData);
            $this->response->getBody()->write($html);
            return $this->response->withHeader('Content-Type', 'text/html');
        } catch (\Exception $e) {
            $this->logger->error('Error showing add node form: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function handleFormSubmission(): Response
    {
        try {
            $treeId = (int) $this->resolveArg('treeId');
            $tree = $this->treeRepository->findById($treeId);

            if (!$tree) {
                return $this->generateTreeNotFoundHTML($treeId);
            }

            $parsedBody = $this->request->getParsedBody();

            // Validate required fields
            $name = trim($parsedBody['name'] ?? '');
            if (empty($name)) {
                return $this->showFormWithError($tree, 'Node name is required');
            }

            if (strlen($name) > 255) {
                return $this->showFormWithError($tree, 'Node name must be 255 characters or less');
            }

            $parentId = !empty($parsedBody['parent_id']) ? (int) $parsedBody['parent_id'] : null;
            $nodeType = $parsedBody['node_type'] ?? 'SimpleNode';
            $sortOrder = (int) ($parsedBody['sort_order'] ?? 0);

            // Validate parent node if specified
            if ($parentId !== null) {
                $parentNode = $this->treeNodeRepository->findById($parentId);
                if (!$parentNode || $parentNode->getTreeId() !== $treeId) {
                    return $this->showFormWithError($tree, 'Invalid parent node selected');
                }
            }

            // Create node based on type
            if ($nodeType === 'ButtonNode') {
                $buttonText = trim($parsedBody['button_text'] ?? '');
                $buttonAction = trim($parsedBody['button_action'] ?? '');

                if (empty($buttonText)) {
                    return $this->showFormWithError($tree, 'Button text is required for ButtonNode');
                }

                if (strlen($buttonText) > 100) {
                    return $this->showFormWithError($tree, 'Button text must be 100 characters or less');
                }

                if (strlen($buttonAction) > 255) {
                    return $this->showFormWithError($tree, 'Button action must be 255 characters or less');
                }

                $node = new ButtonNode(
                    null,
                    $name,
                    $treeId,
                    $parentId,
                    $sortOrder
                );

                // Set button properties
                $node->setButtonText($buttonText);
                if (!empty($buttonAction)) {
                    $node->setButtonAction($buttonAction);
                }
            } else {
                $node = new SimpleNode(
                    null,
                    $name,
                    $treeId,
                    $parentId,
                    $sortOrder
                );
            }

            // Save the node
            $this->treeNodeRepository->save($node);

            return $this->generateSuccessHTML($tree, $node);
        } catch (\Exception $e) {
            $this->logger->error('Error creating node: ' . $e->getMessage());
            return $this->generateErrorHTML($e->getMessage());
        }
    }

    private function generateFormHTML(Tree $tree, string $error = '', array $formData = []): string
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $name = htmlspecialchars($formData['name'] ?? '');
        $parentId = $formData['parent_id'] ?? '';
        $nodeType = $formData['node_type'] ?? 'SimpleNode';
        $buttonText = htmlspecialchars($formData['button_text'] ?? '');
        $buttonAction = htmlspecialchars($formData['button_action'] ?? '');
        $sortOrder = $formData['sort_order'] ?? '0';
        $errorHtml = $error ? "<div class='error-message'>{$this->escapeHtml($error)}</div>" : '';

        // Get available parent nodes
        $parentNodes = $this->treeNodeRepository->findByTreeId($treeId);
        $parentOptions = '<option value="">No Parent (Root Node)</option>';
        foreach ($parentNodes as $parentNode) {
            $selected = $parentId == $parentNode->getId() ? 'selected' : '';
            $parentOptions .= "<option value=\"{$parentNode->getId()}\" {$selected}>{$this->escapeHtml($parentNode->getName())}</option>";
        }

        $css = $this->getCSS();

        $simpleNodeSelected = $nodeType === 'SimpleNode' ? 'selected' : '';
        $buttonNodeSelected = $nodeType === 'ButtonNode' ? 'selected' : '';
        $buttonFieldsDisplay = $nodeType === 'ButtonNode' ? 'block' : 'none';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Node to {$treeName}</title>
    <style>
        {$css}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add Node to Tree</h1>
            <p class="description">Add a new node to "{$treeName}"</p>
        </div>
        
        <div class="form-container">
            {$errorHtml}
            
            <form method="POST" class="node-form">
                <div class="form-group">
                    <label for="name">Node Name *</label>
                    <input type="text" id="name" name="name" value="{$name}" required 
                           placeholder="Enter node name" maxlength="255">
                    <small>Required. Maximum 255 characters.</small>
                </div>
                
                <div class="form-group">
                    <label for="parent_id">Parent Node</label>
                    <select id="parent_id" name="parent_id">
                        {$parentOptions}
                    </select>
                    <small>Leave empty to create a root node.</small>
                </div>
                
                <div class="form-group">
                    <label for="node_type">Node Type</label>
                    <select id="node_type" name="node_type" onchange="toggleButtonFields()">
                        <option value="SimpleNode" {$simpleNodeSelected}>Simple Node</option>
                        <option value="ButtonNode" {$buttonNodeSelected}>Button Node</option>
                    </select>
                    <small>Choose the type of node to create.</small>
                </div>
                
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{$sortOrder}" 
                           min="0" max="999">
                    <small>Order among siblings (0 = first).</small>
                </div>
                
                <div id="button-fields" class="form-group" style="display: {$buttonFieldsDisplay};">
                    <label for="button_text">Button Text *</label>
                    <input type="text" id="button_text" name="button_text" value="{$buttonText}" 
                           placeholder="Enter button text" maxlength="100">
                    <small>Required for ButtonNode. Maximum 100 characters.</small>
                    
                    <label for="button_action">Button Action</label>
                    <input type="text" id="button_action" name="button_action" value="{$buttonAction}" 
                           placeholder="Enter button action (e.g., clickHandler())" maxlength="255">
                    <small>Optional JavaScript function or action. Maximum 255 characters.</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Node</button>
                    <a href="/tree/{$treeId}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleButtonFields() {
            const nodeType = document.getElementById('node_type').value;
            const buttonFields = document.getElementById('button-fields');
            const buttonText = document.getElementById('button_text');
            
            if (nodeType === 'ButtonNode') {
                buttonFields.style.display = 'block';
                buttonText.required = true;
            } else {
                buttonFields.style.display = 'none';
                buttonText.required = false;
            }
        }
    </script>
</body>
</html>
HTML;
    }

    private function showFormWithError(Tree $tree, string $error): Response
    {
        $parsedBody = $this->request->getParsedBody();
        $html = $this->generateFormHTML($tree, $error, $parsedBody);
        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    /**
     * @param ButtonNode|SimpleNode $node
     */
    private function generateSuccessHTML(Tree $tree, $node): Response
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeId = $tree->getId();
        $nodeName = htmlspecialchars($node->getName());
        $nodeType = $node->getType();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node Created Successfully</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .node-info { background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <h1>Node Created Successfully!</h1>
    <div class="success">
        <p>The node "{$nodeName}" has been added to the tree "{$treeName}".</p>
    </div>
    
    <div class="node-info">
        <h3>Node Details:</h3>
        <p><strong>Name:</strong> {$nodeName}</p>
        <p><strong>Type:</strong> {$nodeType}</p>
        <p><strong>Tree ID:</strong> {$treeId}</p>
    </div>
    
    <a href="/tree/{$treeId}" class="btn btn-primary">View Tree</a>
    <a href="/tree/{$treeId}/add-node" class="btn btn-secondary">Add Another Node</a>
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

    private function generateErrorHTML(string $errorMessage): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Adding Node</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Error Adding Node</h1>
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

.node-form {
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
.form-group select {
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
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
