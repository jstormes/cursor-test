<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\RestoreTreeJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class RestoreTreeJsonActionTest extends TestCase
{
    private RestoreTreeJsonAction $action;
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

        $this->action = new RestoreTreeJsonAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testSuccessfulRestore(): void
    {
        $deletedTree = new Tree(1, 'Deleted Tree', 'A deleted tree', new DateTime('2023-01-01 10:00:00'), new DateTime('2023-01-02 15:30:00'), false);

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
                       $data['message'] === "Tree 'Deleted Tree' has been successfully restored." &&
                       $data['tree']['id'] === 1 &&
                       $data['tree']['name'] === 'Deleted Tree' &&
                       $data['tree']['description'] === 'A deleted tree' &&
                       $data['tree']['is_active'] === true &&
                       $data['tree']['created_at'] === '2023-01-01 10:00:00' &&
                       $data['tree']['updated_at'] === '2023-01-02 15:30:00' &&
                       $data['links']['view_tree'] === '/tree/1/json' &&
                       $data['links']['back_to_trees'] === '/trees/json' &&
                       $data['links']['view_deleted_trees'] === '/trees/deleted/json';
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
            ->method('restore');

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
                       $data['error']['message'] === 'Tree with ID 999 was not found.' &&
                       $data['error']['status_code'] === 404;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testAlreadyActiveTree(): void
    {
        $activeTree = new Tree(1, 'Active Tree', 'Already active tree', new DateTime(), new DateTime(), true);

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
                       $data['error']['message'] === "Tree 'Active Tree' is already active." &&
                       $data['error']['status_code'] === 400;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetMethodNotAllowed(): void
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
                       $data['error']['message'] === 'Method not allowed. Must be POST.' &&
                       $data['error']['status_code'] === 405;
            }));

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
                       $data['error']['message'] === 'Method not allowed. Must be POST.' &&
                       $data['error']['status_code'] === 405;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('DELETE');

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
                       $data['error']['message'] === 'Method not allowed. Must be POST.' &&
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
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in restore tree JSON action: Database connection failed'));

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
                       str_contains($data['error']['message'], 'Internal server error: Database connection failed') &&
                       $data['error']['status_code'] === 500;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNullDescription(): void
    {
        $deletedTree = new Tree(1, 'No Description Tree', null, new DateTime('2023-01-01'), new DateTime('2023-01-02'), false);

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
                       $data['tree']['name'] === 'No Description Tree' &&
                       $data['tree']['description'] === null;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testJsonResponseStructure(): void
    {
        $deletedTree = new Tree(
            42,
            'Structure Test Tree',
            'Testing JSON structure',
            new DateTime('2023-05-15 14:30:00'),
            new DateTime('2023-06-20 09:15:30'),
            false
        );

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($deletedTree);

        $this->treeRepository->expects($this->once())
            ->method('restore')
            ->with(42);

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

                // Verify top-level structure
                $expectedKeys = ['success', 'message', 'tree', 'links'];
                foreach ($expectedKeys as $key) {
                    if (!array_key_exists($key, $data)) {
                        return false;
                    }
                }

                // Verify tree data structure
                $tree = $data['tree'];
                $expectedTreeKeys = ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'];
                foreach ($expectedTreeKeys as $key) {
                    if (!array_key_exists($key, $tree)) {
                        return false;
                    }
                }

                // Verify links structure
                $expectedLinkKeys = ['view_tree', 'back_to_trees', 'view_deleted_trees'];
                foreach ($expectedLinkKeys as $key) {
                    if (!array_key_exists($key, $data['links'])) {
                        return false;
                    }
                }

                return $tree['id'] === 42 &&
                       $tree['name'] === 'Structure Test Tree' &&
                       $tree['description'] === 'Testing JSON structure' &&
                       $tree['is_active'] === true &&
                       $tree['created_at'] === '2023-05-15 14:30:00' &&
                       $tree['updated_at'] === '2023-06-20 09:15:30' &&
                       $data['links']['view_tree'] === '/tree/42/json' &&
                       $data['links']['back_to_trees'] === '/trees/json' &&
                       $data['links']['view_deleted_trees'] === '/trees/deleted/json';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '42']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testErrorResponseStructure(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

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

                // Verify error structure
                return array_key_exists('success', $data) &&
                       array_key_exists('error', $data) &&
                       $data['success'] === false &&
                       array_key_exists('message', $data['error']) &&
                       array_key_exists('status_code', $data['error']) &&
                       is_string($data['error']['message']) &&
                       is_int($data['error']['status_code']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testSuccessMessageContent(): void
    {
        $deletedTree = new Tree(99, 'Message Test Tree', 'Testing success message', new DateTime(), new DateTime(), false);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn($deletedTree);

        $this->treeRepository->expects($this->once())
            ->method('restore')
            ->with(99);

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
                       $data['message'] === "Tree 'Message Test Tree' has been successfully restored." &&
                       str_contains($data['message'], 'successfully restored');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '99']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeActiveStatusInResponse(): void
    {
        $deletedTree = new Tree(1, 'Status Test', 'Testing active status', new DateTime(), new DateTime(), false);

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
                return $data['tree']['is_active'] === true;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
