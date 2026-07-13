<?php

declare(strict_types=1);

/**
 * Authentication configuration (Sprint 1).
 */

return [
    'password' => [
        'algo_preference' => 'argon2id',
        'bcrypt_cost' => 12,
    ],
    'login_attempts' => [
        'window_minutes' => 15,
        'max_failures' => 5,
    ],
    'remember' => [
        'enabled' => true,
        'token_bytes' => 32,
        'cookie_days' => 30,
    ],
    'password_reset' => [
        'expire_minutes' => 60,
        'max_attempts' => 5,
        'decay_seconds' => 900,
    ],
    'email_verification' => [
        'expire_hours' => 48,
        'resend_max_attempts' => 3,
        'resend_decay_seconds' => 600,
    ],
    'session_keys' => [
        'user_id' => 'auth.user_id',
        'role_id' => 'auth.role_id',
        'login_at' => 'auth.login_at',
    ],
    'dashboards' => [
        'admin' => '/admin',
        'super_admin' => '/admin',
        'employer' => '/employer',
        'seeker' => '/jobseeker',
        'staff' => '/admin',
        'default' => '/',
    ],
];
