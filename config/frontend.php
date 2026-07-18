<?php

declare(strict_types=1);

/**
 * Frontend polish & accessibility (Sprint 4.8).
 */

return [
    'enabled' => filter_var(env('FRONTEND_A11Y_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'skip_link' => filter_var(env('FRONTEND_SKIP_LINK', 'true'), FILTER_VALIDATE_BOOLEAN),
    'focus_visible' => filter_var(env('FRONTEND_FOCUS_VISIBLE', 'true'), FILTER_VALIDATE_BOOLEAN),
    'reduced_motion' => filter_var(env('FRONTEND_REDUCED_MOTION', 'true'), FILTER_VALIDATE_BOOLEAN),
    'api_auth' => [
        'enabled' => filter_var(env('FRONTEND_API_AUTH_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
        'access_cookie' => (string) env('FRONTEND_API_ACCESS_COOKIE', 'jobvisa_api_access'),
        'refresh_cookie' => (string) env('FRONTEND_API_REFRESH_COOKIE', 'jobvisa_api_refresh'),
    ],
];
