<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreeJsonAction;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;

class ViewTreeJsonActionTest extends TestCase
{
    public function testActionReturnsJsonResponse(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testJsonStructureIsValid(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);
    }

    public function testTreeDataStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        $this->assertArrayHasKey('tree', $data['data']);
        $this->assertArrayHasKey('total_nodes', $data['data']);
        $this->assertArrayHasKey('total_levels', $data['data']);

        $tree = $data['data']['tree'];
        $this->assertArrayHasKey('id', $tree);
        $this->assertArrayHasKey('name', $tree);
        $this->assertArrayHasKey('type', $tree);
        $this->assertArrayHasKey('has_children', $tree);
        $this->assertArrayHasKey('children_count', $tree);
    }

    public function testMainNodeHasButtonData(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        $tree = $data['data']['tree'];
        $this->assertEquals('button', $tree['type']);
        $this->assertArrayHasKey('button', $tree);
        $this->assertArrayHasKey('text', $tree['button']);
        $this->assertArrayHasKey('action', $tree['button']);
        $this->assertEquals('Test Btn', $tree['button']['text']);
    }

    public function testChildrenStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        $tree = $data['data']['tree'];
        $this->assertTrue($tree['has_children']);
        $this->assertEquals(2, $tree['children_count']);
        $this->assertArrayHasKey('children', $tree);
        $this->assertCount(2, $tree['children']);

        // Check first child (Sub-1)
        $sub1 = $tree['children'][0];
        $this->assertEquals('simple', $sub1['type']);
        $this->assertFalse($sub1['has_children']);
        $this->assertEquals(0, $sub1['children_count']);

        // Check second child (Sub-2)
        $sub2 = $tree['children'][1];
        $this->assertEquals('simple', $sub2['type']);
        $this->assertTrue($sub2['has_children']);
        $this->assertEquals(2, $sub2['children_count']);
        $this->assertCount(2, $sub2['children']);
    }

    public function testNodeCounting(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/tree/json');
        $response = $app->handle($request);

        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        // Should have 5 nodes total: Main, Sub-1, Sub-2, Sub-2-1, Sub-2-2
        $this->assertEquals(5, $data['data']['total_nodes']);
        $this->assertEquals(2, $data['data']['total_levels']);
    }
} 