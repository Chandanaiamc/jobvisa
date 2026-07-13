<?php

declare(strict_types=1);

/**
 * HTTP session configuration (values from environment).
 */

return [
    'name' => env('SESSION_NAME', 'jobvisa_session'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'secure' => filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
    'http_only' => filter_var(env('SESSION_HTTP_ONLY', 'true'), FILTER_VALIDATE_BOOLEAN),
    'same_site' => env('SESSION_SAME_SITE', 'Lax'),
    'path' => '/',
];
