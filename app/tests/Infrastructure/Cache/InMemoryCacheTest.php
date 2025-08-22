<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Cache;

use App\Infrastructure\Cache\InMemoryCache;
use App\Infrastructure\Time\ClockInterface;
use PHPUnit\Framework\TestCase;

class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;
    private ClockInterface $mockClock;

    protected function setUp(): void
    {
        $this->mockClock = $this->createMock(ClockInterface::class);
        $this->cache = new InMemoryCache($this->mockClock);
    }

    public function testSetAndGet(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->assertTrue($this->cache->set('test_key', 'test_value', 3600));
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->cache->set('test_key', 'test_value');
        $this->assertTrue($this->cache->has('test_key'));
    }

    public function testHasReturnsFalseForExpiredKey(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 5000); // Second call is past expiry

        $this->cache->set('test_key', 'test_value', 3600);
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testDelete(): void
    {
        $this->mockClock->expects($this->once())
            ->method('now')
            ->willReturn(1000);

        $this->cache->set('test_key', 'test_value');
        $this->assertTrue($this->cache->delete('test_key'));
        $this->assertNull($this->cache->get('test_key'));
    }

    public function testClear(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->assertTrue($this->cache->clear());
        
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }
}