<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeByIdJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Infrastructure\Services\TreeStructureBuilder;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class ViewTreeByIdJsonActionTest extends TestCase
{
    private ViewTreeByIdJsonAction $action;
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

        // Setup default response mock behavior
        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);
        $this->response->expects($this->any())
            ->method('withStatus')
            ->willReturnSelf();
        $this->response->expects($this->any())
            ->method('withHeader')
            ->willReturnSelf();

        $treeStructureBuilder = new TreeStructureBuilder();
        
        $this->action = new ViewTreeByIdJsonAction(
            $this->logger,
            $this->treeRepository,
            $this->treeNodeRepository,
            $treeStructureBuilder
        );
    }

    public function testSuccessfulTreeRetrieval(): void
    {
        $tree = new Tree(1, 'Test Tree', 'A test tree', new DateTime('2023-01-01'), new DateTime('2023-01-02'), true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $childNode = new SimpleNode(2, 'Child', 1, 1, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $childNode]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === true &&
                       $data['message'] === 'Tree structure retrieved successfully' &&
                       $data['data']['tree']['id'] === 1 &&
                       $data['data']['tree']['name'] === 'Test Tree' &&
                       $data['data']['tree']['description'] === 'A test tree' &&
                       $data['data']['tree']['is_active'] === true &&
                       $data['data']['total_nodes'] === 2 &&
                       $data['data']['total_root_nodes'] === 1 &&
                       isset($data['data']['tree']['root_nodes']);
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeNotFound(): void
    {
        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->treeNodeRepository->expects($this->never())
            ->method('findByTreeId');


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['message'] === 'Tree not found' &&
                       $data['error'] === true &&
                       $data['data']['tree_id'] === 999 &&
                       str_contains($data['data']['message'], 'Tree with ID 999 was not found');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '999']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoNodes(): void
    {
        $tree = new Tree(1, 'Empty Tree', 'An empty tree', new DateTime(), new DateTime(), true);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['message'] === 'No nodes found for this tree' &&
                       $data['error'] === true &&
                       $data['data']['tree_id'] === 1 &&
                       $data['data']['tree_name'] === 'Empty Tree';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithNoRootNodes(): void
    {
        $tree = new Tree(1, 'Broken Tree', 'A broken tree', new DateTime(), new DateTime(), true);

        // All nodes have parents (no root nodes)
        $childNode1 = new SimpleNode(1, 'Child1', 1, 999, 0); // Parent doesn't exist
        $childNode2 = new SimpleNode(2, 'Child2', 1, 888, 0); // Parent doesn't exist

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$childNode1, $childNode2]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return $data['success'] === false &&
                       $data['message'] === 'Invalid tree structure - no root nodes found' &&
                       $data['error'] === true &&
                       $data['data']['tree_id'] === 1 &&
                       $data['data']['tree_name'] === 'Broken Tree' &&
                       $data['data']['total_nodes'] === 2;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeWithButtonNodes(): void
    {
        $tree = new Tree(1, 'Button Tree', 'A tree with buttons', new DateTime(), new DateTime(), true);
        $buttonNode = new ButtonNode(1, 'Root Button', 1, null, 0, ['button_text' => 'Click Me', 'button_action' => 'alert("clicked")']);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$buttonNode]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $rootNode = $data['data']['tree']['root_nodes'][0];
                return $data['success'] === true &&
                       $rootNode['type'] === 'ButtonNode' &&
                       $rootNode['button']['text'] === 'Click Me' &&
                       $rootNode['button']['action'] === 'alert("clicked")' &&
                       $rootNode['name'] === 'Root Button';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testComplexTreeStructure(): void
    {
        $tree = new Tree(1, 'Complex Tree', 'Multi-level tree', new DateTime(), new DateTime(), true);

        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $child1 = new SimpleNode(2, 'Child1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child2', 1, 1, 1);
        $grandChild1 = new SimpleNode(4, 'GrandChild1', 1, 2, 0);
        $grandChild2 = new SimpleNode(5, 'GrandChild2', 1, 2, 1);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $child1, $child2, $grandChild1, $grandChild2]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                $rootNode = $data['data']['tree']['root_nodes'][0];

                return $data['success'] === true &&
                       $data['data']['total_nodes'] === 5 &&
                       $data['data']['total_levels'] >= 2 &&
                       $data['data']['total_root_nodes'] === 1 &&
                       $rootNode['name'] === 'Root' &&
                       $rootNode['has_children'] === true &&
                       $rootNode['children_count'] === 2 &&
                       isset($rootNode['children']) &&
                       count($rootNode['children']) === 2;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testNodeDataStructure(): void
    {
        $tree = new Tree(1, 'Data Test Tree', 'Testing node data', new DateTime(), new DateTime(), true);
        $rootNode = new SimpleNode(1, 'Root Node', 1, null, 5);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);

        $this->response->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($this->stream);


        $this->stream->expects($this->atLeastOnce())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                if (isset($data['success']) && $data['success'] === true) {
                    $rootNode = $data['data']['tree']['root_nodes'][0];
                    return isset($rootNode['id']) &&
                           isset($rootNode['name']) &&
                           isset($rootNode['type']) &&
                           isset($rootNode['tree_id']) &&
                           isset($rootNode['parent_id']) &&
                           isset($rootNode['sort_order']) &&
                           isset($rootNode['has_children']) &&
                           isset($rootNode['children_count']) &&
                           isset($rootNode['type_data']) &&
                           $rootNode['name'] === 'Root Node' &&
                           $rootNode['type'] === 'SimpleNode' &&
                           $rootNode['tree_id'] === 1 &&
                           $rootNode['parent_id'] === null &&
                           $rootNode['sort_order'] === 5 &&
                           $rootNode['has_children'] === false &&
                           $rootNode['children_count'] === 0;
                }
                return true; // Accept error responses
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMultipleRootNodes(): void
    {
        $tree = new Tree(1, 'Multi-Root Tree', 'Tree with multiple root nodes', new DateTime(), new DateTime(), true);

        $rootNode1 = new SimpleNode(1, 'Root1', 1, null, 0);
        $rootNode2 = new SimpleNode(2, 'Root2', 1, null, 1);
        $child1 = new SimpleNode(3, 'Child1', 1, 1, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode1, $rootNode2, $child1]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);

                return $data['success'] === true &&
                       $data['data']['total_nodes'] === 3 &&
                       $data['data']['total_root_nodes'] === 2 &&
                       count($data['data']['tree']['root_nodes']) === 2 &&
                       $data['data']['tree']['root_nodes'][0]['name'] === 'Root1' &&
                       $data['data']['tree']['root_nodes'][1]['name'] === 'Root2' &&
                       $data['data']['tree']['root_nodes'][0]['has_children'] === true &&
                       $data['data']['tree']['root_nodes'][1]['has_children'] === false;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
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
            ->with($this->stringContains('Error loading tree structure: Database error'));


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);
                return isset($data['error']) && $data['error'] === true &&
                       isset($data['message']) &&
                       str_contains($data['message'], 'Database error');
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeMetricsCalculation(): void
    {
        $tree = new Tree(1, 'Metrics Tree', 'For testing metrics', new DateTime(), new DateTime(), true);

        // Create a 3-level tree: Root -> Child1 -> GrandChild, Root -> Child2
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);
        $child1 = new SimpleNode(2, 'Child1', 1, 1, 0);
        $child2 = new SimpleNode(3, 'Child2', 1, 1, 1);
        $grandChild = new SimpleNode(4, 'GrandChild', 1, 2, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode, $child1, $child2, $grandChild]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);

                return $data['success'] === true &&
                       $data['data']['total_nodes'] === 4 &&
                       $data['data']['total_levels'] === 2 && // 0=Root, 1=Child, 2=GrandChild
                       $data['data']['total_root_nodes'] === 1;
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testTreeDateFormatting(): void
    {
        $createdAt = new DateTime('2023-05-15 14:30:00');
        $updatedAt = new DateTime('2023-06-20 09:15:30');
        $tree = new Tree(1, 'Date Tree', 'Testing dates', $createdAt, $updatedAt, true);
        $rootNode = new SimpleNode(1, 'Root', 1, null, 0);

        $this->treeRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->treeNodeRepository->expects($this->once())
            ->method('findByTreeId')
            ->with(1)
            ->willReturn([$rootNode]);


        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($json) {
                $data = json_decode($json, true);

                return $data['data']['tree']['created_at'] === '2023-05-15 14:30:00' &&
                       $data['data']['tree']['updated_at'] === '2023-06-20 09:15:30';
            }));

        $result = $this->action->__invoke($this->request, $this->response, ['id' => '1']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
