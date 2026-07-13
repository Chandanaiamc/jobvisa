<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Production\Services;

use JobVisa\App\Logging\Logger;

/**
 * Validates production environment hardening rules.
 */
final class ProductionEnvironmentGuard
{
    /**
     * @return list<array{level: string, code: string, message: string}>
     */
    public function audit(): array
    {
        $issues = [];
        $env = strtolower((string) config('app.env', 'local'));
        $debug = (bool) config('app.debug', false);
        $isProd = in_array($env, ['production', 'prod', 'staging'], true);

        if ($isProd && $debug) {
            $issues[] = [
                'level' => 'critical',
                'code' => 'debug_enabled',
                'message' => 'APP_DEBUG must be false in production/staging.',
            ];
        }

        if ($isProd && (string) config('app.url', '') === '') {
            $issues[] = [
                'level' => 'critical',
                'code' => 'missing_app_url',
                'message' => 'APP_URL is required in production/staging.',
            ];
        }

        if ($isProd && !(bool) config('session.secure', false) && (bool) config('production.force_https', false)) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'session_insecure_cookie',
                'message' => 'SESSION_SECURE should be true when FORCE_HTTPS is enabled.',
            ];
        }

        if ($isProd && !(bool) config('production.force_https', false)) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'https_not_forced',
                'message' => 'FORCE_HTTPS is recommended for production.',
            ];
        }

        if ($isProd && (string) config('app.db.password', '') === '' && strtolower((string) config('app.db.user', '')) === 'root') {
            $issues[] = [
                'level' => 'critical',
                'code' => 'insecure_db_credentials',
                'message' => 'Production must not use root with an empty DB password.',
            ];
        }

        $seedAdmin = (string) env('SEED_ADMIN_PASSWORD', '');
        if ($isProd && ($seedAdmin === '' || str_starts_with($seedAdmin, 'ChangeMe'))) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'default_seed_password',
                'message' => 'Default SEED_* passwords must not be used in shared environments.',
            ];
        }

        if (!(bool) config('production.security_headers', true) && $isProd) {
            $issues[] = [
                'level' => 'warning',
                'code' => 'security_headers_disabled',
                'message' => 'SECURITY_HEADERS_ENABLED is false in production.',
            ];
        }

        if ($isProd && trim((string) env('APP_KEY', '')) === '') {
            $issues[] = [
                'level' => 'critical',
                'code' => 'missing_app_key',
                'message' => 'APP_KEY must be set in production/staging for token hashing and secret derivation.',
            ];
        }

        return $issues;
    }

    /**
     * @return array{ok: bool, issues: list<array{level: string, code: string, message: string}>}
     */
    public function evaluate(bool $enforce = false): array
    {
        $issues = $this->audit();
        $critical = array_values(array_filter(
            $issues,
            static fn (array $i): bool => ($i['level'] ?? '') === 'critical'
        ));

        if ($critical !== []) {
            foreach ($critical as $issue) {
                Logger::security('production_guard_critical', [
                    'code' => $issue['code'],
                    'message' => $issue['message'],
                ]);
            }
        }

        $ok = $critical === [];
        if ($enforce && !$ok && (bool) config('production.fail_on_insecure_production', true)) {
            $env = strtolower((string) config('app.env', 'local'));
            if (in_array($env, ['production', 'prod'], true)) {
                throw new \RuntimeException(
                    'Production environment failed security readiness checks: '
                    . implode('; ', array_map(static fn (array $i): string => $i['message'], $critical))
                );
            }
        }

        return [
            'ok' => $ok,
            'issues' => $issues,
        ];
    }
}
