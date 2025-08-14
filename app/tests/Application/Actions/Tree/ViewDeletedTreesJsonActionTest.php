<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewDeletedTreesJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewDeletedTreesJsonActionTest extends TestCase
{
    private ViewDeletedTreesJsonAction $action;
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
        
        $this->action = new ViewDeletedTreesJsonAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testGetDeletedTreesWithData(): void
    {
        $deletedTree1 = new Tree(1, 'Deleted Tree 1', 'First deleted tree', new DateTime('2023-01-01 10:00:00'), new DateTime('2023-01-02 15:30:00'), false);
        $deletedTree2 = new Tree(2, 'Deleted Tree 2', 'Second deleted tree', new DateTime('2023-02-01 09:15:00'), new DateTime('2023-02-02 14:45:00'), false);
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree1, $deletedTree2]);

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
                       $data['message'] === 'Deleted trees retrieved successfully.' &&
                       $data['stats']['total_deleted_trees'] === 2 &&
                       count($data['trees']) === 2 &&
                       $data['trees'][0]['id'] === 1 &&
                       $data['trees'][0]['name'] === 'Deleted Tree 1' &&
                       $data['trees'][0]['description'] === 'First deleted tree' &&
                       $data['trees'][0]['is_active'] === false &&
                       $data['trees'][0]['created_at'] === '2023-01-01 10:00:00' &&
                       $data['trees'][0]['updated_at'] === '2023-01-02 15:30:00' &&
                       $data['trees'][1]['id'] === 2 &&
                       $data['trees'][1]['name'] === 'Deleted Tree 2' &&
                       isset($data['links']['back_to_active_trees']) &&
                       isset($data['links']['view_active_trees_html']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetDeletedTreesWithNoData(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([]);

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
                       $data['message'] === 'Deleted trees retrieved successfully.' &&
                       $data['stats']['total_deleted_trees'] === 0 &&
                       empty($data['trees']) &&
                       isset($data['links']['back_to_active_trees']) &&
                       $data['links']['back_to_active_trees'] === '/trees/json' &&
                       $data['links']['view_active_trees_html'] === '/trees';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostMethodNotAllowed(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->never())
            ->method('findDeleted');

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
                       $data['error']['message'] === 'Method not allowed. Must be GET.' &&
                       $data['error']['status_code'] === 405;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPutMethodNotAllowed(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('PUT');

        $this->treeRepository->expects($this->never())
            ->method('findDeleted');

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
                       $data['error']['message'] === 'Method not allowed. Must be GET.' &&
                       $data['error']['status_code'] === 405;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('DELETE');

        $this->treeRepository->expects($this->never())
            ->method('findDeleted');

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
                       $data['error']['message'] === 'Method not allowed. Must be GET.' &&
                       $data['error']['status_code'] === 405;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in view deleted trees JSON action: Database connection failed'));

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

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNullDescription(): void
    {
        $deletedTree = new Tree(1, 'Tree No Description', null, new DateTime('2023-01-01'), new DateTime('2023-01-02'), false);
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                       $data['trees'][0]['name'] === 'Tree No Description' &&
                       $data['trees'][0]['description'] === null;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMultipleDeletedTrees(): void
    {
        $trees = [];
        for ($i = 1; $i <= 5; $i++) {
            $trees[] = new Tree($i, "Tree $i", "Description $i", new DateTime('2023-01-01'), new DateTime('2023-01-02'), false);
        }
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn($trees);

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
                       $data['stats']['total_deleted_trees'] === 5 &&
                       count($data['trees']) === 5 &&
                       $data['trees'][0]['name'] === 'Tree 1' &&
                       $data['trees'][4]['name'] === 'Tree 5' &&
                       $data['trees'][2]['description'] === 'Description 3';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testJsonResponseFormat(): void
    {
        $deletedTree = new Tree(
            42, 
            'Format Test Tree', 
            'Testing JSON format', 
            new DateTime('2023-05-15 14:30:00'), 
            new DateTime('2023-06-20 09:15:30'), 
            false
        );
        
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([$deletedTree]);

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
                $expectedKeys = ['success', 'message', 'stats', 'trees', 'links'];
                foreach ($expectedKeys as $key) {
                    if (!array_key_exists($key, $data)) {
                        return false;
                    }
                }
                
                // Verify tree data structure
                $tree = $data['trees'][0];
                $expectedTreeKeys = ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'];
                foreach ($expectedTreeKeys as $key) {
                    if (!array_key_exists($key, $tree)) {
                        return false;
                    }
                }
                
                // Verify links structure
                $expectedLinkKeys = ['back_to_active_trees', 'view_active_trees_html'];
                foreach ($expectedLinkKeys as $key) {
                    if (!array_key_exists($key, $data['links'])) {
                        return false;
                    }
                }
                
                return $tree['id'] === 42 &&
                       $tree['name'] === 'Format Test Tree' &&
                       $tree['description'] === 'Testing JSON format' &&
                       $tree['is_active'] === false &&
                       $tree['created_at'] === '2023-05-15 14:30:00' &&
                       $tree['updated_at'] === '2023-06-20 09:15:30';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testLinksInResponse(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn([]);

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
                return isset($data['links']) &&
                       $data['links']['back_to_active_trees'] === '/trees/json' &&
                       $data['links']['view_active_trees_html'] === '/trees';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}