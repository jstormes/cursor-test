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
     * Get a value from the session
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in the session
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool;

    /**
     * Remove a key from the session
     */
    public function remove(string $key): void;

    /**
     * Clear all session data
     */
    public function clear(): void;

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