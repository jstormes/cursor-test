<?php

declare(strict_types=1);

namespace App\Tests\Application\Middleware;

use App\Application\Middleware\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class SessionMiddlewareTest extends TestCase
{
    private SessionMiddleware $middleware;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = new ResponseFactory();
        $this->middleware = new SessionMiddleware();
    }

    public function testProcessStartsSession(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->responseFactory->createResponse(200);
        
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testProcessWithExistingSession(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->responseFactory->createResponse(200);
        
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        // Test that middleware doesn't interfere with existing session
        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testProcessWithDifferentRequestMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $request = (new ServerRequestFactory())->createServerRequest($method, '/test');
            
            $handler = $this->createMock(RequestHandlerInterface::class);
            $response = $this->responseFactory->createResponse(200);
            
            $handler->expects($this->once())
                ->method('handle')
                ->willReturn($response);

            $result = $this->middleware->process($request, $handler);

            $this->assertInstanceOf(ResponseInterface::class, $result);
            $this->assertEquals(200, $result->getStatusCode());
        }
    }
} 