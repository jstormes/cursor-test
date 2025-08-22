<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time;

use App\Infrastructure\Time\ClockInterface;
use App\Infrastructure\Time\SystemClock;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase
{
    private SystemClock $clock;

    protected function setUp(): void
    {
        $this->clock = new SystemClock();
    }

    public function testImplementsClockInterface(): void
    {
        $this->assertInstanceOf(ClockInterface::class, $this->clock);
    }

    public function testNowReturnsInteger(): void
    {
        $result = $this->clock->now();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testNowFloatReturnsFloat(): void
    {
        $result = $this->clock->nowFloat();
        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testNowDateTimeReturnsDateTime(): void
    {
        $result = $this->clock->nowDateTime();
        $this->assertInstanceOf(\DateTime::class, $result);
    }

    public function testConsistentTime(): void
    {
        $time1 = $this->clock->now();
        $time2 = $this->clock->now();

        // Allow for small time difference due to execution time
        $this->assertLessThanOrEqual(1, abs($time2 - $time1));
    }
}
