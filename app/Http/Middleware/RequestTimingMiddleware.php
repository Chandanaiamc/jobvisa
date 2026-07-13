<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Performance\Services\QueryProfiler;
use JobVisa\App\Logging\Logger;

/**
 * Adds Server-Timing / X-Response-Time and logs slow requests.
 */
final class RequestTimingMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!(bool) config('performance.response_timing', true)) {
            return $next();
        }

        $start = hrtime(true);
        $result = $next();
        $elapsedMs = (hrtime(true) - $start) / 1e6;

        if (!headers_sent()) {
            header('X-Response-Time: ' . round($elapsedMs, 1) . 'ms');
            $profiler = QueryProfiler::instance();
            if ($profiler !== null) {
                header('X-Query-Count: ' . (string) $profiler->count());
                header('Server-Timing: app;dur=' . round($elapsedMs, 1) . ', db;dur=' . $profiler->totalMs());
            } else {
                header('Server-Timing: app;dur=' . round($elapsedMs, 1));
            }
        }

        $budget = (float) config('performance.slow_request_ms', 800);
        if ($elapsedMs >= $budget) {
            Logger::warning('slow_request', [
                'ms' => round($elapsedMs, 1),
                'path' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                'queries' => QueryProfiler::instance()?->count() ?? 0,
            ]);
        }

        return $result;
    }
}
