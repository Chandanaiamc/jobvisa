<?php

declare(strict_types=1);

/**
 * Logging configuration.
 */

return [
    'channel' => env('LOG_CHANNEL', 'file'),
    'path' => env('LOG_PATH', 'storage/logs'),
    'level' => env('LOG_LEVEL', 'debug'),
    'daily_files' => filter_var(env('LOG_DAILY', 'true'), FILTER_VALIDATE_BOOLEAN),
];
