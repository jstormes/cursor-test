<?php

declare(strict_types=1);

namespace App\Tests\Domain\Tree;

use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;
use PHPUnit\Framework\TestCase;

class HtmlTreeNodeRendererExtendedTest extends TestCase
{
    private HtmlTreeNodeRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new HtmlTreeNodeRenderer();
    }

    public function testRenderNodeWithSpecialCharacters(): void
    {
        $node = new SimpleNode('Node with <script>alert("xss")</script>');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node with &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testRenderButtonNodeWithSpecialCharacters(): void
    {
        $node = new ButtonNode('Button with <script>', 'Click <here>');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Button with &lt;script&gt;', $html);
        $this->assertStringContainsString('Click &lt;here&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testRenderButtonNodeWithAction(): void
    {
        $node = new ButtonNode('Test Button', 'Click Me', 'alert("test")');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('onclick="alert(&quot;test&quot;)"', $html);
        $this->assertStringContainsString('Click Me', $html);
    }

    public function testRenderButtonNodeWithoutAction(): void
    {
        $node = new ButtonNode('Test Button', 'Click Me');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('<button>Click Me</button>', $html);
        $this->assertStringNotContainsString('onclick=', $html);
    }

    public function testRenderNodeWithEmptyName(): void
    {
        $node = new SimpleNode('');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('<div>', $html);
        $this->assertStringContainsString('</div>', $html);
    }

    public function testRenderNodeWithVeryLongName(): void
    {
        $longName = str_repeat('A', 1000);
        $node = new SimpleNode($longName);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString(htmlspecialchars($longName), $html);
    }

    public function testRenderNodeWithUnicodeCharacters(): void
    {
        $node = new SimpleNode('Node with émojis 🎉 and unicode 中文');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node with émojis 🎉 and unicode 中文', $html);
    }

    public function testRenderButtonNodeWithUnicodeButtonText(): void
    {
        $node = new ButtonNode('Test', 'Click 🎉 中文', 'alert("test")');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Click 🎉 中文', $html);
    }

    public function testRenderNodeWithQuotesInName(): void
    {
        $node = new SimpleNode('Node with "quotes" and \'apostrophes\'');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node with &quot;quotes&quot; and &#039;apostrophes&#039;', $html);
    }

    public function testRenderButtonNodeWithQuotesInAction(): void
    {
        $node = new ButtonNode('Test', 'Click', 'alert("Hello \"World\"")');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('onclick="alert(&quot;Hello \&quot;World\&quot;&quot;)"', $html);
    }

    public function testRenderNodeWithAmpersand(): void
    {
        $node = new SimpleNode('Node & Company');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node &amp; Company', $html);
    }

    public function testRenderButtonNodeWithAmpersandInText(): void
    {
        $node = new ButtonNode('Test', 'Click & Save', 'save()');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Click &amp; Save', $html);
    }

    public function testRenderNodeWithLessThanAndGreaterThan(): void
    {
        $node = new SimpleNode('Node < 5 and > 0');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node &lt; 5 and &gt; 0', $html);
    }

    public function testRenderButtonNodeWithComplexAction(): void
    {
        $action = 'console.log("test"); alert("hello"); return false;';
        $node = new ButtonNode('Test', 'Click', $action);
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('onclick="console.log(&quot;test&quot;); alert(&quot;hello&quot;); return false;"', $html);
    }

    public function testRenderNodeWithNewlines(): void
    {
        $node = new SimpleNode("Node with\nnewlines\nand\ttabs");
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Node with', $html);
        $this->assertStringContainsString('newlines', $html);
        $this->assertStringContainsString('and', $html);
        $this->assertStringContainsString('tabs', $html);
    }

    public function testRenderButtonNodeWithNewlinesInText(): void
    {
        $node = new ButtonNode('Test', "Click\nMe\nNow", 'test()');
        $html = $this->renderer->render($node);
        
        $this->assertStringContainsString('Click', $html);
        $this->assertStringContainsString('Me', $html);
        $this->assertStringContainsString('Now', $html);
    }
} 