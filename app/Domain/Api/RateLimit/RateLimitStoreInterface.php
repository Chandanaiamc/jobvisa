<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\RateLimit;

interface RateLimitStoreInterface
{
    /**
     * @return array{attempts: int, reset_at: int}
     */
    public function hit(string $key, int $decaySeconds): array;

    /**
     * @return array{attempts: int, reset_at: int}|null
     */
    public function get(string $key): ?array;
}
