<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class PerformanceMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Add timing attribute to request
        $request = $request->withAttribute('request_start_time', $startTime);

        try {
            $response = $handler->handle($request);
        } finally {
            $this->logPerformanceMetrics($request, $startTime, $startMemory);
        }

        // Add performance headers to response
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $response = $response->withHeader('X-Response-Time', "{$duration}ms");
        $response = $response->withHeader('X-Memory-Usage', $this->formatBytes(memory_get_peak_usage()));

        return $response;
    }

    private function logPerformanceMetrics(ServerRequestInterface $request, float $startTime, int $startMemory): void
    {
        $duration = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage() - $startMemory;
        $peakMemory = memory_get_peak_usage();

        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        $context = [
            'method' => $method,
            'uri' => $uri,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => $this->formatBytes($memoryUsage),
            'peak_memory' => $this->formatBytes($peakMemory),
        ];

        // Log slow requests as warnings
        if ($duration > 1.0) {
            $this->logger->warning('Slow request detected', $context);
        } else {
            $this->logger->info('Request completed', $context);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
