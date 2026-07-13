<?php

declare(strict_types=1);

namespace JobVisa\App\Cache;

/**
 * No-op cache used when CACHE_ENABLED=false.
 */
final class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        return true;
    }

    public function forever(string $key, mixed $value): bool
    {
        return true;
    }

    public function forget(string $key): bool
    {
        return true;
    }

    public function flush(): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        return $callback();
    }
}
