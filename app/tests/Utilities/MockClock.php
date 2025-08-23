<?php

declare(strict_types=1);

namespace App\Tests\Utilities;

use App\Infrastructure\Time\ClockInterface;
use DateTime;

class MockClock implements ClockInterface
{
    private DateTime $fixedTime;

    public function __construct(?DateTime $fixedTime = null)
    {
        $this->fixedTime = $fixedTime ?? new DateTime('2023-01-01 10:00:00');
    }

    #[\Override]
    public function now(): int
    {
        return $this->fixedTime->getTimestamp();
    }

    #[\Override]
    public function nowDateTime(): DateTime
    {
        return clone $this->fixedTime;
    }

    public function setTime(DateTime $time): void
    {
        $this->fixedTime = $time;
    }

    public function advance(string $interval): void
    {
        $this->fixedTime->add(new \DateInterval($interval));
    }
}