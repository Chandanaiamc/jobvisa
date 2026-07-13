<?php

declare(strict_types=1);

/**
 * Mail configuration — log driver for local development.
 */
return [
    'driver' => env('MAIL_DRIVER', 'log'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@jobvisa.lk'),
        'name' => env('MAIL_FROM_NAME', 'JobVisa.lk'),
    ],
];
