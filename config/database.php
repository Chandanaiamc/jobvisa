<?php

declare(strict_types=1);

/**
 * Database configuration (credentials from environment only).
 * This file must not open connections or echo output.
 */

return [
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '3306'),
    'name' => env('DB_NAME', 'jobvisa_db'),
    'user' => env('DB_USER', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
