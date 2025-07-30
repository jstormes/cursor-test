<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use App\Domain\Tree\SimpleNode;
use PHPUnit\Framework\TestCase;

class HtmlTreeNodeRendererTest extends TestCase
{
    private HtmlTreeNodeRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HtmlTreeNodeRenderer();
    }

    public function testRenderSimpleNode(): void
    {
        $node = new SimpleNode(1, 'Test Node', 1);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Test Node', $html);
        $this->assertStringContainsString('<div', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function testRenderButtonNode(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, ['button_text' => 'Click Me']);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Test Button', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('Click Me', $html);
    }

    public function testRenderButtonNodeWithAction(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Test Button', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('alert(&quot;test&quot;)', $html);
    }

    public function testRenderSimpleNodeWithChildren(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1);
        $child2 = new SimpleNode(3, 'Child 2', 1, 1, 1);
        
        $parent->addChild($child1);
        $parent->addChild($child2);
        
        $html = $this->renderer->render($parent);
        
        $this->assertStringContainsString('Parent', $html);
        $this->assertStringContainsString('Child 1', $html);
        $this->assertStringContainsString('Child 2', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>', $html);
    }

    public function testRenderButtonNodeWithChildren(): void
    {
        $parent = new ButtonNode(1, 'Parent Button', 1, null, 0, ['button_text' => 'Parent']);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1);
        $child2 = new ButtonNode(3, 'Child Button', 1, 1, 1, ['button_text' => 'Child']);
        
        $parent->addChild($child1);
        $parent->addChild($child2);
        
        $html = $this->renderer->render($parent);
        
        $this->assertStringContainsString('Parent Button', $html);
        $this->assertStringContainsString('Child 1', $html);
        $this->assertStringContainsString('Child Button', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('<ul>', $html);
    }

    public function testRenderWithNestedChildren(): void
    {
        $root = new SimpleNode(1, 'Root', 1);
        $branch = new ButtonNode(2, 'Branch', 1, null, 0, ['button_text' => 'Branch Button']);
        $leaf1 = new SimpleNode(3, 'Leaf 1', 1, 2);
        $leaf2 = new SimpleNode(4, 'Leaf 2', 1, 2, 1);
        
        $branch->addChild($leaf1);
        $branch->addChild($leaf2);
        $root->addChild($branch);
        
        $html = $this->renderer->render($root);
        
        $this->assertStringContainsString('Root', $html);
        $this->assertStringContainsString('Branch', $html);
        $this->assertStringContainsString('Leaf 1', $html);
        $this->assertStringContainsString('Leaf 2', $html);
        
        // Should have nested structure
        $this->assertEquals(2, substr_count($html, '<ul>')); // Root and Branch
        $this->assertEquals(3, substr_count($html, '<li>')); // Root, Branch, and Leafs
    }

    public function testHtmlEscaping(): void
    {
        $node = new SimpleNode(1, 'Test <script>alert("xss")</script>', 1);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Test &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testButtonTextEscaping(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => '<script>alert("xss")</script>'
        ]);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testButtonActionEscaping(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => '<script>alert("xss")</script>'
        ]);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('onclick="&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
} 