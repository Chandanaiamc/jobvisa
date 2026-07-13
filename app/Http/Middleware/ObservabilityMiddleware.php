<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Observability\Services\ObservabilityService;

/**
 * Correlation ID + access metrics/logging for each request.
 */
final class ObservabilityMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!(bool) config('observability.enabled', true)) {
            return $next();
        }

        $headerName = (string) config('observability.request_id_header', 'X-Request-Id');
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        $incoming = (string) ($_SERVER[$serverKey] ?? '');

        /** @var ObservabilityService $obs */
        $obs = container(ObservabilityService::class);
        $ctx = $obs->startRequest($incoming !== '' ? $incoming : null);

        if (!headers_sent()) {
            header($headerName . ': ' . $ctx->requestId());
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $skip = config('observability.skip_paths', []);
        $skip = is_array($skip) ? $skip : [];

        try {
            $result = $next();
            $status = http_response_code();
            if (!is_int($status) || $status < 100) {
                $status = 200;
            }
            if (!in_array($path, $skip, true)) {
                $obs->finishRequest($ctx, $status, $method, $path);
            }

            return $result;
        } catch (\Throwable $e) {
            if (!in_array($path, $skip, true)) {
                $obs->finishRequest($ctx, 500, $method, $path);
            }
            throw $e;
        }
    }
}
