<?php

declare(strict_types=1);

namespace App\Tests\Application\ResponseEmitter;

use App\Application\ResponseEmitter\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class ResponseEmitterTest extends TestCase
{
    private ResponseEmitter $responseEmitter;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = new ResponseFactory();
        $this->responseEmitter = new ResponseEmitter();
    }

    public function testEmitResponse(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write('{"test": "data"}');

        // Capture output to test emission
        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('{"test": "data"}', $output);
    }

    public function testEmitResponseWithHeaders(): void
    {
        $response = $this->responseFactory->createResponse(201);
        $response = $response->withHeader('Content-Type', 'text/html');
        $response = $response->withHeader('X-Custom-Header', 'test-value');
        $response->getBody()->write('<html>test</html>');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('<html>test</html>', $output);
    }

    public function testEmitResponseWithDifferentStatusCodes(): void
    {
        $statusCodes = [200, 201, 400, 404, 500];
        
        foreach ($statusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write('test content');

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString('test content', $output);
        }
    }

    public function testEmitEmptyResponse(): void
    {
        $response = $this->responseFactory->createResponse(204);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
} 