<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\DeleteNodeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class DeleteNodeActionTest extends TestCase
{
    private DeleteNodeAction $action;
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

        $this->action = new DeleteNodeAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testGetRequestShowsConfirmationForm(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $node = new SimpleNode(5, 'Node to Delete', 1, null, 0);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($node);

        $this->treeNodeRepository->expects($this->once())
            ->method('findChildren')
            ->with(5)
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
                return str_contains($html, 'Delete Node') &&
                       str_contains($html, 'Node to Delete') &&
                       str_contains($html, 'form method="POST"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithTreeNotFound(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeNodeRepository->expects($this->never())
            ->method('findById');

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

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '999', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithNodeNotFound(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

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
                return str_contains($html, 'Node Not Found') &&
                       str_contains($html, 'Node with ID 999 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithNodeFromDifferentTree(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $node = new SimpleNode(5, 'Node from Different Tree', 2, null, 0); // Tree ID 2, not 1

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($node);

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
                return str_contains($html, 'Node Not Found') &&
                       str_contains($html, 'Node with ID 5 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestDeletesNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $node = new SimpleNode(5, 'Node to Delete', 1, null, 0);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($node);

        $this->treeNodeRepository->expects($this->once())
            ->method('findChildren')
            ->with(5)
            ->willReturn([]);

        $this->treeNodeRepository->expects($this->once())
            ->method('delete')
            ->with(5);

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
                return str_contains($html, 'Node Deleted Successfully') &&
                       str_contains($html, 'Node to Delete');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestDeletesNodeWithChildren(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $parentNode = new SimpleNode(5, 'Parent Node', 1, null, 0);
        $childNode1 = new SimpleNode(6, 'Child 1', 1, 5, 0);
        $childNode2 = new SimpleNode(7, 'Child 2', 1, 5, 1);
        $grandChild = new SimpleNode(8, 'Grandchild', 1, 6, 0);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($parentNode);

        // Mock the cascading delete calls - allow flexible number of calls
        $this->treeNodeRepository->expects($this->any())
            ->method('findChildren')
            ->willReturnCallback(function ($nodeId) use ($childNode1, $childNode2, $grandChild) {
                switch ($nodeId) {
                    case 5:
                        return [$childNode1, $childNode2]; // Parent's children
                    case 6:
                        return [$grandChild];              // Child1's children
                    case 7:
                        return [];                         // Child2's children (no children)
                    default:
                        return [];
                }
            });

        // Expect delete calls for all nodes
        $this->treeNodeRepository->expects($this->exactly(4))
            ->method('delete')
            ->with($this->logicalOr(5, 6, 7, 8)); // Any of these node IDs

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
                return str_contains($html, 'Node Deleted Successfully') &&
                       str_contains($html, 'Parent Node');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithTreeNotFound(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeNodeRepository->expects($this->never())
            ->method('findById');

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

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '999', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithNodeNotFound(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

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
                return str_contains($html, 'Node Not Found') &&
                       str_contains($html, 'Node with ID 999 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testUnsupportedHttpMethod(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('PUT');

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->willReturnSelf();

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithException(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error showing delete confirmation: Database connection failed');

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
                return str_contains($html, 'Error') &&
                       str_contains($html, 'Database connection failed');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithException(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error deleting node: Database connection failed');

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
                return str_contains($html, 'Error') &&
                       str_contains($html, 'Database connection failed');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1', 'nodeId' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
