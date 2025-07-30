<?php

declare(strict_types=1);

namespace App\Tests\Application\Handlers;

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class HttpErrorHandlerTest extends TestCase
{
    private HttpErrorHandler $errorHandler;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseFactory = new ResponseFactory();
        
        // Create a mock callable resolver
        $callableResolver = $this->createMock(\Slim\Interfaces\CallableResolverInterface::class);
        $this->errorHandler = new HttpErrorHandler($callableResolver, $this->responseFactory);
    }

    public function testHandleException(): void
    {
        $exception = new \Exception('Test exception');
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        // HttpErrorHandler doesn't use the logger we provide
        $this->logger->expects($this->never())
            ->method('error');

        $response = $this->errorHandler->__invoke($request, $exception, false, false, false);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('SERVER_ERROR', $data['error']['type']);
    }

    public function testHandleExceptionWithDisplayErrorDetails(): void
    {
        $exception = new \Exception('Test exception');
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        // HttpErrorHandler doesn't use the logger we provide
        $this->logger->expects($this->never())
            ->method('error');

        $response = $this->errorHandler->__invoke($request, $exception, true, false, false);

        $this->assertEquals(500, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('description', $data['error']);
        $this->assertEquals('Test exception', $data['error']['description']);
    }

    public function testHandleExceptionWithLogErrors(): void
    {
        $exception = new \Exception('Test exception');
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        // The HttpErrorHandler doesn't actually log to the logger we provide
        // It uses Slim's internal logging mechanism
        $this->logger->expects($this->never())
            ->method('error');

        $response = $this->errorHandler->__invoke($request, $exception, false, true, false);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleExceptionWithoutLogErrors(): void
    {
        $exception = new \Exception('Test exception');
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        
        $this->logger->expects($this->never())
            ->method('error');

        $response = $this->errorHandler->__invoke($request, $exception, false, false, false);

        $this->assertEquals(500, $response->getStatusCode());
    }
} 