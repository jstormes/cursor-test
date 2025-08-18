<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

class InMemoryCache implements CacheInterface
{
    private array $cache = [];
    private array $expiry = [];

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
        $this->expiry[$key] = time() + $ttl;

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

        if (isset($this->expiry[$key]) && $this->expiry[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function cleanup(): void
    {
        $now = time();
        foreach ($this->expiry as $key => $expireTime) {
            if ($expireTime < $now) {
                $this->delete($key);
            }
        }
    }
}
