<?php

declare(strict_types=1);

namespace App\Domain\Tree;

class HtmlTreeNodeRenderer implements TreeNodeRenderer, TreeNodeVisitor
{
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

    public function visitSimpleNode(SimpleNode $node): string
    {
        return '<div class="tree-node"><span class="remove-icon">×</span><input type="checkbox"> ' . htmlspecialchars($node->getName()) . '<span class="add-icon">+</span></div>';
    }

    public function visitButtonNode(ButtonNode $node): string
    {
        $html = '<div class="tree-node"><span class="remove-icon">×</span><input type="checkbox"> ' . htmlspecialchars($node->getName());
        
        $action = $node->getButtonAction();
        $buttonText = htmlspecialchars($node->getButtonText());
        
        if ($action) {
            $html .= ' <br/> <button onclick="' . htmlspecialchars($action) . '">' . $buttonText . '</button>';
        } else {
            $html .= ' <br/> <button>' . $buttonText . '</button>';
        }
        
        $html .= '<span class="add-icon">+</span></div>';
        
        return $html;
    }
} 