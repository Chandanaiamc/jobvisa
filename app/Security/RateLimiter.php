<?php

declare(strict_types=1);

namespace JobVisa\App\Security;

/**
 * Simple session-backed rate limiter (no schema changes).
 */
final class RateLimiter
{
    /**
     * Whether the key has exceeded max attempts within the decay window.
     */
    public function tooMany(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        if (!(bool) config('security.rate_limit_enabled', true)) {
            return false;
        }

        SessionManager::start();
        $bucket = $_SESSION['_rate_limits'][$key] ?? null;

        if (!is_array($bucket)) {
            return false;
        }

        $resetAt = (int) ($bucket['reset_at'] ?? 0);

        if ($resetAt < time()) {
            unset($_SESSION['_rate_limits'][$key]);

            return false;
        }

        return (int) ($bucket['attempts'] ?? 0) >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        if (!(bool) config('security.rate_limit_enabled', true)) {
            return;
        }

        SessionManager::start();
        $bucket = $_SESSION['_rate_limits'][$key] ?? null;
        $now = time();

        if (!is_array($bucket) || (int) ($bucket['reset_at'] ?? 0) < $now) {
            $_SESSION['_rate_limits'][$key] = [
                'attempts' => 1,
                'reset_at' => $now + max(1, $decaySeconds),
            ];

            return;
        }

        $_SESSION['_rate_limits'][$key]['attempts'] = (int) ($bucket['attempts'] ?? 0) + 1;
    }

    public function clear(string $key): void
    {
        SessionManager::start();
        unset($_SESSION['_rate_limits'][$key]);
    }
}
