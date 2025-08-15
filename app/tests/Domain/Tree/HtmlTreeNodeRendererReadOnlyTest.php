<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use App\Domain\Tree\SimpleNode;
use PHPUnit\Framework\TestCase;

class HtmlTreeNodeRendererReadOnlyTest extends TestCase
{
    private HtmlTreeNodeRenderer $readOnlyRenderer;
    private HtmlTreeNodeRenderer $editableRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readOnlyRenderer = new HtmlTreeNodeRenderer(false); // Read-only mode
        $this->editableRenderer = new HtmlTreeNodeRenderer(true);   // Editable mode (default)
    }

    public function testConstructorDefaultsToEditable(): void
    {
        $defaultRenderer = new HtmlTreeNodeRenderer();
        $node = new SimpleNode(1, 'Test Node', 1);
        $html = $defaultRenderer->render($node);

        // Should contain edit icons by default
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
    }

    public function testConstructorExplicitEditable(): void
    {
        $editableRenderer = new HtmlTreeNodeRenderer(true);
        $node = new SimpleNode(1, 'Test Node', 1);
        $html = $editableRenderer->render($node);

        // Should contain edit icons when explicitly enabled
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
    }

    public function testConstructorReadOnly(): void
    {
        $readOnlyRenderer = new HtmlTreeNodeRenderer(false);
        $node = new SimpleNode(1, 'Test Node', 1);
        $html = $readOnlyRenderer->render($node);

        // Should NOT contain edit icons when disabled
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
    }

    public function testSimpleNodeReadOnlyMode(): void
    {
        $node = new SimpleNode(5, 'Test Node', 3);
        $html = $this->readOnlyRenderer->render($node);

        // Should contain the node name and checkbox
        $this->assertStringContainsString('Test Node', $html);
        $this->assertStringContainsString('<input type="checkbox">', $html);
        $this->assertStringContainsString('<div class="tree-node">', $html);

        // Should NOT contain edit links
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
        $this->assertStringNotContainsString('/tree/3/node/5/delete', $html);
        $this->assertStringNotContainsString('/tree/3/add-node?parent_id=5', $html);
    }

    public function testSimpleNodeEditableMode(): void
    {
        $node = new SimpleNode(5, 'Test Node', 3);
        $html = $this->editableRenderer->render($node);

        // Should contain the node name and checkbox
        $this->assertStringContainsString('Test Node', $html);
        $this->assertStringContainsString('<input type="checkbox">', $html);
        $this->assertStringContainsString('<div class="tree-node">', $html);

        // Should contain edit links
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
        $this->assertStringContainsString('/tree/3/node/5/delete', $html);
        $this->assertStringContainsString('/tree/3/add-node?parent_id=5', $html);
    }

    public function testButtonNodeReadOnlyMode(): void
    {
        $node = new ButtonNode(7, 'Button Node', 2, null, 0);
        $node->setButtonText('Click Me');
        $node->setButtonAction('alert("Hello")');

        $html = $this->readOnlyRenderer->render($node);

        // Should contain the node name, checkbox, and button
        $this->assertStringContainsString('Button Node', $html);
        $this->assertStringContainsString('<input type="checkbox">', $html);
        $this->assertStringContainsString('<div class="tree-node">', $html);
        $this->assertStringContainsString('<button onclick="alert(&quot;Hello&quot;)">Click Me</button>', $html);

        // Should NOT contain edit links
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
        $this->assertStringNotContainsString('/tree/2/node/7/delete', $html);
        $this->assertStringNotContainsString('/tree/2/add-node?parent_id=7', $html);
    }

    public function testButtonNodeEditableMode(): void
    {
        $node = new ButtonNode(7, 'Button Node', 2, null, 0);
        $node->setButtonText('Click Me');
        $node->setButtonAction('alert("Hello")');

        $html = $this->editableRenderer->render($node);

        // Should contain the node name, checkbox, and button
        $this->assertStringContainsString('Button Node', $html);
        $this->assertStringContainsString('<input type="checkbox">', $html);
        $this->assertStringContainsString('<div class="tree-node">', $html);
        $this->assertStringContainsString('<button onclick="alert(&quot;Hello&quot;)">Click Me</button>', $html);

        // Should contain edit links
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
        $this->assertStringContainsString('/tree/2/node/7/delete', $html);
        $this->assertStringContainsString('/tree/2/add-node?parent_id=7', $html);
    }

    public function testButtonNodeWithoutActionReadOnly(): void
    {
        $node = new ButtonNode(8, 'Simple Button', 4, null, 0);
        $node->setButtonText('Just a Button');

        $html = $this->readOnlyRenderer->render($node);

        // Should contain button without onclick attribute
        $this->assertStringContainsString('Simple Button', $html);
        $this->assertStringContainsString('<button>Just a Button</button>', $html);
        $this->assertStringNotContainsString('onclick', $html);

        // Should NOT contain edit links
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
    }

    public function testButtonNodeWithoutActionEditable(): void
    {
        $node = new ButtonNode(8, 'Simple Button', 4, null, 0);
        $node->setButtonText('Just a Button');

        $html = $this->editableRenderer->render($node);

        // Should contain button without onclick attribute
        $this->assertStringContainsString('Simple Button', $html);
        $this->assertStringContainsString('<button>Just a Button</button>', $html);
        $this->assertStringNotContainsString('onclick', $html);

        // Should contain edit links
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
    }

    public function testTreeStructureReadOnlyMode(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1);
        $child2 = new ButtonNode(3, 'Child Button', 1, 1, 1);
        $child2->setButtonText('Child Action');

        $parent->addChild($child1);
        $parent->addChild($child2);

        $html = $this->readOnlyRenderer->render($parent);

        // Should contain all nodes
        $this->assertStringContainsString('Parent', $html);
        $this->assertStringContainsString('Child 1', $html);
        $this->assertStringContainsString('Child Button', $html);
        $this->assertStringContainsString('Child Action', $html);

        // Should contain tree structure
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>', $html);

        // Should NOT contain any edit links
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
        $this->assertStringNotContainsString('/delete', $html);
        $this->assertStringNotContainsString('add-node', $html);
    }

    public function testTreeStructureEditableMode(): void
    {
        $parent = new SimpleNode(1, 'Parent', 1);
        $child1 = new SimpleNode(2, 'Child 1', 1, 1);
        $child2 = new ButtonNode(3, 'Child Button', 1, 1, 1);
        $child2->setButtonText('Child Action');

        $parent->addChild($child1);
        $parent->addChild($child2);

        $html = $this->editableRenderer->render($parent);

        // Should contain all nodes
        $this->assertStringContainsString('Parent', $html);
        $this->assertStringContainsString('Child 1', $html);
        $this->assertStringContainsString('Child Button', $html);
        $this->assertStringContainsString('Child Action', $html);

        // Should contain tree structure
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>', $html);

        // Should contain edit links for all nodes
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
        $this->assertStringContainsString('/delete', $html);
        $this->assertStringContainsString('add-node', $html);
    }

    public function testDirectVisitorCallsReadOnly(): void
    {
        $simpleNode = new SimpleNode(10, 'Direct Simple', 5);
        $buttonNode = new ButtonNode(11, 'Direct Button', 5, null, 0);
        $buttonNode->setButtonText('Direct Action');
        $buttonNode->setButtonAction('console.log("test")');

        // Test direct visitor calls
        $simpleHtml = $this->readOnlyRenderer->visitSimpleNode($simpleNode);
        $buttonHtml = $this->readOnlyRenderer->visitButtonNode($buttonNode);

        // Simple node should not have edit links
        $this->assertStringContainsString('Direct Simple', $simpleHtml);
        $this->assertStringNotContainsString('add-icon', $simpleHtml);
        $this->assertStringNotContainsString('remove-icon', $simpleHtml);

        // Button node should not have edit links
        $this->assertStringContainsString('Direct Button', $buttonHtml);
        $this->assertStringContainsString('Direct Action', $buttonHtml);
        $this->assertStringContainsString('console.log(&quot;test&quot;)', $buttonHtml);
        $this->assertStringNotContainsString('add-icon', $buttonHtml);
        $this->assertStringNotContainsString('remove-icon', $buttonHtml);
    }

    public function testDirectVisitorCallsEditable(): void
    {
        $simpleNode = new SimpleNode(10, 'Direct Simple', 5);
        $buttonNode = new ButtonNode(11, 'Direct Button', 5, null, 0);
        $buttonNode->setButtonText('Direct Action');
        $buttonNode->setButtonAction('console.log("test")');

        // Test direct visitor calls
        $simpleHtml = $this->editableRenderer->visitSimpleNode($simpleNode);
        $buttonHtml = $this->editableRenderer->visitButtonNode($buttonNode);

        // Simple node should have edit links
        $this->assertStringContainsString('Direct Simple', $simpleHtml);
        $this->assertStringContainsString('add-icon', $simpleHtml);
        $this->assertStringContainsString('remove-icon', $simpleHtml);

        // Button node should have edit links
        $this->assertStringContainsString('Direct Button', $buttonHtml);
        $this->assertStringContainsString('Direct Action', $buttonHtml);
        $this->assertStringContainsString('console.log(&quot;test&quot;)', $buttonHtml);
        $this->assertStringContainsString('add-icon', $buttonHtml);
        $this->assertStringContainsString('remove-icon', $buttonHtml);
    }

    public function testReadOnlyModeWithSpecialCharacters(): void
    {
        $node = new SimpleNode(15, 'Test & "Special" <Node>', 6);
        $html = $this->readOnlyRenderer->render($node);

        // Should properly escape special characters
        $this->assertStringContainsString('Test &amp; &quot;Special&quot; &lt;Node&gt;', $html);
        $this->assertStringNotContainsString('Test & "Special" <Node>', $html);

        // Should not have edit links
        $this->assertStringNotContainsString('add-icon', $html);
        $this->assertStringNotContainsString('remove-icon', $html);
    }

    public function testEditableModeWithSpecialCharacters(): void
    {
        $node = new SimpleNode(15, 'Test & "Special" <Node>', 6);
        $html = $this->editableRenderer->render($node);

        // Should properly escape special characters
        $this->assertStringContainsString('Test &amp; &quot;Special&quot; &lt;Node&gt;', $html);
        $this->assertStringNotContainsString('Test & "Special" <Node>', $html);

        // Should have edit links with properly escaped URLs
        $this->assertStringContainsString('add-icon', $html);
        $this->assertStringContainsString('remove-icon', $html);
        $this->assertStringContainsString('/tree/6/node/15/delete', $html);
        $this->assertStringContainsString('/tree/6/add-node?parent_id=15', $html);
    }
}
