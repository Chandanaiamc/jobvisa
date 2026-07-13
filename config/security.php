<?php

declare(strict_types=1);

/**
 * Enterprise security hardening (Sprint 4.7) + legacy security flags.
 */

return [
    'csrf_token_key' => (string) env('CSRF_TOKEN_KEY', '_csrf_token'),
    'password_algo' => (string) env('PASSWORD_ALGO', 'argon2id'),
    'rate_limit_enabled' => filter_var(env('RATE_LIMIT_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    // Password policy (defaults preserve current min length = 8)
    'password_min_length' => max(8, (int) env('PASSWORD_MIN_LENGTH', '8')),
    'password_require_mixed' => filter_var(env('PASSWORD_REQUIRE_MIXED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'password_require_number' => filter_var(env('PASSWORD_REQUIRE_NUMBER', 'false'), FILTER_VALIDATE_BOOLEAN),
    'password_require_symbol' => filter_var(env('PASSWORD_REQUIRE_SYMBOL', 'false'), FILTER_VALIDATE_BOOLEAN),

    // Remember-me pepper (OFF by default — enabling invalidates existing cookies)
    'remember_pepper_enabled' => filter_var(env('REMEMBER_PEPPER_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),

    // Security audit → audit_logs table
    'audit_enabled' => filter_var(env('SECURITY_AUDIT_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    // Content-Security-Policy (matches prior hardcoded defaults for compatibility)
    'csp_enabled' => filter_var(env('CSP_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'csp_policy' => (string) env(
        'CSP_POLICY',
        "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'"
    ),
];
