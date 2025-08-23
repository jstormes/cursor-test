<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\DeleteTreeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class DeleteTreeActionTest extends TestCase
{
    private DeleteTreeAction $action;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private StreamInterface $stream;
    private LoggerInterface $logger;
    private TreeRepository $treeRepository;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->treeRepository = $this->createMock(TreeRepository::class);

        $this->action = new DeleteTreeAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testGetRequestShowsConfirmationForm(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

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
                return str_contains($html, 'Delete Tree') &&
                       str_contains($html, 'Test Tree') &&
                       str_contains($html, 'Are you sure you want to delete this tree?') &&
                       str_contains($html, '<form method="POST"') &&
                       str_contains($html, '🗑️ Delete Tree');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
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

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestWithAlreadyDeletedTree(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), false, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

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
                return str_contains($html, 'Tree Already Deleted') &&
                       str_contains($html, 'Test Tree') &&
                       str_contains($html, 'has already been deleted');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestDeletesTree(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeRepository->expects($this->once())
            ->method('softDelete')
            ->with(1);

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
                return str_contains($html, 'Tree Deleted Successfully') &&
                       str_contains($html, 'Test Tree') &&
                       str_contains($html, 'moved to the deleted trees list');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithNonExistentTree(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeRepository->expects($this->never())
            ->method('softDelete');

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

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithAlreadyDeletedTree(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), false, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeRepository->expects($this->never())
            ->method('softDelete');

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
                return str_contains($html, 'Tree Already Deleted');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
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

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
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
            ->with($this->stringContains('Error in delete tree action: Database error'));

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
                return str_contains($html, 'Error Deleting Tree') &&
                       str_contains($html, 'Database error');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testConfirmationFormHtmlStructure(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Test description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

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
                       str_contains($html, 'Tree Details:') &&
                       str_contains($html, '⚠️ Warning') &&
                       str_contains($html, 'This action will:') &&
                       str_contains($html, 'Hide the tree from the main trees list') &&
                       str_contains($html, 'Keep all tree nodes and data intact') &&
                       str_contains($html, 'Allow you to restore the tree later');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testSuccessHtmlContent(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeRepository->expects($this->once())
            ->method('softDelete')
            ->with(1);

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
                return str_contains($html, 'What happens next?') &&
                       str_contains($html, 'The tree is now hidden from the main trees list') &&
                       str_contains($html, 'All tree nodes and data are preserved') &&
                       str_contains($html, 'You can restore the tree from the deleted trees section') &&
                       str_contains($html, 'The tree can be permanently deleted later if needed') &&
                       str_contains($html, '← Back to Trees List') &&
                       str_contains($html, 'View Deleted Trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
