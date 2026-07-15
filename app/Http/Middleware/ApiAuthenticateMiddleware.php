<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Api\Auth\ApiBearerAuthenticator;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Http\ApiResponse;

/**
 * Bearer-token authentication for /api routes (no CSRF).
 * Distinguishes short-lived access tokens from long-lived PATs.
 */
final class ApiAuthenticateMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        try {
            container(ApiBearerAuthenticator::class)->authenticateFromRequest();
            container(\JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter::class)->enforce();
        } catch (ApiException $e) {
            if ($e->status() === 429 && !headers_sent()) {
                $retry = (int) ($e->details()['retry_after'] ?? 60);
                header('Retry-After: ' . $retry);
            }
            ApiResponse::error($e->errorCode(), $e->getMessage(), $e->status(), $e->details());

            return null;
        }

        return $next();
    }
}
