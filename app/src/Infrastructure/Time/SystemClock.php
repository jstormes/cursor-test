<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

final class SystemClock implements ClockInterface
{
    #[\Override]
    public function now(): int
    {
        return time();
    }

    #[\Override]
    public function nowFloat(): float
    {
        return microtime(true);
    }

    #[\Override]
    public function nowDateTime(): \DateTime
    {
        return new \DateTime();
    }
}