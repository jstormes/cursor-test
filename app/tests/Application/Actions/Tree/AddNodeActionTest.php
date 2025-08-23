<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\AddNodeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class AddNodeActionTest extends TestCase
{
    private AddNodeAction $action;
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

        $this->action = new AddNodeAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testGetRequestShowsForm(): void
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
                return str_contains($html, 'Add Node to Tree') &&
                       str_contains($html, 'Test Tree') &&
                       str_contains($html, '<form method="POST"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithNonExistentTree(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

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
                       str_contains($html, 'Tree with ID 999 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestCreatesSimpleNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Node',
                'node_type' => 'SimpleNode',
                'sort_order' => '0'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($node) {
                return $node instanceof SimpleNode &&
                       $node->getName() === 'Test Node' &&
                       $node->getTreeId() === 1 &&
                       $node->getParentId() === null &&
                       $node->getSortOrder() === 0;
            }));

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
                return str_contains($html, 'Node Created Successfully') &&
                       str_contains($html, 'Test Node');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestCreatesButtonNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Button Node',
                'node_type' => 'ButtonNode',
                'button_text' => 'Click Me',
                'button_action' => 'alert("clicked")',
                'sort_order' => '1'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($node) {
                return $node instanceof ButtonNode &&
                       $node->getName() === 'Button Node' &&
                       $node->getTreeId() === 1 &&
                       $node->getSortOrder() === 1;
            }));

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
                return str_contains($html, 'Node Created Successfully') &&
                       str_contains($html, 'Button Node');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithEmptyName(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => '',
                'node_type' => 'SimpleNode'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->treeNodeRepository->expects($this->never())
            ->method('save');

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
                return str_contains($html, 'Node name is required');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithTooLongName(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $longName = str_repeat('A', 256);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => $longName,
                'node_type' => 'SimpleNode'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->treeNodeRepository->expects($this->never())
            ->method('save');

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
                return str_contains($html, 'Node name must be 255 characters or less');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestButtonNodeWithoutButtonText(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Button Node',
                'node_type' => 'ButtonNode',
                'button_text' => ''
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->treeNodeRepository->expects($this->never())
            ->method('save');

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
                return str_contains($html, 'Button text is required for ButtonNode');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithInvalidParentNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Child Node',
                'parent_id' => '999',
                'node_type' => 'SimpleNode'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->treeNodeRepository->expects($this->never())
            ->method('save');

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
                return str_contains($html, 'Invalid parent node selected');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithValidParentNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());
        $parentNode = new SimpleNode(5, 'Parent', 1, null, 0);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Child Node',
                'parent_id' => '5',
                'node_type' => 'SimpleNode'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($parentNode);

        $this->treeNodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($node) {
                return $node instanceof SimpleNode &&
                       $node->getName() === 'Child Node' &&
                       $node->getParentId() === 5;
            }));

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
                return str_contains($html, 'Node Created Successfully');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testUnsupportedHttpMethod(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('DELETE');

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->willReturnSelf();

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error showing add node form: Database error'));

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
                return str_contains($html, 'Error Loading Form') &&
                       str_contains($html, 'Database error');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
