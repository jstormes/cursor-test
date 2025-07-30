<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\TreeNode;
use App\Domain\Tree\TreeNodeVisitor;
use PHPUnit\Framework\TestCase;

class CompositePatternTest extends TestCase
{
    private HtmlTreeNodeRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HtmlTreeNodeRenderer();
    }

    public function testUniformInterface(): void
    {
        // Test that all nodes implement the same interface
        $node1 = new SimpleNode(1, 'Node 1', 1);
        $node2 = new ButtonNode(2, 'Node 2', 1, null, 0, ['button_text' => 'Button']);
        
        $this->assertInstanceOf(TreeNode::class, $node1);
        $this->assertInstanceOf(TreeNode::class, $node2);
        
        // Both should have the same methods
        $this->assertTrue(method_exists($node1, 'getName'));
        $this->assertTrue(method_exists($node1, 'addChild'));
        $this->assertTrue(method_exists($node1, 'getChildren'));
        $this->assertTrue(method_exists($node1, 'hasChildren'));
        $this->assertTrue(method_exists($node1, 'getType'));
    }

    public function testTreatingIndividualAndCompositeUniformly(): void
    {
        // Create nodes that will act as both individual and composite
        $root = new SimpleNode(1, 'Root', 1);
        $branch = new ButtonNode(2, 'Branch', 1, null, 0, ['button_text' => 'Branch Button']);
        $leaf1 = new SimpleNode(3, 'Leaf 1', 1, 2);
        $leaf2 = new SimpleNode(4, 'Leaf 2', 1, 2, 1);
        
        // Initially, all nodes are treated the same (no children)
        $this->assertFalse($root->hasChildren());
        $this->assertFalse($branch->hasChildren());
        $this->assertFalse($leaf1->hasChildren());
        $this->assertFalse($leaf2->hasChildren());
        
        // Add children to make some nodes composite
        $branch->addChild($leaf1);
        $branch->addChild($leaf2);
        $root->addChild($branch);
        
        // Now some have children, but the interface is the same
        $this->assertTrue($root->hasChildren());
        $this->assertTrue($branch->hasChildren());
        $this->assertFalse($leaf1->hasChildren());
        $this->assertFalse($leaf2->hasChildren());
    }

    public function testRecursiveRendering(): void
    {
        // Build a complex tree structure
        $root = new SimpleNode(1, 'Root', 1);
        $branch1 = new ButtonNode(2, 'Branch 1', 1, null, 0, ['button_text' => 'Button 1']);
        $branch2 = new SimpleNode(3, 'Branch 2', 1, null, 1);
        $leaf1 = new SimpleNode(4, 'Leaf 1', 1, 2);
        $leaf2 = new SimpleNode(5, 'Leaf 2', 1, 2, 1);
        $leaf3 = new ButtonNode(6, 'Leaf 3', 1, 3, 0, ['button_text' => 'Button 3']);
        
        // Build the tree
        $branch1->addChild($leaf1);
        $branch1->addChild($leaf2);
        $branch2->addChild($leaf3);
        $root->addChild($branch1);
        $root->addChild($branch2);
        
        // Test recursive rendering
        $html = $this->renderer->render($root);
        
        // Should contain all node names
        $this->assertStringContainsString('Root', $html);
        $this->assertStringContainsString('Branch 1', $html);
        $this->assertStringContainsString('Branch 2', $html);
        $this->assertStringContainsString('Leaf 1', $html);
        $this->assertStringContainsString('Leaf 2', $html);
        $this->assertStringContainsString('Leaf 3', $html);
        
        // Should have proper nesting structure
        $this->assertEquals(3, substr_count($html, '<ul>')); // Root, Branch1, Branch2
        $this->assertEquals(5, substr_count($html, '<li>')); // All nodes
    }

    public function testComplexTreeStructure(): void
    {
        // Create a more complex tree
        $root = new SimpleNode(1, 'Company', 1);
        
        $hr = new ButtonNode(2, 'HR', 1, null, 0, ['button_text' => 'HR Button']);
        $it = new SimpleNode(3, 'IT', 1, null, 1);
        $sales = new ButtonNode(4, 'Sales', 1, null, 2, ['button_text' => 'Sales Button']);
        
        $hr->addChild(new SimpleNode(5, 'Recruitment', 1, 2));
        $hr->addChild(new SimpleNode(6, 'Benefits', 1, 2, 1));
        
        $it->addChild(new SimpleNode(7, 'Development', 1, 3));
        $it->addChild(new ButtonNode(8, 'Infrastructure', 1, 3, 1, ['button_text' => 'Infra Button']));
        
        $sales->addChild(new SimpleNode(9, 'North', 1, 4));
        $sales->addChild(new SimpleNode(10, 'South', 1, 4, 1));
        
        $root->addChild($hr);
        $root->addChild($it);
        $root->addChild($sales);
        
        // Test the structure
        $this->assertTrue($root->hasChildren());
        $this->assertCount(3, $root->getChildren());
        
        $this->assertTrue($hr->hasChildren());
        $this->assertCount(2, $hr->getChildren());
        
        $this->assertTrue($it->hasChildren());
        $this->assertCount(2, $it->getChildren());
        
        $this->assertTrue($sales->hasChildren());
        $this->assertCount(2, $sales->getChildren());
        
        // Test rendering
        $html = $this->renderer->render($root);
        $this->assertStringContainsString('Company', $html);
        $this->assertStringContainsString('HR', $html);
        $this->assertStringContainsString('IT', $html);
        $this->assertStringContainsString('Sales', $html);
    }

    public function testButtonFunctionality(): void
    {
        $button = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        
        $this->assertEquals('ButtonNode', $button->getType());
        $this->assertEquals('Click Me', $button->getButtonText());
        $this->assertEquals('alert("test")', $button->getButtonAction());
        
        // Test visitor pattern
        $visitor = new class implements TreeNodeVisitor {
            public function visitSimpleNode(SimpleNode $node): string { return 'simple'; }
            public function visitButtonNode(ButtonNode $node): string { 
                return 'button:' . $node->getButtonText() . ':' . $node->getButtonAction(); 
            }
        };
        
        $result = $button->accept($visitor);
        $this->assertEquals('button:Click Me:alert("test")', $result);
    }

    public function testEmptyTree(): void
    {
        $root = new SimpleNode(1, 'Empty Root', 1);
        
        $this->assertFalse($root->hasChildren());
        $this->assertEmpty($root->getChildren());
        
        $html = $this->renderer->render($root);
        $this->assertStringContainsString('Empty Root', $html);
        $this->assertStringNotContainsString('<ul>', $html); // No children, no nested lists
    }

    public function testSingleChildTree(): void
    {
        $root = new SimpleNode(1, 'Parent', 1);
        $child = new SimpleNode(2, 'Child', 1, 1);
        
        $root->addChild($child);
        
        $this->assertTrue($root->hasChildren());
        $this->assertCount(1, $root->getChildren());
        $this->assertFalse($child->hasChildren());
        
        $html = $this->renderer->render($root);
        $this->assertStringContainsString('Parent', $html);
        $this->assertStringContainsString('Child', $html);
        $this->assertEquals(1, substr_count($html, '<ul>')); // One nested list
    }

    public function testDeepNesting(): void
    {
        // Create a deeply nested structure
        $level1 = new SimpleNode(1, 'Level 1', 1);
        $level2 = new SimpleNode(2, 'Level 2', 1, 1);
        $level3 = new SimpleNode(3, 'Level 3', 1, 2);
        $level4 = new SimpleNode(4, 'Level 4', 1, 3);
        $level5 = new SimpleNode(5, 'Level 5', 1, 4);
        
        $level4->addChild($level5);
        $level3->addChild($level4);
        $level2->addChild($level3);
        $level1->addChild($level2);
        
        $html = $this->renderer->render($level1);
        
        // Should contain all levels
        $this->assertStringContainsString('Level 1', $html);
        $this->assertStringContainsString('Level 2', $html);
        $this->assertStringContainsString('Level 3', $html);
        $this->assertStringContainsString('Level 4', $html);
        $this->assertStringContainsString('Level 5', $html);
        
        // Should have proper nesting (4 nested lists for 5 levels)
        $this->assertEquals(4, substr_count($html, '<ul>'));
    }
} 