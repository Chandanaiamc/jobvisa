<?php

declare(strict_types=1);

/**
 * Enterprise API platform (Sprint 4.5).
 */

return [
    'enabled' => filter_var(env('API_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'default_version' => (string) env('API_DEFAULT_VERSION', 'v1'),
    'token_prefix' => (string) env('API_TOKEN_PREFIX', 'jv1_'),
    'token_bytes' => max(16, min(64, (int) env('API_TOKEN_BYTES', '32'))),
    'token_default_ttl_days' => max(0, (int) env('API_TOKEN_TTL_DAYS', '365')),
    'rate_limit_enabled' => filter_var(env('API_RATE_LIMIT_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'rate_limit_driver' => (string) env('API_RATE_LIMIT_DRIVER', 'file'), // file|redis (redis reserved)
    'rate_limit_per_minute' => max(1, (int) env('API_RATE_LIMIT_PER_MINUTE', '120')),
    'rate_limit_auth_per_minute' => max(1, (int) env('API_RATE_LIMIT_AUTH_PER_MINUTE', '240')),
    'cors_enabled' => filter_var(env('API_CORS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'cors_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('API_CORS_ORIGINS', ''))
    ), static fn (string $v): bool => $v !== '')),
    'cors_allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'cors_allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Request-Id'],
    'cors_max_age' => max(0, (int) env('API_CORS_MAX_AGE', '86400')),
    'audit_enabled' => filter_var(env('API_AUDIT_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'webhooks_enabled' => filter_var(env('API_WEBHOOKS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'docs_enabled' => filter_var(env('API_DOCS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
];
