<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

use App\Domain\Tree\Tree;
use App\Domain\Tree\HtmlTreeNodeRenderer;

final class TreeHtmlRenderer implements HtmlRendererInterface
{
    #[\Override]
    public function renderTreeView(Tree $tree, array $rootNodes): string
    {
        $treeName = htmlspecialchars($tree->getName());
        $treeDescription = htmlspecialchars($tree->getDescription() ?: 'No description available');
        
        // Generate tree HTML
        $renderer = new HtmlTreeNodeRenderer(true);
        $treeHtml = '<div class="tree"><ul>';
        foreach ($rootNodes as $rootNode) {
            $treeHtml .= '<li>' . $renderer->render($rootNode) . '</li>';
        }
        $treeHtml .= '</ul></div>';

        return $this->renderPage(
            "Tree Structure - {$treeName}",
            <<<HTML
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
            HTML
        );
    }

    #[\Override]
    public function renderTreeList(array $trees): string
    {
        $treeListHtml = '';
        foreach ($trees as $tree) {
            $treeName = htmlspecialchars($tree->getName());
            $treeDescription = htmlspecialchars($tree->getDescription() ?: 'No description');
            $treeListHtml .= <<<HTML
            <div class="tree-item">
                <h3><a href="/tree/{$tree->getId()}">{$treeName}</a></h3>
                <p>{$treeDescription}</p>
                <small>Created: {$tree->getCreatedAt()->format('M j, Y g:i A')}</small>
            </div>
            HTML;
        }

        return $this->renderPage(
            'Trees List',
            <<<HTML
            <div class="header">
                <h1>Trees List</h1>
                <p class="description">All available trees in the system</p>
            </div>
            
            <div class="navigation">
                <a href="/tree/add" class="btn btn-primary">Add New Tree</a>
            </div>
            
            <div class="tree-list">
                {$treeListHtml}
            </div>
            HTML
        );
    }

    #[\Override]
    public function renderNoTrees(): string
    {
        return $this->renderPage(
            'No Trees Available',
            <<<HTML
            <h1>No Trees Available</h1>
            <p class="message">No active trees found in the database.</p>
            <a href="/trees" class="btn">Go to Trees List</a>
            HTML,
            $this->getSimplePageCSS()
        );
    }

    #[\Override]
    public function renderEmptyTree(Tree $tree): string
    {
        $treeName = htmlspecialchars($tree->getName());
        
        return $this->renderPage(
            "Empty Tree - {$treeName}",
            <<<HTML
            <h1>Empty Tree: {$treeName}</h1>
            <p class="message">This tree has no nodes yet.</p>
            <a href="/trees" class="btn">Back to Trees List</a>
            HTML,
            $this->getSimplePageCSS()
        );
    }

    #[\Override]
    public function renderNoRootNodes(Tree $tree): string
    {
        $treeName = htmlspecialchars($tree->getName());
        
        return $this->renderPage(
            "No Root Nodes - {$treeName}",
            <<<HTML
            <h1>No Root Nodes: {$treeName}</h1>
            <p class="message">This tree has nodes but no root nodes found.</p>
            <a href="/trees" class="btn">Back to Trees List</a>
            HTML,
            $this->getSimplePageCSS()
        );
    }

    #[\Override]
    public function renderError(string $message, ?string $title = null): string
    {
        $title = $title ?: 'Error';
        $safeMessage = htmlspecialchars($message);
        
        return $this->renderPage(
            $title,
            <<<HTML
            <h1>{$title}</h1>
            <p class="error">{$safeMessage}</p>
            <a href="/trees" class="btn">Back to Trees List</a>
            HTML,
            $this->getErrorPageCSS()
        );
    }

    #[\Override]
    public function renderForm(string $title, string $formHtml, array $navigationLinks = []): string
    {
        $navigationHtml = '';
        if (!empty($navigationLinks)) {
            $navigationHtml = '<div class="navigation">';
            foreach ($navigationLinks as $text => $url) {
                $navigationHtml .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-secondary">' . htmlspecialchars($text) . '</a>';
            }
            $navigationHtml .= '</div>';
        }

        return $this->renderPage(
            $title,
            <<<HTML
            <div class="header">
                <h1>{$title}</h1>
            </div>
            {$navigationHtml}
            <div class="form-container">
                {$formHtml}
            </div>
            HTML
        );
    }

