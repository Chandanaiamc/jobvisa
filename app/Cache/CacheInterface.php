<?php

declare(strict_types=1);

namespace JobVisa\App\Cache;

/**
 * Simple cache contract (file now; Redis later).
 */
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, int $ttlSeconds): bool;

    public function forever(string $key, mixed $value): bool;

    public function forget(string $key): bool;

    public function flush(): bool;

    public function has(string $key): bool;

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed;
}
