<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\AddNodeJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class AddNodeJsonActionTest extends TestCase
{
    private AddNodeJsonAction $action;
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
        
        $this->action = new AddNodeJsonAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testCreateSimpleNodeSuccess(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Node',
                'node_type' => 'SimpleNode',
                'sort_order' => 0
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
                       $node->getTreeId() === 1;
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
                       $data['message'] === 'Node created successfully' &&
                       $data['node']['name'] === 'Test Node' &&
                       $data['tree']['name'] === 'Test Tree';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateButtonNodeSuccess(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Button Node',
                'node_type' => 'ButtonNode',
                'button_text' => 'Click Me',
                'button_action' => 'alert("clicked")',
                'sort_order' => 1
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
                       $node->getTreeId() === 1;
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
                       $data['node']['name'] === 'Button Node' &&
                       $data['node']['type'] === 'ButtonNode';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeNotFound(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

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
                       $data['error']['message'] === 'Tree not found' &&
                       $data['error']['details']['tree_id'] === 999;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvalidJsonData(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(null);
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

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

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testEmptyNodeName(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => '',
                'node_type' => 'SimpleNode'
            ]);
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Node name is required';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testNodeNameTooLong(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        $longName = str_repeat('A', 256);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => $longName,
                'node_type' => 'SimpleNode'
            ]);
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Node name must be 255 characters or less';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testButtonNodeWithoutButtonText(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
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

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Button text is required for ButtonNode';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvalidParentNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Child Node',
                'parent_id' => 999,
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

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Invalid parent node selected';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testValidParentNode(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        $parentNode = new SimpleNode(5, 'Parent', 1, null, 0);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Child Node',
                'parent_id' => 5,
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
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       $data['node']['parent_id'] === 5;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testExceptionHandling(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException(new \Exception('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error creating node via JSON: Database error'));

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
                       str_contains($data['error']['message'], 'An error occurred while creating the node');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testButtonTextTooLong(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        $longButtonText = str_repeat('A', 101);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Button Node',
                'node_type' => 'ButtonNode',
                'button_text' => $longButtonText
            ]);
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Button text must be 100 characters or less';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testButtonActionTooLong(): void
    {
        $tree = new Tree(1, 'Test Tree', 'Description', new DateTime(), new DateTime(), true);
        $longButtonAction = str_repeat('A', 256);
        
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Button Node',
                'node_type' => 'ButtonNode',
                'button_text' => 'Click Me',
                'button_action' => $longButtonAction
            ]);
            
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->never())
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
                       $data['error']['message'] === 'Button action must be 255 characters or less';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['treeId' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}