    #[\Override]
    public function renderSuccess(string $message, array $navigationLinks = []): string
    {
        $safeMessage = htmlspecialchars($message);
        $navigationHtml = '';
        
        if (!empty($navigationLinks)) {
            foreach ($navigationLinks as $text => $url) {
                $navigationHtml .= '<a href="' . htmlspecialchars($url) . '" class="btn">' . htmlspecialchars($text) . '</a>';
            }
        }

        return $this->renderPage(
            'Success',
            <<<HTML
            <h1>Success</h1>
            <p class="success">{$safeMessage}</p>
            {$navigationHtml}
            HTML,
            $this->getSuccessPageCSS()
        );
    }

    #[\Override]
    public function renderDeletedTrees(array $deletedTrees): string
    {
        $treeListHtml = '';
        foreach ($deletedTrees as $tree) {
            $treeName = htmlspecialchars($tree->getName());
            $treeDescription = htmlspecialchars($tree->getDescription() ?: 'No description');
            $treeListHtml .= <<<HTML
            <div class="tree-item deleted">
                <h3>{$treeName}</h3>
                <p>{$treeDescription}</p>
                <small>Deleted: {$tree->getUpdatedAt()->format('M j, Y g:i A')}</small>
                <a href="/tree/{$tree->getId()}/restore" class="btn btn-primary btn-sm">Restore</a>
            </div>
            HTML;
        }

        return $this->renderPage(
            'Deleted Trees',
            <<<HTML
            <div class="header">
                <h1>Deleted Trees</h1>
                <p class="description">Trees that have been soft deleted</p>
            </div>
            
            <div class="navigation">
                <a href="/trees" class="btn btn-secondary">← Back to Active Trees</a>
            </div>
            
            <div class="tree-list">
                {$treeListHtml}
            </div>
            HTML
        );
    }

    private function renderPage(string $title, string $content, ?string $customCSS = null): string
    {
        $css = $customCSS ?: $this->getMainCSS();
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                {$css}
            </style>
        </head>
        <body>
            {$content}
        </body>
        </html>
        HTML;
    }

    private function getMainCSS(): string
    {
        return <<<CSS
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin: 20px;
        }
        
        .header h1 { margin: 0 0 10px 0; font-size: 2em; }
        .description { margin: 0 0 15px 0; font-size: 1.1em; opacity: 0.9; }
        .tree-info { display: flex; justify-content: center; gap: 20px; font-size: 0.9em; opacity: 0.8; }
        
        .navigation { text-align: center; margin: 20px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 10px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); }
        
        .tree ul { padding-top: 20px; position: relative; transition: all 0.5s; }
        .tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; transition: all 0.5s; }
        .tree li::before, .tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 1px solid #ccc; width: 50%; height: 20px; }
        .tree li::after { right: auto; left: 50%; border-left: 1px solid #ccc; }
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0 none; }
        .tree li:last-child::before { border-right: 1px solid #ccc; border-radius: 0 5px 0 0; }
        .tree li:first-child::after { border-radius: 5px 0 0 0; }
        .tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 1px solid #ccc; width: 0; height: 20px; }
        .tree li div {
            border: 1px solid #1e3a8a;
            padding: 15px 10px;
            color: #1e3a8a;
            background-color: #ffffff;
            font-family: arial, verdana, tahoma;
            font-size: 11px;
            display: inline-block;
            position: relative;
            border-radius: 5px;
            transition: all 0.5s;
        }
        
        .tree-list { margin: 20px; }
        .tree-item { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .tree-item h3 { margin: 0 0 10px 0; }
        .tree-item a { color: #667eea; text-decoration: none; }
        .tree-item a:hover { text-decoration: underline; }
        CSS;
    }

    private function getSimplePageCSS(): string
    {
        return <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .message { color: #666; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }

    private function getErrorPageCSS(): string
    {
        return <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }

    private function getSuccessPageCSS(): string
    {
        return <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .success { color: #28a745; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }
}