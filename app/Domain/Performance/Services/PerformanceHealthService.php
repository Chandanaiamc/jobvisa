<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Performance\Services;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Domain\Performance\Support\PerformanceVersion;

/**
 * Aggregates performance readiness signals for ops/CLI.
 */
final class PerformanceHealthService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly QueryProfiler $profiler,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $cacheDir = base_path('storage/cache');
        $writable = is_dir($cacheDir) ? is_writable($cacheDir) : @mkdir($cacheDir, 0755, true);

        $probeKey = 'perf.health.probe';
        $this->cache->put($probeKey, ['t' => time()], 60);
        $cacheOk = $this->cache->has($probeKey);
        $this->cache->forget($probeKey);

        return [
            'status' => ($writable && ((bool) config('performance.cache_enabled', true) ? $cacheOk : true)) ? 'ok' : 'degraded',
            'version' => PerformanceVersion::CURRENT,
            'cache' => [
                'enabled' => (bool) config('performance.cache_enabled', true),
                'driver' => (string) config('performance.cache_driver', 'file'),
                'writable' => (bool) $writable,
                'probe_ok' => $cacheOk,
            ],
            'query_profiler' => [
                'enabled' => (bool) config('performance.query_profile', false),
                'slow_query_ms' => (int) config('performance.slow_query_ms', 100),
                'budget' => (int) config('performance.query_budget', 40),
            ],
            'request_timing' => (bool) config('performance.response_timing', true),
            'pagination' => [
                'default_per_page' => (int) config('performance.default_per_page', 15),
                'max_per_page' => (int) config('performance.max_per_page', 50),
            ],
            'runtime' => $this->profiler->summary(),
        ];
    }
}
