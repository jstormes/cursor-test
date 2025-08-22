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
     * Get current date and time as DateTime object
     */
    public function nowDateTime(): \DateTime;
}
