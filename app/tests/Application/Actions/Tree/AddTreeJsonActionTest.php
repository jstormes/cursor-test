<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\AddTreeJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use App\Infrastructure\Time\ClockInterface;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class AddTreeJsonActionTest extends TestCase
{
    private AddTreeJsonAction $action;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private StreamInterface $stream;
    private LoggerInterface $logger;
    private TreeRepository $treeRepository;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->treeRepository = $this->createMock(TreeRepository::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->action = new AddTreeJsonAction(
            $this->logger,
            $this->treeRepository,
            $this->clock
        );
    }

    public function testCreateTreeSuccess(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'A test tree description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                return $tree instanceof Tree &&
                       $tree->getName() === 'Test Tree' &&
                       $tree->getDescription() === 'A test tree description';
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       $data['message'] === 'Tree created successfully' &&
                       $data['tree']['name'] === 'Test Tree' &&
                       $data['tree']['description'] === 'A test tree description' &&
                       isset($data['links']['view_tree']) &&
                       isset($data['links']['add_node']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateTreeWithEmptyDescription(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => ''
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                return $tree instanceof Tree &&
                       $tree->getName() === 'Test Tree' &&
                       $tree->getDescription() === null;
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       $data['tree']['description'] === null;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvalidJsonData(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(null);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'Invalid JSON data provided';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testEmptyTreeName(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => '',
                'description' => 'A description'
            ]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'Tree name is required';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeNameTooLong(): void
    {
        $longName = str_repeat('A', 256);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => $longName,
                'description' => 'A description'
            ]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'Tree name must be 255 characters or less';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDescriptionTooLong(): void
    {
        $longDescription = str_repeat('A', 1001);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Valid Tree Name',
                'description' => $longDescription
            ]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'Description must be 1000 characters or less';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDuplicateTreeName(): void
    {
        $existingTree = new Tree(1, 'Test Tree', 'Existing', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'New description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$existingTree]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'A tree with this name already exists';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCaseInsensitiveDuplicateCheck(): void
    {
        $existingTree = new Tree(1, 'Test Tree', 'Existing', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'TEST TREE',
                'description' => 'New description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$existingTree]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error']['message'] === 'A tree with this name already exists';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willThrowException(new \Exception('Parse error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error creating tree via JSON: Parse error'));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       str_contains($data['error']['message'], 'An error occurred while creating the tree') &&
                       str_contains($data['error']['message'], 'Parse error');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testResponseContainsCorrectLinks(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'Description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        // Mock a tree with ID 42 to test link generation
        $mockTree = $this->createMock(Tree::class);
        $mockTree->method('getId')->willReturn(42);
        $mockTree->method('getName')->willReturn('Test Tree');
        $mockTree->method('getDescription')->willReturn('Description');
        $mockTree->method('getCreatedAt')->willReturn(new DateTime());
        $mockTree->method('getUpdatedAt')->willReturn(new DateTime());

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                // Simulate setting an ID on the tree
                $reflection = new \ReflectionClass($tree);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($tree, 42);
                return true;
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return isset($data['links']) &&
                       $data['links']['view_tree'] === '/tree/42' &&
                       $data['links']['view_tree_json'] === '/tree/42/json' &&
                       $data['links']['add_node'] === '/tree/42/add-node' &&
                       $data['links']['add_node_json'] === '/tree/42/add-node/json' &&
                       $data['links']['view_trees'] === '/trees';
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTrimsWhitespaceFromInputs(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => '  Test Tree  ',
                'description' => '  A test description  '
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                return $tree instanceof Tree &&
                       $tree->getName() === 'Test Tree' &&  // Should be trimmed
                       $tree->getDescription() === 'A test description';  // Should be trimmed
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true;
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
