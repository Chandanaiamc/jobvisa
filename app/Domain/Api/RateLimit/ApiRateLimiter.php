<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\RateLimit;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Security\SecurityHelper;

/**
 * Per-IP / per-user / per-token API rate limiting.
 */
final class ApiRateLimiter
{
    public function __construct(
        private readonly RateLimitStoreInterface $store,
    ) {
    }

    /**
     * @return array{limit: int, remaining: int, reset: int}
     */
    public function enforce(): array
    {
        if (!(bool) config('api.rate_limit_enabled', true)) {
            $limit = (int) config('api.rate_limit_per_minute', 120);

            return ['limit' => $limit, 'remaining' => $limit, 'reset' => time() + 60];
        }

        $ip = SecurityHelper::clientIp();
        $userId = ApiAuth::id();
        $tokenId = ApiAuth::tokenId();

        $limit = $userId !== null
            ? (int) config('api.rate_limit_auth_per_minute', 240)
            : (int) config('api.rate_limit_per_minute', 120);

        $keys = ['ip:' . $ip];
        if ($userId !== null) {
            $keys[] = 'user:' . $userId;
        }
        if ($tokenId !== null) {
            $keys[] = 'token:' . $tokenId;
        }

        $worst = ['attempts' => 0, 'reset_at' => time() + 60];
        static $hitThisRequest = [];
        foreach ($keys as $key) {
            if (isset($hitThisRequest[$key])) {
                $bucket = $hitThisRequest[$key];
            } else {
                $bucket = $this->store->hit($key, 60);
                $hitThisRequest[$key] = $bucket;
            }
            if ($bucket['attempts'] > $worst['attempts']) {
                $worst = $bucket;
            }
            if ($bucket['attempts'] > $limit) {
                $retry = max(1, $bucket['reset_at'] - time());
                if (!headers_sent()) {
                    header('Retry-After: ' . $retry);
                    header('X-RateLimit-Limit: ' . $limit);
                    header('X-RateLimit-Remaining: 0');
                    header('X-RateLimit-Reset: ' . $bucket['reset_at']);
                }
                throw ApiException::rateLimited('Too many requests.', $retry);
            }
        }

        $remaining = max(0, $limit - (int) $worst['attempts']);
        if (!headers_sent()) {
            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . $remaining);
            header('X-RateLimit-Reset: ' . (int) $worst['reset_at']);
        }

        return [
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => (int) $worst['reset_at'],
        ];
    }
}
