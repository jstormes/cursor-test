<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use App\Domain\Tree\SimpleNode;
use PHPUnit\Framework\TestCase;

class HtmlTreeNodeRendererExtendedTest extends TestCase
{
    private HtmlTreeNodeRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new HtmlTreeNodeRenderer();
    }

    public function testRenderNodeWithSpecialCharacters(): void
    {
        $node = new SimpleNode(1, 'Test & Node', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test &amp; Node', $html);
        $this->assertStringNotContainsString('Test & Node', $html);
    }

    public function testRenderButtonNodeWithSpecialCharacters(): void
    {
        $node = new ButtonNode(1, 'Test & Button', 1, null, 0, ['button_text' => 'Click & Me']);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test &amp; Button', $html);
        $this->assertStringContainsString('Click &amp; Me', $html);
    }

    public function testRenderButtonNodeWithAction(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("test")'
        ]);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('alert(&quot;test&quot;)', $html);
    }

    public function testRenderButtonNodeWithoutAction(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, ['button_text' => 'Click Me']);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function testRenderNodeWithEmptyName(): void
    {
        $node = new SimpleNode(1, '', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('<div', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testRenderNodeWithVeryLongName(): void
    {
        $longName = str_repeat('A', 1000);
        $node = new SimpleNode(1, $longName, 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString($longName, $html);
        $this->assertStringContainsString('<div', $html);
    }

    public function testRenderNodeWithUnicodeCharacters(): void
    {
        $node = new SimpleNode(1, 'Test 测试 Node', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test 测试 Node', $html);
        $this->assertStringContainsString('<div', $html);
    }

    public function testRenderButtonNodeWithUnicodeButtonText(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, ['button_text' => '点击 测试']);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('点击 测试', $html);
        $this->assertStringContainsString('<button', $html);
    }

    public function testRenderNodeWithQuotesInName(): void
    {
        $node = new SimpleNode(1, 'Test "Quote" Node', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test &quot;Quote&quot; Node', $html);
        $this->assertStringContainsString('<div', $html);
    }

    public function testRenderButtonNodeWithQuotesInAction(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'alert("Hello \"World\"")'
        ]);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('alert(&quot;Hello \&quot;World\&quot;&quot;)', $html);
    }

    public function testRenderNodeWithAmpersand(): void
    {
        $node = new SimpleNode(1, 'Test & Node', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test &amp; Node', $html);
        $this->assertStringNotContainsString('Test & Node', $html);
    }

    public function testRenderButtonNodeWithAmpersandInText(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, ['button_text' => 'Click & Me']);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Click &amp; Me', $html);
        $this->assertStringNotContainsString('Click & Me', $html);
    }

    public function testRenderNodeWithLessThanAndGreaterThan(): void
    {
        $node = new SimpleNode(1, 'Test < Node >', 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('Test &lt; Node &gt;', $html);
        $this->assertStringNotContainsString('Test < Node >', $html);
    }

    public function testRenderButtonNodeWithComplexAction(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => 'Click Me',
            'button_action' => 'function() { console.log("test"); return false; }'
        ]);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString('function() { console.log(&quot;test&quot;); return false; }', $html);
    }

    public function testRenderNodeWithNewlines(): void
    {
        $node = new SimpleNode(1, "Test\nNode", 1);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString("Test\nNode", $html);
        $this->assertStringContainsString('<div', $html);
    }

    public function testRenderButtonNodeWithNewlinesInText(): void
    {
        $node = new ButtonNode(1, 'Test Button', 1, null, 0, [
            'button_text' => "Click\nMe"
        ]);
        $html = $this->renderer->render($node);

        $this->assertStringContainsString("Click\nMe", $html);
        $this->assertStringContainsString('<button', $html);
    }
}
