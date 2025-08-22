<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Infrastructure\Time\ClockInterface;

class InMemoryCache implements CacheInterface
{
    private array $cache = [];
    private array $expiry = [];

    public function __construct(private ClockInterface $clock)
    {
    }

    #[\Override]
    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            return null;
        }

        return $this->cache[$key];
    }

    #[\Override]
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->cache[$key] = $value;
        $this->expiry[$key] = $this->clock->now() + $ttl;

        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expiry[$key]);
        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->cache = [];
        $this->expiry = [];
        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        if (isset($this->expiry[$key]) && $this->expiry[$key] < $this->clock->now()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function cleanup(): void
    {
        $now = $this->clock->now();
        foreach ($this->expiry as $key => $expireTime) {
            if ($expireTime < $now) {
                $this->delete($key);
            }
        }
    }
}
