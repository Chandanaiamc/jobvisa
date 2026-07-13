<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Security\Services;

use JobVisa\App\Domain\Security\Support\SecurityHardeningVersion;

/**
 * Aggregates security hardening readiness for ops / CLI.
 */
final class SecurityHardeningService
{
    public function __construct(
        private readonly PasswordPolicy $passwordPolicy,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'status' => 'ok',
            'version' => SecurityHardeningVersion::CURRENT,
            'csrf_token_key' => (string) config('security.csrf_token_key', '_csrf_token'),
            'rate_limit_enabled' => (bool) config('security.rate_limit_enabled', true),
            'csp_enabled' => (bool) config('security.csp_enabled', true),
            'audit_enabled' => (bool) config('security.audit_enabled', true),
            'audit_schema' => $this->audit->ensureSchemaReady(),
            'password_policy' => [
                'min_length' => (int) config('security.password_min_length', 8),
                'require_mixed' => (bool) config('security.password_require_mixed', false),
                'require_number' => (bool) config('security.password_require_number', false),
                'require_symbol' => (bool) config('security.password_require_symbol', false),
            ],
            'remember_pepper_enabled' => (bool) config('security.remember_pepper_enabled', false),
            'app_key_configured' => trim((string) env('APP_KEY', '')) !== '',
            'trusted_proxies' => config('production.trusted_proxies', []),
            'owasp' => [
                'A01' => 'access_control_middleware',
                'A02' => 'argon2id_app_key_guard',
                'A03' => 'pdo_prepared_csp',
                'A04' => 'password_policy',
                'A05' => 'security_headers_config',
                'A07' => 'login_throttle_remember',
                'A09' => 'security_audit_logger',
            ],
            'password_policy_smoke' => $this->passwordPolicy->passes(str_repeat('a', (int) config('security.password_min_length', 8))),
        ];
    }
}
