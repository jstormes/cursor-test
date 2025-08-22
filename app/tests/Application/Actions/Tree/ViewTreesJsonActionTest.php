<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreesJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewTreesJsonActionTest extends TestCase
{
    private ViewTreesJsonAction $action;
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

        $this->action = new ViewTreesJsonAction(
            $this->logger,
            $this->treeRepository
        );
    }

    public function testGetActiveTreesWithData(): void
    {
        $activeTree1 = new Tree(1, 'Active Tree 1', 'First active tree', new DateTime('2023-01-01 10:00:00'), new DateTime('2023-01-02 15:30:00'), true);
        $activeTree2 = new Tree(2, 'Active Tree 2', 'Second active tree', new DateTime('2023-02-01 09:15:00'), new DateTime('2023-02-02 14:45:00'), true);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$activeTree1, $activeTree2]);

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
                       $data['message'] === 'Active trees retrieved successfully.' &&
                       $data['stats']['total_active_trees'] === 2 &&
                       count($data['trees']) === 2 &&
                       $data['trees'][0]['id'] === 1 &&
                       $data['trees'][0]['name'] === 'Active Tree 1' &&
                       $data['trees'][0]['description'] === 'First active tree' &&
                       $data['trees'][0]['is_active'] === true &&
                       $data['trees'][0]['created_at'] === '2023-01-01 10:00:00' &&
                       $data['trees'][0]['updated_at'] === '2023-01-02 15:30:00' &&
                       $data['trees'][1]['id'] === 2 &&
                       $data['trees'][1]['name'] === 'Active Tree 2' &&
                       isset($data['links']['view_deleted_trees']) &&
                       isset($data['links']['view_trees_html']) &&
                       isset($data['links']['add_new_tree']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetActiveTreesWithNoData(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
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
                       $data['message'] === 'Active trees retrieved successfully.' &&
                       $data['stats']['total_active_trees'] === 0 &&
                       empty($data['trees']) &&
                       isset($data['links']['view_deleted_trees']) &&
                       $data['links']['view_deleted_trees'] === '/trees/deleted/json' &&
                       $data['links']['view_trees_html'] === '/trees' &&
                       $data['links']['add_new_tree'] === '/tree/add';
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
            ->method('findActive');

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
            ->method('findActive');

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
            ->method('findActive');

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
            ->method('findActive')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in view trees JSON action: Database connection failed'));

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
        $activeTree = new Tree(1, 'Tree No Description', null, new DateTime('2023-01-01'), new DateTime('2023-01-02'), true);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$activeTree]);

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

    public function testMultipleActiveTrees(): void
    {
        $trees = [];
        for ($i = 1; $i <= 5; $i++) {
            $trees[] = new Tree($i, "Active Tree $i", "Description $i", new DateTime('2023-01-01'), new DateTime('2023-01-02'), true);
        }

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
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
                       $data['stats']['total_active_trees'] === 5 &&
                       count($data['trees']) === 5 &&
                       $data['trees'][0]['name'] === 'Active Tree 1' &&
                       $data['trees'][4]['name'] === 'Active Tree 5' &&
                       $data['trees'][2]['description'] === 'Description 3';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testJsonResponseFormat(): void
    {
        $activeTree = new Tree(
            42,
            'Format Test Tree',
            'Testing JSON format',
            new DateTime('2023-05-15 14:30:00'),
            new DateTime('2023-06-20 09:15:30'),
            true
        );

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$activeTree]);

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
                $expectedLinkKeys = ['view_deleted_trees', 'view_trees_html', 'add_new_tree'];
                foreach ($expectedLinkKeys as $key) {
                    if (!array_key_exists($key, $data['links'])) {
                        return false;
                    }
                }

                return $tree['id'] === 42 &&
                       $tree['name'] === 'Format Test Tree' &&
                       $tree['description'] === 'Testing JSON format' &&
                       $tree['is_active'] === true &&
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
            ->method('findActive')
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
                       $data['links']['view_deleted_trees'] === '/trees/deleted/json' &&
                       $data['links']['view_trees_html'] === '/trees' &&
                       $data['links']['add_new_tree'] === '/tree/add';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testResponseContentType(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
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
            ->with($this->isType('string'));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testSuccessResponseStructure(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Test Description', new DateTime('2023-01-01'), new DateTime('2023-01-02'), true);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

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
                return is_array($data) &&
                       array_key_exists('success', $data) &&
                       array_key_exists('message', $data) &&
                       array_key_exists('stats', $data) &&
                       array_key_exists('trees', $data) &&
                       array_key_exists('links', $data) &&
                       $data['success'] === true &&
                       is_array($data['stats']) &&
                       is_array($data['trees']) &&
                       is_array($data['links']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testErrorResponseStructure(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->treeRepository->expects($this->never())
            ->method('findActive');

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
                return is_array($data) &&
                       array_key_exists('success', $data) &&
                       array_key_exists('error', $data) &&
                       $data['success'] === false &&
                       is_array($data['error']) &&
                       array_key_exists('message', $data['error']) &&
                       array_key_exists('status_code', $data['error']) &&
                       is_string($data['error']['message']) &&
                       is_int($data['error']['status_code']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
