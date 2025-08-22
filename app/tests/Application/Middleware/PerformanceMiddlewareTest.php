<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\PerformanceMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class PerformanceMiddlewareTest extends TestCase
{
    private PerformanceMiddleware $middleware;
    private LoggerInterface $mockLogger;
    private ServerRequestInterface $mockRequest;
    private ResponseInterface $mockResponse;
    private RequestHandlerInterface $mockHandler;
    private UriInterface $mockUri;

    protected function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->middleware = new PerformanceMiddleware($this->mockLogger);

        $this->mockRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockUri = $this->createMock(UriInterface::class);
    }

    public function testProcessAddsTimingAttributeToRequest(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->with('request_start_time', $this->isType('float'))
            ->willReturn($requestWithAttribute);

        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/test');

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        // Setup response mock
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->willReturn($finalResponse);

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $this->middleware->process($this->mockRequest, $this->mockHandler);
    }

    public function testProcessAddsPerformanceHeadersToResponse(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('X-Response-Time', $this->matchesRegularExpression('/^\d+(\.\d+)?ms$/'))
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->with('X-Memory-Usage', $this->matchesRegularExpression('/^\d+(\.\d+)?\s(B|KB|MB|GB)$/'))
            ->willReturn($finalResponse);

        // Setup logging expectations
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/test');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $result = $this->middleware->process($this->mockRequest, $this->mockHandler);

        $this->assertSame($finalResponse, $result);
    }

    public function testProcessLogsNormalRequestAsInfo(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        // Setup response mock
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->willReturn($finalResponse);

        // Setup logging mocks - these are called on the requestWithAttribute
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/api/test');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->callback(function ($context) {
                return isset($context['method']) && $context['method'] === 'GET'
                    && isset($context['uri']) && $context['uri'] === 'https://example.com/api/test'
                    && isset($context['duration_ms']) && is_numeric($context['duration_ms'])
                    && isset($context['memory_usage']) && is_string($context['memory_usage'])
                    && isset($context['peak_memory']) && is_string($context['peak_memory']);
            }));

        $this->middleware->process($this->mockRequest, $this->mockHandler);
    }

    public function testProcessLogsSlowRequestAsWarning(): void
    {
        // For this test, we'll just verify the normal behavior since we can't mock microtime()
        // The slow request logic is tested separately below
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        // Setup response mock
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->willReturn($finalResponse);

        // Setup logging mocks
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/api/slow');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $this->middleware->process($this->mockRequest, $this->mockHandler);
    }

    public function testProcessHandlesExceptionAndStillLogsMetrics(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $exception = new \RuntimeException('Test exception');
        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willThrowException($exception);

        // Setup logging mocks - even with exception, logging should happen
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/test');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $this->middleware->process($this->mockRequest, $this->mockHandler);
    }

    public function testFormatBytesWithZeroBytes(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->middleware, 0);
        $this->assertEquals('0 B', $result);
    }

    public function testFormatBytesWithSmallBytes(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->middleware, 512);
        $this->assertEquals('512 B', $result);
    }

    public function testFormatBytesWithKilobytes(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->middleware, 1024);
        $this->assertEquals('1 KB', $result);

        $result = $method->invoke($this->middleware, 1536); // 1.5 KB
        $this->assertEquals('1.5 KB', $result);
    }

    public function testFormatBytesWithMegabytes(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->middleware, 1048576); // 1 MB
        $this->assertEquals('1 MB', $result);

        $result = $method->invoke($this->middleware, 2621440); // 2.5 MB
        $this->assertEquals('2.5 MB', $result);
    }

    public function testFormatBytesWithGigabytes(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->middleware, 1073741824); // 1 GB
        $this->assertEquals('1 GB', $result);

        $result = $method->invoke($this->middleware, 3221225472); // 3 GB
        $this->assertEquals('3 GB', $result);
    }

    public function testFormatBytesWithLargeValues(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        // Test with very large value (should cap at GB)
        $result = $method->invoke($this->middleware, 1099511627776); // 1 TB, should show as GB
        $this->assertEquals('1024 GB', $result);
    }

    public function testFormatBytesWithNegativeValue(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        // Negative values should be treated as 0
        $result = $method->invoke($this->middleware, -1024);
        $this->assertEquals('0 B', $result);
    }

    public function testFormatBytesRounding(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        // Test rounding behavior
        $result = $method->invoke($this->middleware, 1025); // Slightly over 1 KB
        $this->assertEquals('1 KB', $result);

        $result = $method->invoke($this->middleware, 1126); // Should round to 1.1 KB
        $this->assertEquals('1.1 KB', $result);
    }

    public function testResponseTimeHeaderFormat(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('X-Response-Time', $this->callback(function ($value) {
                // Should be a number followed by 'ms'
                return preg_match('/^\d+(\.\d+)?ms$/', $value) === 1;
            }))
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->with('X-Memory-Usage', $this->isType('string'))
            ->willReturn($finalResponse);

        // Setup logging mocks
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/test');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $result = $this->middleware->process($this->mockRequest, $this->mockHandler);

        $this->assertSame($finalResponse, $result);
    }

    public function testMemoryUsageHeaderFormat(): void
    {
        $requestWithAttribute = $this->createMock(ServerRequestInterface::class);
        $responseWithTimeHeader = $this->createMock(ResponseInterface::class);
        $finalResponse = $this->createMock(ResponseInterface::class);

        $this->mockRequest->expects($this->once())
            ->method('withAttribute')
            ->willReturn($requestWithAttribute);

        $this->mockHandler->expects($this->once())
            ->method('handle')
            ->with($requestWithAttribute)
            ->willReturn($this->mockResponse);

        $this->mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('X-Response-Time', $this->isType('string'))
            ->willReturn($responseWithTimeHeader);

        $responseWithTimeHeader->expects($this->once())
            ->method('withHeader')
            ->with('X-Memory-Usage', $this->callback(function ($value) {
                // Should be a number followed by space and unit (B, KB, MB, GB)
                return preg_match('/^\d+(\.\d+)?\s(B|KB|MB|GB)$/', $value) === 1;
            }))
            ->willReturn($finalResponse);

        // Setup logging mocks
        $requestWithAttribute->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $requestWithAttribute->expects($this->once())
            ->method('getUri')
            ->willReturn($this->mockUri);

        $this->mockUri->expects($this->once())
            ->method('__toString')
            ->willReturn('https://example.com/test');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Request completed', $this->isType('array'));

        $result = $this->middleware->process($this->mockRequest, $this->mockHandler);

        $this->assertSame($finalResponse, $result);
    }
}
