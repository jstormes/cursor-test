<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeByIdAction;
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

class ViewTreeByIdActionTest extends TestCase
{
    private ViewTreeByIdAction $action;
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

        $this->action = new ViewTreeByIdAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testViewTreeWithNodes(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

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
                       str_contains($html, 'Tree Structure: Test Tree') &&
                       str_contains($html, 'A test tree') &&
                       str_contains($html, '<div class="tree">') &&
                       str_contains($html, 'Root') &&
                       str_contains($html, 'Add Node') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeNotFound(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeNodeRepository->expects($this->never())
            ->method('findByTreeId');

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
                return str_contains($html, 'Tree Not Found') &&
                       str_contains($html, 'Tree with ID 999 was not found') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoNodes(): void
    {
        $tree = new Tree(1, 'Empty Tree', 'An empty tree', new DateTime(), new DateTime(), true);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

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
                return str_contains($html, 'Tree Structure: Empty Tree') &&
                       str_contains($html, 'Empty tree - add your first node') &&
                       str_contains($html, 'Add Node') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoRootNodes(): void
    {
        $tree = new Tree(1, 'Broken Tree', 'A tree with no root nodes', new DateTime(), new DateTime(), true);

        // All nodes have parents (no root nodes)
        $childNode1 = new SimpleNode(1, 'Child1', 1, 999, 0); // Parent doesn't exist
        $childNode2 = new SimpleNode(2, 'Child2', 1, 888, 0); // Parent doesn't exist

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$childNode1, $childNode2]);

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
                return str_contains($html, 'Invalid Tree Structure: Broken Tree') &&
                       str_contains($html, 'This tree has nodes but no root nodes found') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithComplexHierarchy(): void
    {
        $tree = new Tree(1, 'Complex Tree', 'A tree with multiple levels', new DateTime(), new DateTime(), true);

        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $child1 = new SimpleNode(2, 'Child1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child2', 1, 1, 1);
        $grandChild = new SimpleNode(4, 'GrandChild', 1, 2, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $child1, $child2, $grandChild]);

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
                return str_contains($html, 'Tree Structure: Complex Tree') &&
                       str_contains($html, 'A tree with multiple levels') &&
                       str_contains($html, '<div class="tree">') &&
                       str_contains($html, 'Root');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithButtonNodes(): void
    {
        $tree = new Tree(1, 'Button Tree', 'A tree with button nodes', new DateTime(), new DateTime(), true);

        $rootNode = new ButtonNode(1, 'Root Button', 1, null, 0, ['button_text' => 'Click Me']);
        $simpleChild = new SimpleNode(2, 'Simple Child', 1, 1, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $simpleChild]);

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
                return str_contains($html, 'Tree Structure: Button Tree') &&
                       str_contains($html, '<div class="tree">') &&
                       str_contains($html, 'Click Me');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error loading tree by ID: Database error'));

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
                return str_contains($html, 'Error Loading Tree') &&
                       str_contains($html, 'Database error') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testHtmlStructureAndContent(): void
    {
        $tree = new Tree(1, 'HTML Test Tree', 'Testing HTML output', new DateTime('2023-01-01 12:00:00'), new DateTime(), true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

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
                       str_contains($html, '<html lang="en">') &&
                       str_contains($html, '<head>') &&
                       str_contains($html, '<body>') &&
                       str_contains($html, '<style>') &&
                       str_contains($html, 'Tree ID: 1') &&
                       str_contains($html, 'Created: Jan 1, 2023') &&
                       str_contains($html, '/tree/1/add-node') &&
                       str_contains($html, '/tree/1/json') &&
                       str_contains($html, '/trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNullDescription(): void
    {
        $tree = new Tree(1, 'No Description Tree', null, new DateTime(), new DateTime(), true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

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
                return str_contains($html, 'No Description Tree') &&
                       str_contains($html, 'No description available');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCssIsIncludedInResponse(): void
    {
        $tree = new Tree(1, 'CSS Test Tree', 'Testing CSS', new DateTime(), new DateTime(), true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

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
                return str_contains($html, '.header {') &&
                       str_contains($html, '.tree ul {') &&
                       str_contains($html, '.tree li {') &&
                       str_contains($html, '.btn {') &&
                       str_contains($html, 'linear-gradient') &&
                       str_contains($html, '@media (max-width: 768px)');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testBuildTreeFromNodesMethod(): void
    {
        $tree = new Tree(1, 'Hierarchy Test', 'Testing tree building', new DateTime(), new DateTime(), true);

        // Create a more complex tree structure
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $child1 = new SimpleNode(2, 'Child1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child2', 1, 1, 1);
        $grandChild1 = new SimpleNode(4, 'GrandChild1', 1, 2, 0);
        $grandChild2 = new SimpleNode(5, 'GrandChild2', 1, 2, 1);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $child1, $child2, $grandChild1, $grandChild2]);

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
                // Verify the tree structure is properly built
                return str_contains($html, 'Root') &&
                       str_contains($html, '<div class="tree">') &&
                       str_contains($html, '<ul>') &&
                       str_contains($html, '<li>');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
