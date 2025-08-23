<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeByIdReadOnlyAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Infrastructure\Services\TreeStructureBuilder;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewTreeByIdReadOnlyActionTest extends TestCase
{
    private ViewTreeByIdReadOnlyAction $action;
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

        $treeStructureBuilder = new TreeStructureBuilder();
        
        $this->action = new ViewTreeByIdReadOnlyAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository,
            $treeStructureBuilder
        );
    }

    public function testViewTreeWithNodesReadOnly(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
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

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<!DOCTYPE html>') &&
                       str_contains($html, 'Tree Structure: Test Tree') &&
                       str_contains($html, 'A test tree') &&
                       str_contains($html, '<div class="tree">') &&
                       str_contains($html, 'Root') &&
                       // Verify it's read-only (no add/delete icons)
                       !str_contains($html, 'class="add-icon"') &&
                       !str_contains($html, 'class="remove-icon"') &&
                       !str_contains($html, 'Add Node') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testViewTreeWithButtonNodeReadOnly(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true, new MockClock());
        $buttonNode = new ButtonNode(1, 'Button Node', 1, null, 0);
        $buttonNode->setButtonText('Click Me');
        $buttonNode->setButtonAction('alert("Hello")');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$buttonNode]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Button Node') &&
                       str_contains($html, 'Click Me') &&
                       // Verify button is present but not editable
                       str_contains($html, '<button') &&
                       // Verify no edit icons
                       !str_contains($html, 'add-icon') &&
                       !str_contains($html, 'remove-icon');
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

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree Not Found') &&
                       str_contains($html, 'Tree with ID 999 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoNodes(): void
    {
        $tree = new Tree(1, 'Empty Tree', 'An empty tree', new DateTime(), new DateTime(), true, new MockClock());

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree Structure: Empty Tree') &&
                       str_contains($html, 'Empty tree') &&
                       // Read-only mode should have no add buttons even for empty trees
                       !str_contains($html, 'Add Node') &&
                       !str_contains($html, 'add-icon');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoRootNodes(): void
    {
        $tree = new Tree(1, 'Invalid Tree', 'Tree with invalid structure', new DateTime(), new DateTime(), true, new MockClock());
        $orphanNode = new SimpleNode(1, 'Orphan', 1, 999, 0); // Parent ID 999 doesn't exist

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$orphanNode]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Invalid Tree Structure: Invalid Tree') &&
                       str_contains($html, 'This tree has nodes but no root nodes found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testComplexTreeStructure(): void
    {
        $tree = new Tree(1, 'Complex Tree', 'A complex tree structure', new DateTime(), new DateTime(), true, new MockClock());
        $root1 = new SimpleNode(1, 'Root 1', 1, null, 0);
        $root2 = new SimpleNode(2, 'Root 2', 1, null, 1);
        $child1 = new SimpleNode(3, 'Child 1', 1, 1, 0);
        $child2 = new SimpleNode(4, 'Child 2', 1, 1, 1);
        $grandchild = new SimpleNode(5, 'Grandchild', 1, 3, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$root1, $root2, $child1, $child2, $grandchild]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Complex Tree') &&
                       str_contains($html, 'Root 1') &&
                       str_contains($html, 'Root 2') &&
                       str_contains($html, 'Child 1') &&
                       str_contains($html, 'Child 2') &&
                       str_contains($html, 'Grandchild') &&
                       // Verify it's read-only
                       !str_contains($html, 'add-icon') &&
                       !str_contains($html, 'remove-icon');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error loading tree by ID: Database connection failed');

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Error Loading Tree') &&
                       str_contains($html, 'Database connection failed');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithSpecialCharacters(): void
    {
        $tree = new Tree(1, 'Tree <script>alert("XSS")</script>', 'Description with & special chars', new DateTime(), new DateTime(), true, new MockClock());
        $node = new SimpleNode(1, 'Node with "quotes" & <tags>', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$node]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                // Verify HTML escaping is working
                return str_contains($html, '&lt;script&gt;') &&
                       str_contains($html, '&amp;') &&
                       str_contains($html, '&quot;') &&
                       !str_contains($html, '<script>alert("XSS")</script>');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithInactiveStatus(): void
    {
        $tree = new Tree(1, 'Inactive Tree', 'This tree is inactive', new DateTime(), new DateTime(), false, new MockClock());
        $node = new SimpleNode(1, 'Node in inactive tree', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$node]);

        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->any())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Inactive Tree') &&
                       str_contains($html, 'Node in inactive tree') &&
                       // Still should be read-only regardless of tree status
                       !str_contains($html, 'add-icon') &&
                       !str_contains($html, 'remove-icon');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
