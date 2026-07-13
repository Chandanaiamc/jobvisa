<?php

declare(strict_types=1);

/**
 * Production readiness flags (Sprint 4.1).
 */

return [
    'maintenance' => filter_var(env('APP_MAINTENANCE', 'false'), FILTER_VALIDATE_BOOLEAN),
    'maintenance_allow_ip' => trim((string) env('MAINTENANCE_ALLOW_IP', '')),
    'maintenance_secret' => (string) env('MAINTENANCE_SECRET', ''),
    'force_https' => filter_var(env('FORCE_HTTPS', 'false'), FILTER_VALIDATE_BOOLEAN),
    'security_headers' => filter_var(env('SECURITY_HEADERS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'hsts_enabled' => filter_var(env('HSTS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'hsts_max_age' => max(0, (int) env('HSTS_MAX_AGE', '31536000')),
    'frame_options' => (string) env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
    'referrer_policy' => (string) env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    'permissions_policy' => (string) env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ), static fn (string $v): bool => $v !== '')),
    'fail_on_insecure_production' => filter_var(env('FAIL_ON_INSECURE_PRODUCTION', 'true'), FILTER_VALIDATE_BOOLEAN),
];
