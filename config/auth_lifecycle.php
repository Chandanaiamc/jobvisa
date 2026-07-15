<?php

declare(strict_types=1);

/**
 * Authentication & Token Lifecycle v2.
 */

return [
    'enabled' => filter_var(env('AUTH_LIFECYCLE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    // Phase 2: short-lived session access tokens (default 15 minutes).
    'access_ttl_seconds' => max(60, (int) env('AUTH_ACCESS_TTL_SECONDS', '900')),
    'access_prefix' => (string) env('AUTH_ACCESS_PREFIX', 'jva1_'),
    'refresh_ttl_days' => max(1, (int) env('AUTH_REFRESH_TTL_DAYS', '30')),
    'refresh_prefix' => (string) env('AUTH_REFRESH_PREFIX', 'jvr1_'),
    'mfa_enforced' => filter_var(env('AUTH_MFA_ENFORCED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'logout_everywhere_revokes_pats' => filter_var(env('AUTH_LOGOUT_ALL_REVOKE_PATS', 'true'), FILTER_VALIDATE_BOOLEAN),
];
