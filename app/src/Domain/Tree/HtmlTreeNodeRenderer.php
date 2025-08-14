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
        if ($this->allowEdit) {
            return '<div class="tree-node"><span class="remove-icon">×</span><input type="checkbox"> ' . htmlspecialchars($node->getName()) . '<span class="add-icon">+</span></div>';
        } else {
            return '<div class="tree-node"><input type="checkbox"> ' . htmlspecialchars($node->getName()) . '</div>';
        }
    }

    public function visitButtonNode(ButtonNode $node): string
    {
        if ($this->allowEdit) {
            $html = '<div class="tree-node"><span class="remove-icon">×</span><input type="checkbox"> ' . htmlspecialchars($node->getName());
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
            $html .= '<span class="add-icon">+</span></div>';
        } else {
            $html .= '</div>';
        }
        
        return $html;
    }
} 