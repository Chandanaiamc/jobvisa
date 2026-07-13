<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Audit\ApiAuditLogger;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Http\ApiResponse;
use JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter;

/**
 * Enterprise API middleware: CORS, JSON, rate limit, audit.
 */
final class ApiMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Content-Type: application/json; charset=utf-8');
        }

        $this->applyCors();

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
            http_response_code(204);
            return null;
        }

        if (!(bool) config('api.enabled', true)) {
            ApiResponse::error('api_disabled', 'API platform is disabled.', 503);

            return null;
        }

        $started = hrtime(true);
        ApiAuth::clear();

        try {
            container(ApiRateLimiter::class)->enforce();
            $result = $next();
            $status = http_response_code();
            if (!is_int($status) || $status < 100) {
                $status = 200;
            }
            $this->audit($started, $status);

            return $result;
        } catch (ApiException $e) {
            $this->audit($started, $e->status());
            if ($e->status() === 429 && !headers_sent()) {
                $retry = (int) ($e->details()['retry_after'] ?? 60);
                header('Retry-After: ' . $retry);
            }
            ApiResponse::error($e->errorCode(), $e->getMessage(), $e->status(), $e->details());

            return null;
        } catch (\Throwable $e) {
            $this->audit($started, 500);
            if (ApiResponse::isApiRequest()) {
                $debug = (bool) config('app.debug', false);
                ApiResponse::error(
                    'server_error',
                    $debug ? $e->getMessage() : 'An unexpected error occurred.',
                    500
                );

                return null;
            }
            throw $e;
        }
    }

    private function applyCors(): void
    {
        if (!(bool) config('api.cors_enabled', true) || headers_sent()) {
            return;
        }
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        $allowed = config('api.cors_allowed_origins', []);
        $allowed = is_array($allowed) ? $allowed : [];

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        } elseif ($allowed === [] && (string) config('app.env', 'local') === 'local') {
            // Local-only permissive CORS when allow-list empty.
            if ($origin !== '') {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }
        }

        $methods = config('api.cors_allowed_methods', ['GET', 'POST', 'OPTIONS']);
        $headers = config('api.cors_allowed_headers', ['Authorization', 'Content-Type', 'Accept', 'X-Request-Id']);
        header('Access-Control-Allow-Methods: ' . implode(', ', is_array($methods) ? $methods : []));
        header('Access-Control-Allow-Headers: ' . implode(', ', is_array($headers) ? $headers : []));
        header('Access-Control-Max-Age: ' . (int) config('api.cors_max_age', 86400));
        header('Access-Control-Expose-Headers: X-Request-Id, X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, Retry-After');
    }

    private function audit(float $started, int $status): void
    {
        $ms = (hrtime(true) - $started) / 1e6;
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        try {
            container(ApiAuditLogger::class)->log($method, $path, $status, $ms, 0);
        } catch (\Throwable) {
        }
    }
}
