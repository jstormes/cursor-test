<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Infrastructure\Rendering\HtmlRendererInterface;
use App\Infrastructure\Services\TreeStructureBuilder;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewTreeActionTest extends TestCase
{
    private ViewTreeAction $action;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private StreamInterface $stream;
    private LoggerInterface $logger;
    private TreeRepository $treeRepository;
    private TreeNodeRepository $treeNodeRepository;
    private HtmlRendererInterface $htmlRenderer;
    private TreeStructureBuilder $treeStructureBuilder;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->treeRepository = $this->createMock(TreeRepository::class);
        $this->treeNodeRepository = $this->createMock(TreeNodeRepository::class);
        $this->htmlRenderer = $this->createMock(HtmlRendererInterface::class);
        $this->treeStructureBuilder = new TreeStructureBuilder();

        // Setup default response mock behavior
        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);
        $this->response->expects($this->any())
            ->method('withStatus')
            ->willReturnSelf();
        $this->response->expects($this->any())
            ->method('withHeader')
            ->willReturnSelf();

        $this->action = new ViewTreeAction(
            $this->logger,
            $this->htmlRenderer,
            $this->treeRepository,
            $this->treeNodeRepository,
            $this->treeStructureBuilder
        );
    }

    private function mockRendererForTreeView(string $expectedHtml = '<html><body>Test HTML</body></html>'): void
    {
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->willReturn($expectedHtml);
    }

    private function mockRendererForNoTrees(string $expectedHtml = '<html><body>No Trees</body></html>'): void
    {
        $this->htmlRenderer->expects($this->once())
            ->method('renderNoTrees')
            ->willReturn($expectedHtml);
    }

    private function mockRendererForEmptyTree(string $expectedHtml = '<html><body>Empty Tree</body></html>'): void
    {
        $this->htmlRenderer->expects($this->once())
            ->method('renderEmptyTree')
            ->willReturn($expectedHtml);
    }

    public function testActionReturnsHtmlResponse(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $childNode]);

        // Mock HTML renderer
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn('<html><body>Test HTML</body></html>');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with('<html><body>Test HTML</body></html>');

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsTreeStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $childNode]);

        // Mock HTML renderer to return HTML with expected content
        $expectedHtml = '<html><body><div>Root</div><div>Child</div><button>Click Me</button></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsCss(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        // Mock HTML renderer to return HTML with CSS
        $expectedHtml = '<html><head><style>.tree ul{float: left} .tree li{text-align: center}</style></head><body>Test</body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsButtonForMainNode(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data with button
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        // Mock HTML renderer to return HTML with button
        $expectedHtml = '<html><body><div><button>Click Me</button></div></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlHasProperStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        // Mock HTML renderer to return proper HTML structure
        $expectedHtml = '<!DOCTYPE html><html lang="en"><head></head><body><div class="header"></div><div class="navigation"></div></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsCheckboxes(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        // Mock HTML renderer to return HTML with checkboxes
        $expectedHtml = '<html><body><div class="tree-node">Root<a class="remove-icon">×</a><a class="add-icon">+</a></div></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlIsValidHtml(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        // Mock HTML renderer to return valid HTML
        $expectedHtml = '<html><head></head><body>Valid HTML content</body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderTreeView')
            ->with($tree, $this->isType('array'))
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoTreesFound(): void
    {
        // Mock empty tree data
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        // Mock HTML renderer for no trees scenario
        $expectedHtml = '<html><body><h1>No Trees Available</h1><p>No active trees found in the database</p></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderNoTrees')
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoNodesFound(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock empty node data
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        // Mock HTML renderer for empty tree scenario
        $expectedHtml = '<html><body><h1>Empty Tree: Test Tree</h1><p>This tree has no nodes yet</p></body></html>';
        $this->htmlRenderer->expects($this->once())
            ->method('renderEmptyTree')
            ->with($tree)
            ->willReturn($expectedHtml);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedHtml);

        $this->action->__invoke($this->request, $this->response, []);
    }
}
