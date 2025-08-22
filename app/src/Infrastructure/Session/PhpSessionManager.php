<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final class PhpSessionManager implements SessionManagerInterface
{
    #[\Override]
    public function start(): void
    {
        if (!$this->isStarted()) {
            session_start();
        }
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    #[\Override]
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    #[\Override]
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    #[\Override]
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    #[\Override]
    public function getId(): ?string
    {
        if (!$this->isStarted()) {
            return null;
        }
        $sessionId = session_id();
        return $sessionId !== false ? $sessionId : null;
    }

    #[\Override]
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * @psalm-return array<string, mixed>
     */
    #[\Override]
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }
}
