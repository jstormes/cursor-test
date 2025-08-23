<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewDeletedTreesAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewDeletedTreesActionTest extends TestCase
{
    private ViewDeletedTreesAction $action;
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

        $this->action = new ViewDeletedTreesAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testViewDeletedTreesWithTrees(): void
    {
        $deletedTree1 = new Tree(1, 'Deleted Tree 1', 'First deleted tree', new DateTime('2023-01-01'), new DateTime('2023-01-02'), false);
        $deletedTree2 = new Tree(2, 'Deleted Tree 2', 'Second deleted tree', new DateTime('2023-02-01'), new DateTime('2023-02-02'), false);

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree1, $deletedTree2]);

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
                       str_contains($html, '🗑️ Deleted Trees') &&
                       str_contains($html, 'Deleted Tree 1') &&
                       str_contains($html, 'Deleted Tree 2') &&
                       str_contains($html, 'First deleted tree') &&
                       str_contains($html, 'Second deleted tree') &&
                       str_contains($html, 'Total deleted trees: <strong>2</strong>') &&
                       str_contains($html, '🔄 Restore') &&
                       str_contains($html, '🗑️ Delete Permanently') &&
                       str_contains($html, '← Back to Active Trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testViewDeletedTreesWithNoTrees(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
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
                return str_contains($html, '🗑️ Deleted Trees') &&
                       str_contains($html, 'No Deleted Trees') &&
                       str_contains($html, 'There are no deleted trees to display') &&
                       str_contains($html, 'empty-state') &&
                       str_contains($html, '← Back to Active Trees');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeCardContent(): void
    {
        $deletedTree = new Tree(
            42,
            'Test Tree Name',
            'Test tree description',
            new DateTime('2023-05-15 14:30:00'),
            new DateTime('2023-06-20 09:15:30'),
            false
        );

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                return str_contains($html, 'Test Tree Name') &&
                       str_contains($html, 'Test tree description') &&
                       str_contains($html, 'ID: 42') &&
                       str_contains($html, 'Created: May 15, 2023') &&
                       str_contains($html, 'Deleted: Jun 20, 2023') &&
                       str_contains($html, '/tree/42/restore') &&
                       str_contains($html, '/tree/42/delete') &&
                       str_contains($html, 'tree-card deleted');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNullDescription(): void
    {
        $deletedTree = new Tree(1, 'Tree No Description', null, new DateTime(), new DateTime(), false);

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                return str_contains($html, 'Tree No Description') &&
                       str_contains($html, 'No description');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testHtmlStructureAndCSS(): void
    {
        $deletedTree = new Tree(1, 'Structure Test', 'Testing HTML structure', new DateTime(), new DateTime(), false);

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                       str_contains($html, '.tree-card {') &&
                       str_contains($html, '.btn {') &&
                       str_contains($html, 'linear-gradient') &&
                       str_contains($html, '@media (max-width: 768px)') &&
                       str_contains($html, 'class="container"') &&
                       str_contains($html, 'class="header"') &&
                       str_contains($html, 'class="navigation"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMultipleDeletedTrees(): void
    {
        $trees = [];
        for ($i = 1; $i <= 5; $i++) {
            $trees[] = new Tree($i, "Tree $i", "Description $i", new DateTime(), new DateTime(), false);
        }

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn($trees);

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
                return str_contains($html, 'Total deleted trees: <strong>5</strong>') &&
                       str_contains($html, 'Tree 1') &&
                       str_contains($html, 'Tree 2') &&
                       str_contains($html, 'Tree 3') &&
                       str_contains($html, 'Tree 4') &&
                       str_contains($html, 'Tree 5') &&
                       str_contains($html, 'Description 1') &&
                       str_contains($html, 'Description 5');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error loading deleted trees: Database connection failed'));

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
                return str_contains($html, 'Error Loading Deleted Trees') &&
                       str_contains($html, 'Database connection failed') &&
                       str_contains($html, 'Back to Trees List');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testEmptyStateStructure(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
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
                return str_contains($html, 'class="empty-state"') &&
                       str_contains($html, 'class="empty-icon"') &&
                       str_contains($html, '🗑️') &&
                       str_contains($html, '<h2>No Deleted Trees</h2>') &&
                       str_contains($html, 'There are no deleted trees to display');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeActionButtons(): void
    {
        $deletedTree = new Tree(99, 'Action Test Tree', 'Testing action buttons', new DateTime(), new DateTime(), false);

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                return str_contains($html, 'class="tree-actions"') &&
                       str_contains($html, 'href="/tree/99/restore"') &&
                       str_contains($html, 'href="/tree/99/delete"') &&
                       str_contains($html, 'class="btn btn-success"') &&
                       str_contains($html, 'class="btn btn-danger"') &&
                       str_contains($html, '🔄 Restore') &&
                       str_contains($html, '🗑️ Delete Permanently');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPageTitleAndMetadata(): void
    {
        $deletedTree = new Tree(1, 'Title Test', 'Testing page metadata', new DateTime(), new DateTime(), false);

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                return str_contains($html, '<title>Deleted Trees</title>') &&
                       str_contains($html, '<meta charset="UTF-8">') &&
                       str_contains($html, '<meta name="viewport"') &&
                       str_contains($html, 'content="width=device-width, initial-scale=1.0"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
