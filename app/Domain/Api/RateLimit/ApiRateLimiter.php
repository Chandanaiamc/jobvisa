<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\RateLimit;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Security\SecurityHelper;

/**
 * Per-IP / per-user / per-token API rate limiting.
 *
 * Memoization is request-scoped only (cleared via beginRequest) so PHP-FPM
 * workers cannot reuse hit counts across HTTP requests.
 */
final class ApiRateLimiter
{
    /** @var array<string, array{attempts: int, reset_at: int}> */
    private static array $hitThisRequest = [];

    public function __construct(
        private readonly RateLimitStoreInterface $store,
    ) {
    }

    /**
     * Clear per-request memo. Call once at the start of each API request.
     */
    public static function beginRequest(): void
    {
        self::$hitThisRequest = [];
    }

    /**
     * @return array{limit: int, remaining: int, reset: int}
     */
    public function enforce(): array
    {
        if (!(bool) config('api.rate_limit_enabled', true)) {
            $limit = (int) config('api.rate_limit_per_minute', 120);
            $headers = [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + 60,
            ];
            $this->emitHeaders($headers['limit'], $headers['remaining'], $headers['reset']);

            return $headers;
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
        foreach ($keys as $key) {
            if (isset(self::$hitThisRequest[$key])) {
                $bucket = self::$hitThisRequest[$key];
            } else {
                try {
                    $bucket = $this->store->hit($key, 60);
                } catch (\Throwable) {
                    throw new ApiException(
                        'rate_limit_unavailable',
                        'Rate limit store is temporarily unavailable.',
                        503
                    );
                }
                self::$hitThisRequest[$key] = $bucket;
            }
            if ($bucket['attempts'] > $worst['attempts']) {
                $worst = $bucket;
            }
            if ($bucket['attempts'] > $limit) {
                $retry = max(1, $bucket['reset_at'] - time());
                $this->emitHeaders($limit, 0, (int) $bucket['reset_at'], $retry);
                throw ApiException::rateLimited('Too many requests.', $retry);
            }
        }

        $remaining = max(0, $limit - (int) $worst['attempts']);
        $this->emitHeaders($limit, $remaining, (int) $worst['reset_at']);

        return [
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => (int) $worst['reset_at'],
        ];
    }

    private function emitHeaders(int $limit, int $remaining, int $reset, ?int $retryAfter = null): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $reset);
        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
        }
    }
}
