<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

use App\Domain\Tree\Tree;
use App\Domain\Tree\HtmlTreeNodeRenderer;

final class TreeHtmlRenderer implements HtmlRendererInterface
{
    public function __construct(
        private CssProviderInterface $cssProvider
    ) {
    }
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
            $this->cssProvider->getSimplePageCSS()
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
            $this->cssProvider->getSimplePageCSS()
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
            $this->cssProvider->getSimplePageCSS()
        );
    }

    #[\Override]
    public function renderError(string $message, ?string $title = null): string
    {
        $title = $title ?: 'Error';
        $safeTitle = htmlspecialchars($title);
        $safeMessage = htmlspecialchars($message);

        return $this->renderPage(
            $safeTitle,
            <<<HTML
            <h1>{$safeTitle}</h1>
            <p class="error">{$safeMessage}</p>
            <a href="/trees" class="btn">Back to Trees List</a>
            HTML,
            $this->cssProvider->getErrorPageCSS()
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
            $this->cssProvider->getSuccessPageCSS()
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
        $mainCSS = $this->cssProvider->getMainCSS();
        $treeCSS = $this->cssProvider->getTreeCSS('standard');
        
        $css = $customCSS ?: ($mainCSS . "\n\n" . $treeCSS);

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
}
