<?php

declare(strict_types=1);

/**
 * Performance & optimization (Sprint 4.2).
 */

return [
    'enabled' => filter_var(env('PERF_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'cache_enabled' => filter_var(env('CACHE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'cache_driver' => (string) env('CACHE_DRIVER', 'file'),
    'cache_prefix' => (string) env('CACHE_PREFIX', 'jobvisa'),
    'cache_ttl' => max(60, (int) env('CACHE_TTL', '3600')),
    'catalog_cache_ttl' => max(60, (int) env('CATALOG_CACHE_TTL', '3600')),
    'query_profile' => filter_var(env('QUERY_PROFILE', 'false'), FILTER_VALIDATE_BOOLEAN),
    'slow_query_ms' => max(1, (int) env('SLOW_QUERY_MS', '100')),
    'query_budget' => max(1, (int) env('QUERY_BUDGET', '40')),
    'response_timing' => filter_var(env('RESPONSE_TIMING', 'true'), FILTER_VALIDATE_BOOLEAN),
    'slow_request_ms' => max(1, (int) env('SLOW_REQUEST_MS', '800')),
    'default_per_page' => max(1, min(100, (int) env('DEFAULT_PER_PAGE', '15'))),
    'max_per_page' => max(1, min(200, (int) env('MAX_PER_PAGE', '50'))),
];
