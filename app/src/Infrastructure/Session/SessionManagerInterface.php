<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

interface SessionManagerInterface
{
    /**
     * Start the session
     */
    public function start(): void;

    /**
     * Get the session ID
     */
    public function getId(): ?string;

    /**
     * Check if session is started
     */
    public function isStarted(): bool;

    /**
     * Get all session data
     */
    public function all(): array;
}
