<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Rendering;

use App\Infrastructure\Rendering\TreeHtmlRenderer;
use App\Infrastructure\Rendering\CssProviderInterface;
use App\Domain\Tree\Tree;
use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\SimpleNode;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;

class TreeHtmlRendererTest extends TestCase
{
    private TreeHtmlRenderer $renderer;
    private CssProviderInterface|MockObject $mockCssProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCssProvider = $this->createMock(CssProviderInterface::class);
        $this->renderer = new TreeHtmlRenderer($this->mockCssProvider);
    }

    public function testRenderTreeViewWithNodes(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Test Description', new DateTime('2023-01-01 10:00:00'), new DateTime('2023-01-02 15:30:00'), true, new MockClock());
        $rootNodes = [
            new SimpleNode(1, 'Root Node 1', 1),
            new ButtonNode(2, 'Root Node 2', 1, null, 0, ['href' => '#', 'class' => 'primary'])
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { font-family: Arial; }');

        $result = $this->renderer->renderTreeView($tree, $rootNodes);

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html lang="en">', $result);
        $this->assertStringContainsString('Tree Structure - Test Tree', $result);
        $this->assertStringContainsString('Tree Structure: Test Tree', $result);
        $this->assertStringContainsString('Test Description', $result);
        $this->assertStringContainsString('Tree ID: 1', $result);
        $this->assertStringContainsString('Created: Jan 1, 2023 10:00 AM', $result);
        $this->assertStringContainsString('<div class="tree"><ul>', $result);
        $this->assertStringContainsString('Root Node 1', $result);
        $this->assertStringContainsString('Root Node 2', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('href="/tree/json"', $result);
        $this->assertStringContainsString('body { font-family: Arial; }', $result);
    }

    public function testRenderTreeViewWithNullDescription(): void
    {
        $tree = new Tree(2, 'Tree Without Description', null, new DateTime('2023-02-01'), null, true, new MockClock());
        $rootNodes = [new SimpleNode(1, 'Single Node', 2)];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { color: black; }');

        $result = $this->renderer->renderTreeView($tree, $rootNodes);

        $this->assertStringContainsString('Tree Without Description', $result);
        $this->assertStringContainsString('No description available', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testRenderTreeViewEscapesHtmlSpecialChars(): void
    {
        $tree = new Tree(3, '<script>alert("xss")</script>', '<img src="x" onerror="alert(1)">', new DateTime('2023-03-01'), null, true, new MockClock());
        $rootNodes = [];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { margin: 0; }');

        $result = $this->renderer->renderTreeView($tree, $rootNodes);

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
        $this->assertStringContainsString('&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;', $result);
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $result);
        $this->assertStringNotContainsString('<img src="x"', $result);
    }

    public function testRenderTreeListWithTrees(): void
    {
        $trees = [
            new Tree(1, 'First Tree', 'First description', new DateTime('2023-01-01 08:30:00'), null, true, new MockClock()),
            new Tree(2, 'Second Tree', null, new DateTime('2023-02-15 14:45:00'), null, true, new MockClock())
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { background: white; }');

        $result = $this->renderer->renderTreeList($trees);

        $this->assertStringContainsString('Trees List', $result);
        $this->assertStringContainsString('All available trees in the system', $result);
        $this->assertStringContainsString('First Tree', $result);
        $this->assertStringContainsString('First description', $result);
        $this->assertStringContainsString('Second Tree', $result);
        $this->assertStringContainsString('No description', $result);
        $this->assertStringContainsString('href="/tree/1"', $result);
        $this->assertStringContainsString('href="/tree/2"', $result);
        $this->assertStringContainsString('href="/tree/add"', $result);
        $this->assertStringContainsString('Created: Jan 1, 2023 8:30 AM', $result);
        $this->assertStringContainsString('Created: Feb 15, 2023 2:45 PM', $result);
    }

    public function testRenderTreeListWithEmptyArray(): void
    {
        $trees = [];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { padding: 20px; }');

        $result = $this->renderer->renderTreeList($trees);

        $this->assertStringContainsString('Trees List', $result);
        $this->assertStringContainsString('All available trees in the system', $result);
        $this->assertStringContainsString('href="/tree/add"', $result);
        $this->assertStringNotContainsString('href="/tree/1"', $result);
    }

    public function testRenderNoTrees(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getSimplePageCSS')
            ->willReturn('.message { color: gray; }');

        $result = $this->renderer->renderNoTrees();

        $this->assertStringContainsString('No Trees Available', $result);
        $this->assertStringContainsString('No active trees found in the database.', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('.message { color: gray; }', $result);
    }

    public function testRenderEmptyTree(): void
    {
        $tree = new Tree(5, 'Empty Tree Name', 'Empty tree description', new DateTime('2023-05-01'), null, true, new MockClock());

        $this->mockCssProvider->expects($this->once())
            ->method('getSimplePageCSS')
            ->willReturn('.message { font-size: 16px; }');

        $result = $this->renderer->renderEmptyTree($tree);

        $this->assertStringContainsString('Empty Tree - Empty Tree Name', $result);
        $this->assertStringContainsString('Empty Tree: Empty Tree Name', $result);
        $this->assertStringContainsString('This tree has no nodes yet.', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('.message { font-size: 16px; }', $result);
    }

    public function testRenderEmptyTreeEscapesHtml(): void
    {
        $tree = new Tree(6, '<b>Bold Tree</b>', 'Description', new DateTime('2023-06-01'), null, true, new MockClock());

        $this->mockCssProvider->expects($this->once())
            ->method('getSimplePageCSS')
            ->willReturn('body { margin: 0; }');

        $result = $this->renderer->renderEmptyTree($tree);

        $this->assertStringContainsString('&lt;b&gt;Bold Tree&lt;/b&gt;', $result);
        $this->assertStringNotContainsString('<b>Bold Tree</b>', $result);
    }

    public function testRenderNoRootNodes(): void
    {
        $tree = new Tree(7, 'No Root Nodes Tree', 'Has nodes but no root', new DateTime('2023-07-01'), null, true, new MockClock());

        $this->mockCssProvider->expects($this->once())
            ->method('getSimplePageCSS')
            ->willReturn('.message { color: orange; }');

        $result = $this->renderer->renderNoRootNodes($tree);

        $this->assertStringContainsString('No Root Nodes - No Root Nodes Tree', $result);
        $this->assertStringContainsString('No Root Nodes: No Root Nodes Tree', $result);
        $this->assertStringContainsString('This tree has nodes but no root nodes found.', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('.message { color: orange; }', $result);
    }

    public function testRenderError(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getErrorPageCSS')
            ->willReturn('.error { color: red; }');

        $result = $this->renderer->renderError('Something went wrong');

        $this->assertStringContainsString('<title>Error</title>', $result);
        $this->assertStringContainsString('<h1>Error</h1>', $result);
        $this->assertStringContainsString('Something went wrong', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('.error { color: red; }', $result);
    }

    public function testRenderErrorWithCustomTitle(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getErrorPageCSS')
            ->willReturn('.error { font-weight: bold; }');

        $result = $this->renderer->renderError('Database error', 'Database Connection Failed');

        $this->assertStringContainsString('<title>Database Connection Failed</title>', $result);
        $this->assertStringContainsString('<h1>Database Connection Failed</h1>', $result);
        $this->assertStringContainsString('Database error', $result);
        $this->assertStringContainsString('.error { font-weight: bold; }', $result);
    }

    public function testRenderErrorEscapesHtml(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getErrorPageCSS')
            ->willReturn('body { background: red; }');

        $result = $this->renderer->renderError('<script>alert("error")</script>', '<img src="x">');

        $this->assertStringContainsString('&lt;img src=&quot;x&quot;&gt;', $result);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;error&quot;)&lt;/script&gt;', $result);
        $this->assertStringNotContainsString('<script>alert("error")</script>', $result);
        $this->assertStringNotContainsString('<img src="x">', $result);
    }

    public function testRenderForm(): void
    {
        $formHtml = '<form><input type="text" name="name"><button>Submit</button></form>';
        $navigationLinks = [
            'Back to Trees' => '/trees',
            'Home' => '/'
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('form { margin: 20px; }');

        $result = $this->renderer->renderForm('Add New Tree', $formHtml, $navigationLinks);

        $this->assertStringContainsString('<title>Add New Tree</title>', $result);
        $this->assertStringContainsString('<h1>Add New Tree</h1>', $result);
        $this->assertStringContainsString($formHtml, $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('href="/"', $result);
        $this->assertStringContainsString('Back to Trees', $result);
        $this->assertStringContainsString('Home', $result);
        $this->assertStringContainsString('form { margin: 20px; }', $result);
    }

    public function testRenderFormWithoutNavigation(): void
    {
        $formHtml = '<form><input type="submit" value="Save"></form>';

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('input { padding: 10px; }');

        $result = $this->renderer->renderForm('Simple Form', $formHtml);

        $this->assertStringContainsString('Simple Form', $result);
        $this->assertStringContainsString($formHtml, $result);
        $this->assertStringNotContainsString('<div class="navigation">', $result);
    }

    public function testRenderFormEscapesNavigationLinks(): void
    {
        $formHtml = '<form></form>';
        $navigationLinks = [
            '<script>alert("xss")</script>' => '/malicious',
            'Safe Link' => '<script>alert("href")</script>'
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { margin: 0; }');

        $result = $this->renderer->renderForm('Test Form', $formHtml, $navigationLinks);

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;href&quot;)&lt;/script&gt;', $result);
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $result);
    }

    public function testRenderSuccess(): void
    {
        $navigationLinks = [
            'View Trees' => '/trees',
            'Add Another' => '/tree/add'
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getSuccessPageCSS')
            ->willReturn('.success { color: green; }');

        $result = $this->renderer->renderSuccess('Tree created successfully!', $navigationLinks);

        $this->assertStringContainsString('<title>Success</title>', $result);
        $this->assertStringContainsString('<h1>Success</h1>', $result);
        $this->assertStringContainsString('Tree created successfully!', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('href="/tree/add"', $result);
        $this->assertStringContainsString('View Trees', $result);
        $this->assertStringContainsString('Add Another', $result);
        $this->assertStringContainsString('.success { color: green; }', $result);
    }

    public function testRenderSuccessWithoutNavigation(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getSuccessPageCSS')
            ->willReturn('.success { font-size: 18px; }');

        $result = $this->renderer->renderSuccess('Operation completed!');

        $this->assertStringContainsString('Operation completed!', $result);
        $this->assertStringNotContainsString('href="', $result);
        $this->assertStringContainsString('.success { font-size: 18px; }', $result);
    }

    public function testRenderSuccessEscapesHtml(): void
    {
        $this->mockCssProvider->expects($this->once())
            ->method('getSuccessPageCSS')
            ->willReturn('body { background: green; }');

        $result = $this->renderer->renderSuccess('<script>alert("success")</script>');

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;success&quot;)&lt;/script&gt;', $result);
        $this->assertStringNotContainsString('<script>alert("success")</script>', $result);
    }

    public function testRenderDeletedTrees(): void
    {
        $deletedTrees = [
            new Tree(1, 'Deleted Tree 1', 'First deleted', new DateTime('2023-01-01'), new DateTime('2023-01-05'), false, new MockClock()),
            new Tree(2, 'Deleted Tree 2', null, new DateTime('2023-02-01'), new DateTime('2023-02-10'), false, new MockClock())
        ];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('.deleted { opacity: 0.5; }');

        $result = $this->renderer->renderDeletedTrees($deletedTrees);

        $this->assertStringContainsString('Deleted Trees', $result);
        $this->assertStringContainsString('Trees that have been soft deleted', $result);
        $this->assertStringContainsString('Deleted Tree 1', $result);
        $this->assertStringContainsString('First deleted', $result);
        $this->assertStringContainsString('Deleted Tree 2', $result);
        $this->assertStringContainsString('No description', $result);
        $this->assertStringContainsString('href="/tree/1/restore"', $result);
        $this->assertStringContainsString('href="/tree/2/restore"', $result);
        $this->assertStringContainsString('Restore', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringContainsString('Back to Active Trees', $result);
        $this->assertStringContainsString('Deleted: Jan 5, 2023', $result);
        $this->assertStringContainsString('Deleted: Feb 10, 2023', $result);
    }

    public function testRenderDeletedTreesWithEmptyArray(): void
    {
        $deletedTrees = [];

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { color: black; }');

        $result = $this->renderer->renderDeletedTrees($deletedTrees);

        $this->assertStringContainsString('Deleted Trees', $result);
        $this->assertStringContainsString('Trees that have been soft deleted', $result);
        $this->assertStringContainsString('href="/trees"', $result);
        $this->assertStringNotContainsString('href="/tree/', $result);
        $this->assertStringNotContainsString('Restore', $result);
    }

    public function testRenderPageStructure(): void
    {
        $tree = new Tree(1, 'Test', 'Description', new DateTime('2023-01-01'), null, true, new MockClock());

        $this->mockCssProvider->expects($this->once())
            ->method('getMainCSS')
            ->willReturn('body { font-family: Arial; }');

        $result = $this->renderer->renderTreeView($tree, []);

        // Test HTML5 structure
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html lang="en">', $result);
        $this->assertStringContainsString('<meta charset="UTF-8">', $result);
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1.0">', $result);
        $this->assertStringContainsString('<title>', $result);
        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('</style>', $result);
        $this->assertStringContainsString('<body>', $result);
        $this->assertStringContainsString('</body>', $result);
        $this->assertStringContainsString('</html>', $result);
    }

    public function testCssProviderIntegration(): void
    {
        $tree = new Tree(1, 'CSS Test', null, new DateTime(), null, true, new MockClock());

        // Test that the main CSS and tree CSS are requested
        $this->mockCssProvider->expects($this->atLeastOnce())
            ->method('getMainCSS')
            ->willReturn('/* Main CSS */');

        $this->mockCssProvider->expects($this->atLeastOnce())
            ->method('getTreeCSS')
            ->with('standard')
            ->willReturn('/* Tree CSS */');

        $this->renderer->renderTreeView($tree, []);

        // Test that error CSS is requested
        $this->mockCssProvider->expects($this->once())
            ->method('getErrorPageCSS')
            ->willReturn('/* Error CSS */');

        $this->renderer->renderError('Test error');

        // Test that simple CSS is requested
        $this->mockCssProvider->expects($this->once())
            ->method('getSimplePageCSS')
            ->willReturn('/* Simple CSS */');

        $this->renderer->renderNoTrees();

        // Test that success CSS is requested
        $this->mockCssProvider->expects($this->once())
            ->method('getSuccessPageCSS')
            ->willReturn('/* Success CSS */');

        $this->renderer->renderSuccess('Test success');
    }

    public function testRendererIsStateless(): void
    {
        $tree1 = new Tree(1, 'Tree 1', null, new DateTime(), null, true, new MockClock());
        $tree2 = new Tree(2, 'Tree 2', null, new DateTime(), null, true, new MockClock());

        $this->mockCssProvider->expects($this->exactly(2))
            ->method('getMainCSS')
            ->willReturn('body { margin: 0; }');

        $result1 = $this->renderer->renderTreeView($tree1, []);
        $result2 = $this->renderer->renderTreeView($tree2, []);

        $this->assertStringContainsString('Tree 1', $result1);
        $this->assertStringNotContainsString('Tree 2', $result1);
        $this->assertStringContainsString('Tree 2', $result2);
        $this->assertStringNotContainsString('Tree 1', $result2);
    }
}
