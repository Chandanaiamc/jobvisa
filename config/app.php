<?php

declare(strict_types=1);

/**
 * Application configuration values.
 */

return [
    'name' => env('APP_NAME', 'JobVisa.lk'),
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost/jobvisa'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Colombo'),
    'version' => env('APP_VERSION', '1.0.0'),
    'version_tag' => env('APP_VERSION_TAG', 'v1.0.0'),

    // Legacy nested DB keys (used by existing Database class / helpers)
    'db' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'jobvisa_db'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
];
