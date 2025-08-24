<?php

declare(strict_types=1);

namespace App\Domain\Tree;

class HtmlTreeNodeRenderer implements TreeNodeRenderer, TreeNodeVisitor
{
    private bool $allowEdit;

    public function __construct(bool $allowEdit = true)
    {
        $this->allowEdit = $allowEdit;
    }

    #[\Override]
    public function render(TreeNode $node): string
    {
        $html = $node->accept($this);

        if ($node->hasChildren()) {
            $html .= '<ul>';
            foreach ($node->getChildren() as $child) {
                $html .= '<li>' . $this->render($child) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    #[\Override]
    public function visitSimpleNode(SimpleNode $node): string
    {
        if ($this->allowEdit) {
            $nodeId = $node->getId();
            $treeId = $node->getTreeId();
            return '<div class="tree-node">' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/delete" class="remove-icon">×</a>' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/sort-left" class="sort-left-icon">&lt;</a>' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/sort-right" class="sort-right-icon">&gt;</a>' .
                '<input type="checkbox"> ' . htmlspecialchars($node->getName()) .
                '<a href="/tree/' . $treeId . '/add-node?parent_id=' . $nodeId . '" class="add-icon">+</a>' .
                '</div>';
        } else {
            return '<div class="tree-node"><input type="checkbox"> ' . htmlspecialchars($node->getName()) . '</div>';
        }
    }

    #[\Override]
    public function visitButtonNode(ButtonNode $node): string
    {
        if ($this->allowEdit) {
            $nodeId = $node->getId();
            $treeId = $node->getTreeId();
            $html = '<div class="tree-node">' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/delete" class="remove-icon">×</a>' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/sort-left" class="sort-left-icon">&lt;</a>' .
                '<a href="/tree/' . $treeId . '/node/' . $nodeId . '/sort-right" class="sort-right-icon">&gt;</a>' .
                '<input type="checkbox"> ' . htmlspecialchars($node->getName());
        } else {
            $html = '<div class="tree-node"><input type="checkbox"> ' . htmlspecialchars($node->getName());
        }

        $action = $node->getButtonAction();
        $buttonText = htmlspecialchars($node->getButtonText());

        if ($action) {
            $html .= ' <br/> <button onclick="' . htmlspecialchars($action) . '">' . $buttonText . '</button>';
        } else {
            $html .= ' <br/> <button>' . $buttonText . '</button>';
        }

        if ($this->allowEdit) {
            $nodeId = $node->getId();
            $treeId = $node->getTreeId();
            $html .= '<a href="/tree/' . $treeId . '/add-node?parent_id=' . $nodeId . '" class="add-icon">+</a></div>';
        } else {
            $html .= '</div>';
        }

        return $html;
    }
}
