<?php

declare(strict_types=1);

/**
 * Monitoring & observability (Sprint 4.3).
 */

return [
    'enabled' => filter_var(env('OBS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'request_id_header' => (string) env('OBS_REQUEST_ID_HEADER', 'X-Request-Id'),
    'access_log' => filter_var(env('OBS_ACCESS_LOG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'access_log_sample' => max(1, min(100, (int) env('OBS_ACCESS_LOG_SAMPLE', '100'))),
    'metrics_enabled' => filter_var(env('OBS_METRICS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'metrics_secret' => (string) env('OBS_METRICS_SECRET', ''),
    'error_tracking' => filter_var(env('OBS_ERROR_TRACKING', 'true'), FILTER_VALIDATE_BOOLEAN),
    'error_ring_size' => max(10, min(500, (int) env('OBS_ERROR_RING_SIZE', '50'))),
    'alert_webhook_url' => trim((string) env('OBS_ALERT_WEBHOOK_URL', '')),
    'alert_on_5xx' => filter_var(env('OBS_ALERT_ON_5XX', 'false'), FILTER_VALIDATE_BOOLEAN),
    'skip_paths' => ['/health/live', '/health/ready', '/metrics'],
];
