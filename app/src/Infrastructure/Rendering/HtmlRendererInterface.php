<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

use App\Domain\Tree\Tree;

interface HtmlRendererInterface
{
    /**
     * Render a complete tree view page
     */
    public function renderTreeView(Tree $tree, array $rootNodes): string;

    /**
     * Render a tree list page
     */
    public function renderTreeList(array $trees): string;

    /**
     * Render a "no trees available" page
     */
    public function renderNoTrees(): string;

    /**
     * Render an "empty tree" page
     */
    public function renderEmptyTree(Tree $tree): string;

    /**
     * Render a "no root nodes" page
     */
    public function renderNoRootNodes(Tree $tree): string;

    /**
     * Render an error page
     */
    public function renderError(string $message, ?string $title = null): string;

    /**
     * Render a form page
     */
    public function renderForm(string $title, string $formHtml, array $navigationLinks = []): string;

    /**
     * Render a success page
     */
    public function renderSuccess(string $message, array $navigationLinks = []): string;

    /**
     * Render deleted trees page
     */
    public function renderDeletedTrees(array $deletedTrees): string;
}