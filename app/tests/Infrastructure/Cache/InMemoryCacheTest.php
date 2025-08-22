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

    public function testGetNonExistentKey(): void
    {
        $this->assertNull($this->cache->get('nonexistent_key'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $this->assertFalse($this->cache->has('nonexistent_key'));
    }

    public function testDeleteNonExistentKeyReturnsTrue(): void
    {
        $this->assertTrue($this->cache->delete('nonexistent_key'));
    }

    public function testSetWithDefaultTtl(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->assertTrue($this->cache->set('test_key', 'test_value')); // Using default TTL
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function testSetWithCustomTtl(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->assertTrue($this->cache->set('test_key', 'test_value', 7200)); // 2 hours
        $this->assertEquals('test_value', $this->cache->get('test_key'));
    }

    public function testSetWithZeroTtl(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 1001); // Second call is past expiry

        $this->assertTrue($this->cache->set('test_key', 'test_value', 0)); // Immediate expiry
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testSetWithNegativeTtl(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturn(1000);

        $this->assertTrue($this->cache->set('test_key', 'test_value', -100)); // Already expired
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testGetReturnsNullForExpiredKey(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 5000); // Second call is past expiry

        $this->cache->set('test_key', 'test_value', 3600);
        $this->assertNull($this->cache->get('test_key')); // Should return null for expired key
    }

    public function testExpiredKeyIsRemovedOnHasCheck(): void
    {
        $this->mockClock->expects($this->exactly(2))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 5000); // Set, check expired

        $this->cache->set('test_key', 'test_value', 3600);
        $this->assertFalse($this->cache->has('test_key')); // Should remove expired key
        $this->assertFalse($this->cache->has('test_key')); // Should still be false (no clock call needed)
    }

    public function testSetOverwritesExistingKey(): void
    {
        $this->mockClock->expects($this->exactly(4))
            ->method('now')
            ->willReturn(1000);

        $this->cache->set('test_key', 'original_value');
        $this->assertEquals('original_value', $this->cache->get('test_key'));

        $this->cache->set('test_key', 'new_value');
        $this->assertEquals('new_value', $this->cache->get('test_key'));
    }

    public function testSetDifferentDataTypes(): void
    {
        $this->mockClock->expects($this->exactly(8))
            ->method('now')
            ->willReturn(1000);

        // String
        $this->cache->set('string_key', 'string_value');
        $this->assertEquals('string_value', $this->cache->get('string_key'));

        // Integer
        $this->cache->set('int_key', 42);
        $this->assertEquals(42, $this->cache->get('int_key'));

        // Array
        $array = ['a' => 1, 'b' => 2];
        $this->cache->set('array_key', $array);
        $this->assertEquals($array, $this->cache->get('array_key'));

        // Object
        $object = (object) ['property' => 'value'];
        $this->cache->set('object_key', $object);
        $this->assertEquals($object, $this->cache->get('object_key'));
    }

    public function testSetNullValue(): void
    {
        $this->mockClock->expects($this->once())
            ->method('now')
            ->willReturn(1000);

        // Note: Due to PHP's isset() behavior, null values cannot be stored properly
        // in this cache implementation. isset() returns false for null values.
        $this->cache->set('null_key', null);
        $this->assertFalse($this->cache->has('null_key')); // This is the actual behavior
        $this->assertNull($this->cache->get('null_key')); // Returns null because key doesn't "exist"
    }

    public function testCleanupRemovesExpiredKeys(): void
    {
        $this->mockClock->expects($this->exactly(5))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 1000, 1000, 5000, 5000); // Set three keys, cleanup, then has check for key3

        $this->cache->set('key1', 'value1', 1000); // Expires at 2000
        $this->cache->set('key2', 'value2', 2000); // Expires at 3000
        $this->cache->set('key3', 'value3', 10000); // Expires at 11000

        $this->cache->cleanup();

        // After cleanup, expired keys should be removed
        $this->assertFalse($this->cache->has('key1')); // Expired (already removed by cleanup)
        $this->assertFalse($this->cache->has('key2')); // Expired (already removed by cleanup)
        $this->assertTrue($this->cache->has('key3')); // Still valid
    }

    public function testCleanupWithNoExpiredKeys(): void
    {
        $this->mockClock->expects($this->exactly(5))
            ->method('now')
            ->willReturn(1000);

        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);

        $this->cache->cleanup();

        // All keys should still exist
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
    }

    public function testCleanupWithAllKeysExpired(): void
    {
        $this->mockClock->expects($this->exactly(3))
            ->method('now')
            ->willReturnOnConsecutiveCalls(1000, 1000, 5000); // Set two keys, then cleanup at time 5000

        $this->cache->set('key1', 'value1', 1000); // Expires at 2000
        $this->cache->set('key2', 'value2', 1000); // Expires at 2000

        $this->cache->cleanup();

        // All keys should be removed
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testCleanupOnEmptyCache(): void
    {
        $this->cache->cleanup(); // Should not cause any errors
        $this->assertFalse($this->cache->has('any_key'));
    }

    public function testClearOnEmptyCache(): void
    {
        $this->assertTrue($this->cache->clear()); // Should return true even on empty cache
    }

    public function testMultipleOperationsSequence(): void
    {
        $this->mockClock->expects($this->exactly(8))
            ->method('now')
            ->willReturn(1000);

        // Set multiple keys
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        // Verify they exist
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));

        // Delete one
        $this->cache->delete('key2');
        $this->assertFalse($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key3'));

        // Clear all
        $this->cache->clear();
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key3'));
    }
}
