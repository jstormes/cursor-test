<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

interface ClockInterface
{
    /**
     * Get the current timestamp
     */
    public function now(): int;

    /**
     * Get the current timestamp as a float with microseconds
     */
    public function nowFloat(): float;

    /**
     * Get current date and time as DateTime object
     */
    public function nowDateTime(): \DateTime;
}
