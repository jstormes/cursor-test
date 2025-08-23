<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\RestoreTreeAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class RestoreTreeActionTest extends TestCase
{
    private RestoreTreeAction $action;
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

        $this->action = new RestoreTreeAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testGetShowsConfirmationForm(): void
    {
        $deletedTree = new Tree(1, 'Deleted Tree', 'A deleted tree', new DateTime('2023-01-01 10:00:00'), new DateTime('2023-01-02 15:30:00'), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($deletedTree);

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
                       str_contains($html, '<title>Restore Tree - Deleted Tree</title>') &&
                       str_contains($html, 'Are you sure you want to restore this tree?') &&
                       str_contains($html, 'Deleted Tree') &&
                       str_contains($html, 'A deleted tree') &&
                       str_contains($html, 'Tree ID:</strong> 1') &&
                       str_contains($html, 'Jan 1, 2023') &&
                       str_contains($html, 'Status:</strong> <span style="color: #dc3545;">Deleted</span>') &&
                       str_contains($html, '🔄 Restore Tree') &&
                       str_contains($html, 'method="POST"') &&
                       str_contains($html, 'href="/trees/deleted"') &&
                       str_contains($html, '← Back to Trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTreeNotFound(): void
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
                return str_contains($html, '<title>Tree Not Found</title>') &&
                       str_contains($html, 'Tree Not Found') &&
                       str_contains($html, 'Tree with ID 999 was not found in the database') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetAlreadyActiveTree(): void
    {
        $activeTree = new Tree(1, 'Active Tree', 'An active tree', new DateTime(), new DateTime(), true);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($activeTree);

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
                return str_contains($html, '<title>Tree Already Active</title>') &&
                       str_contains($html, 'Tree Already Active') &&
                       str_contains($html, 'The tree "Active Tree" is already active') &&
                       str_contains($html, 'href="/tree/1"') &&
                       str_contains($html, 'View Tree') &&
                       str_contains($html, '← Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostSuccessfulRestore(): void
    {
        $deletedTree = new Tree(1, 'Restore Me', 'Tree to be restored', new DateTime(), new DateTime(), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($deletedTree);

        $this->treeRepository->expects($this->once())
            ->method('restore')
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
                return str_contains($html, '<title>Tree Restored Successfully</title>') &&
                       str_contains($html, 'Tree Restored Successfully!') &&
                       str_contains($html, 'The tree "Restore Me" has been restored') &&
                       str_contains($html, 'now visible in the main trees list') &&
                       str_contains($html, 'What happens next?') &&
                       str_contains($html, 'The tree is now visible in the main trees list') &&
                       str_contains($html, 'All tree nodes and data are fully accessible') &&
                       str_contains($html, 'href="/tree/1"') &&
                       str_contains($html, 'View Tree') &&
                       str_contains($html, '← Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostTreeNotFound(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeRepository->expects($this->never())
            ->method('restore');

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

    public function testPostAlreadyActiveTree(): void
    {
        $activeTree = new Tree(1, 'Active Tree', 'Already active', new DateTime(), new DateTime(), true);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($activeTree);

        $this->treeRepository->expects($this->never())
            ->method('restore');

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
                return str_contains($html, 'Tree Already Active') &&
                       str_contains($html, 'is already active and visible');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testUnsupportedMethod(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('DELETE');

        $this->treeRepository->expects($this->never())
            ->method('findById');

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->willReturnSelf();

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPutMethodNotAllowed(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('PUT');

        $this->treeRepository->expects($this->never())
            ->method('findById');

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
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in restore tree action: Database connection failed'));

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
                return str_contains($html, '<title>Error Restoring Tree</title>') &&
                       str_contains($html, 'Error Restoring Tree') &&
                       str_contains($html, 'Database connection failed') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testHtmlStructureAndContent(): void
    {
        $deletedTree = new Tree(42, 'Structure Test', 'Testing HTML structure', new DateTime('2023-05-15 14:30:00'), new DateTime('2023-06-20 09:15:30'), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($deletedTree);

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
                       str_contains($html, '.container {') &&
                       str_contains($html, '.header {') &&
                       str_contains($html, '.tree-info {') &&
                       str_contains($html, '.btn {') &&
                       str_contains($html, 'linear-gradient') &&
                       str_contains($html, '@media (max-width: 768px)') &&
                       str_contains($html, '<meta charset="UTF-8">') &&
                       str_contains($html, '<meta name="viewport"') &&
                       str_contains($html, 'content="width=device-width, initial-scale=1.0"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '42']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNullDescription(): void
    {
        $deletedTree = new Tree(1, 'No Description Tree', null, new DateTime(), new DateTime(), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($deletedTree);

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
                       str_contains($html, 'No description');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testConfirmationFormContent(): void
    {
        $deletedTree = new Tree(5, 'Confirm Test', 'Testing confirmation', new DateTime('2023-01-15 10:30:00'), new DateTime('2023-02-20 16:45:00'), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($deletedTree);

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
                return str_contains($html, 'class="confirmation-container"') &&
                       str_contains($html, 'class="tree-info"') &&
                       str_contains($html, 'Tree Details:') &&
                       str_contains($html, '<strong>Name:</strong> Confirm Test') &&
                       str_contains($html, '<strong>Description:</strong> Testing confirmation') &&
                       str_contains($html, '<strong>Tree ID:</strong> 5') &&
                       str_contains($html, '<strong>Created:</strong>') &&
                       str_contains($html, 'Jan 15, 2023') &&
                       str_contains($html, 'ℹ️ Information') &&
                       str_contains($html, 'This action will:') &&
                       str_contains($html, 'Make the tree visible in the main trees list') &&
                       str_contains($html, 'Restore all tree nodes and data') &&
                       str_contains($html, 'Allow normal tree operations again') &&
                       str_contains($html, 'Keep all existing tree structure intact') &&
                       str_contains($html, 'class="form-actions"') &&
                       str_contains($html, '<form method="POST"') &&
                       str_contains($html, 'type="submit"') &&
                       str_contains($html, 'class="btn btn-success"') &&
                       str_contains($html, 'class="btn btn-secondary"') &&
                       str_contains($html, 'Cancel') &&
                       str_contains($html, '← Back to Trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testSuccessPageContent(): void
    {
        $restoredTree = new Tree(10, 'Successfully Restored', 'This tree was restored', new DateTime(), new DateTime(), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($restoredTree);

        $this->treeRepository->expects($this->once())
            ->method('restore')
            ->with(10);

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
                return str_contains($html, 'Tree Restored Successfully!') &&
                       str_contains($html, 'class="success"') &&
                       str_contains($html, '"Successfully Restored" has been restored') &&
                       str_contains($html, 'What happens next?') &&
                       str_contains($html, 'text-align: left; max-width: 400px') &&
                       str_contains($html, 'The tree is now visible in the main trees list') &&
                       str_contains($html, 'All tree nodes and data are fully accessible') &&
                       str_contains($html, 'You can view, edit, and manage the tree normally') &&
                       str_contains($html, 'The tree can be deleted again if needed') &&
                       str_contains($html, 'href="/tree/10"') &&
                       str_contains($html, 'class="btn btn-primary"') &&
                       str_contains($html, 'View Tree') &&
                       str_contains($html, 'class="btn btn-secondary"') &&
                       str_contains($html, '← Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '10']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
