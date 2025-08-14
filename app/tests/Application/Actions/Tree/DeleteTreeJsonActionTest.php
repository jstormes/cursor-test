<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\DeleteTreeJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class DeleteTreeJsonActionTest extends TestCase
{
    private DeleteTreeJsonAction $action;
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
        
        $this->action = new DeleteTreeJsonAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testSuccessfulTreeDeletion(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
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
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       str_contains($data['message'], 'Test Tree') &&
                       str_contains($data['message'], 'successfully deleted') &&
                       $data['tree']['id'] === 1 &&
                       $data['tree']['name'] === 'Test Tree' &&
                       $data['tree']['is_active'] === false &&
                       isset($data['links']['view_deleted_trees']) &&
                       isset($data['links']['back_to_trees']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeNotFound(): void
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
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       str_contains($data['error']['message'], 'Tree with ID 999 was not found') &&
                       $data['error']['status_code'] === 404;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeAlreadyDeleted(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), false);
        
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
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       str_contains($data['error']['message'], 'Test Tree') &&
                       str_contains($data['error']['message'], 'already been deleted') &&
                       $data['error']['status_code'] === 400;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvalidHttpMethod(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->never())
            ->method('findById');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       str_contains($data['error']['message'], 'Method not allowed. Must be POST.') &&
                       $data['error']['status_code'] === 405;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in delete tree JSON action: Database error'));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       str_contains($data['error']['message'], 'Internal server error') &&
                       str_contains($data['error']['message'], 'Database error') &&
                       $data['error']['status_code'] === 500;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDeleteMethodCallsRepositoryOnce(): void
    {
        $tree = new Tree(5, 'Another Tree', 'Another description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($tree);

        $this->treeRepository->expects($this->once())
            ->method('softDelete')
            ->with(5);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       $data['tree']['id'] === 5 &&
                       $data['tree']['name'] === 'Another Tree';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '5']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testResponseContainsCorrectTreeData(): void
    {
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-02 15:30:00');
        $tree = new Tree(10, 'Data Test Tree', 'Test description', $createdAt, $updatedAt, true);
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($tree);

        $this->treeRepository->expects($this->once())
            ->method('softDelete')
            ->with(10);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['tree']['id'] === 10 &&
                       $data['tree']['name'] === 'Data Test Tree' &&
                       $data['tree']['description'] === 'Test description' &&
                       $data['tree']['is_active'] === false &&
                       $data['tree']['created_at'] === '2023-01-01 10:00:00' &&
                       $data['tree']['updated_at'] === '2023-01-02 15:30:00';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '10']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testResponseContainsCorrectLinks(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
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
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['links']['view_deleted_trees'] === '/trees/deleted/json' &&
                       $data['links']['back_to_trees'] === '/trees/json';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}