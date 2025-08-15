<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
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

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->treeRepository = $this->createMock(TreeRepository::class);
        $this->treeNodeRepository = $this->createMock(TreeNodeRepository::class);

        $this->action = new ViewTreeAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testActionReturnsHtmlResponse(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
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

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<!DOCTYPE html>') &&
                       str_contains($html, '<title>Tree Structure - Test Tree</title>') &&
                       str_contains($html, 'Tree Structure: Test Tree');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsTreeStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
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

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Root') &&
                       str_contains($html, 'Child') &&
                       str_contains($html, 'Click Me');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsCss(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<style>') &&
                       str_contains($html, '.tree ul') &&
                       str_contains($html, '.tree li') &&
                       str_contains($html, 'float: left') &&
                       str_contains($html, 'text-align: center');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsButtonForMainNode(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data with button
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Click Me') &&
                       str_contains($html, '<button>');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlHasProperStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
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

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<!DOCTYPE html>') &&
                       str_contains($html, '<html lang="en">') &&
                       str_contains($html, '<head>') &&
                       str_contains($html, '<body>') &&
                       str_contains($html, '<div class="header">') &&
                       str_contains($html, '<div class="navigation">');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlContainsCheckboxes(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
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

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Root') &&
                       str_contains($html, '<div class="tree-node">') &&
                       str_contains($html, 'class="remove-icon">×</a>') &&
                       str_contains($html, 'class="add-icon">+</a>');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testGeneratedHtmlIsValidHtml(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
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

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<html') &&
                       str_contains($html, '</html>') &&
                       str_contains($html, '<head') &&
                       str_contains($html, '</head>') &&
                       str_contains($html, '<body') &&
                       str_contains($html, '</body>');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoTreesFound(): void
    {
        // Mock empty tree data
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'No Trees Available') &&
                       str_contains($html, 'No active trees found in the database');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoNodesFound(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock empty node data
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Empty Tree: Test Tree') &&
                       str_contains($html, 'This tree has no nodes yet');
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }
}
