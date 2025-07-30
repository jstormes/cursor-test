<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeJsonAction;
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

class ViewTreeJsonActionTest extends TestCase
{
    private ViewTreeJsonAction $action;
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
        
        $this->action = new ViewTreeJsonAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository
        );
    }

    public function testActionReturnsJsonResponse(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

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
                       $data['message'] === 'Tree structure retrieved successfully';
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testJsonStructureIsValid(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return is_array($data) &&
                       array_key_exists('success', $data) &&
                       array_key_exists('message', $data) &&
                       array_key_exists('data', $data) &&
                       $data['success'] === true;
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testTreeDataStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return array_key_exists('tree', $data['data']) &&
                       array_key_exists('total_nodes', $data['data']) &&
                       array_key_exists('total_levels', $data['data']) &&
                       array_key_exists('total_root_nodes', $data['data']);
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testButtonNodeData(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock button node data
        $rootNode = new ButtonNode(1, 'Root', 1, null, 0, ['button_text' => 'Click Me']);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $rootNodes = $data['data']['tree']['root_nodes'];
                $firstNode = $rootNodes[0];
                return $firstNode['type'] === 'ButtonNode' &&
                       array_key_exists('button', $firstNode) &&
                       $firstNode['button']['text'] === 'Click Me';
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testChildrenStructure(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data with children
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $childNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $rootNodes = $data['data']['tree']['root_nodes'];
                $firstNode = $rootNodes[0];
                return $firstNode['has_children'] === true &&
                       $firstNode['children_count'] === 1 &&
                       array_key_exists('children', $firstNode) &&
                       count($firstNode['children']) === 1;
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNodeCounting(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $childNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['data']['total_nodes'] === 2 &&
                       $data['data']['total_root_nodes'] === 1;
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoTreesFound(): void
    {
        // Mock empty tree data
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error'] === true &&
                       $data['message'] === 'No active trees found in the database';
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testNoNodesFound(): void
    {
        // Mock tree data
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime(), new DateTime(), true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock empty node data
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['error'] === true &&
                       $data['message'] === 'No nodes found for this tree' &&
                       $data['data']['tree_id'] === 1 &&
                       $data['data']['tree_name'] === 'Test Tree';
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }

    public function testTreeMetadata(): void
    {
        // Mock tree data
        $createdAt = new DateTime('2023-01-01 10:00:00');
        $updatedAt = new DateTime('2023-01-01 11:00:00');
        $tree = new Tree(1, 'Test Tree', 'A test tree', $createdAt, $updatedAt, true);
        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$tree]);

        // Mock node data
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('withHeader')->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $treeData = $data['data']['tree'];
                return $treeData['id'] === 1 &&
                       $treeData['name'] === 'Test Tree' &&
                       $treeData['description'] === 'A test tree' &&
                       $treeData['is_active'] === true &&
                       array_key_exists('created_at', $treeData) &&
                       array_key_exists('updated_at', $treeData);
            }));

        $this->action->__invoke($this->request, $this->response, []);
    }
} 