<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Cache\FileCache;
use JobVisa\App\Cache\NullCache;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Performance\Services\QueryProfiler;

/**
 * Cache + query profiler bindings (Sprint 4.2).
 */
final class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(CacheInterface::class, static function (): CacheInterface {
            if (!(bool) config('performance.cache_enabled', true)) {
                return new NullCache();
            }
            $driver = strtolower((string) config('performance.cache_driver', 'file'));
            if ($driver !== 'file') {
                // Redis reserved for a later sprint — fall back safely.
                return new FileCache(
                    base_path('storage/cache'),
                    (string) config('performance.cache_prefix', 'jobvisa')
                );
            }

            return new FileCache(
                base_path('storage/cache'),
                (string) config('performance.cache_prefix', 'jobvisa')
            );
        });

        $this->container->singleton(QueryProfiler::class, static function (): QueryProfiler {
            $enabled = (bool) config('performance.enabled', true)
                && (bool) config('performance.query_profile', false);

            return new QueryProfiler(
                $enabled,
                (float) config('performance.slow_query_ms', 100),
                (int) config('performance.query_budget', 40),
            );
        });

        $this->container->singleton(PerformanceHealthService::class, static function ($c): PerformanceHealthService {
            return new PerformanceHealthService(
                $c->get(CacheInterface::class),
                $c->get(QueryProfiler::class),
            );
        });
    }

    public function boot(): void
    {
        QueryProfiler::boot($this->container->get(QueryProfiler::class));

        $dir = base_path('storage/cache');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($deny)) {
            @file_put_contents($deny, "Require all denied\n");
        }
        $keep = $dir . DIRECTORY_SEPARATOR . '.gitkeep';
        if (!is_file($keep)) {
            @file_put_contents($keep, '');
        }
    }
}
