<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

/**
 * Validates env, config, DB, and writable storage before deploy.
 */
final class EnvironmentValidator
{
    /**
     * @return array{ok: bool, checks: list<array{name: string, ok: bool, message: string}>}
     */
    public function validate(): array
    {
        $checks = [];

        $required = config('deployment.required_env', []);
        $required = is_array($required) ? $required : [];
        foreach ($required as $key) {
            $key = (string) $key;
            $val = env($key, null);
            $ok = $val !== null && trim((string) $val) !== '';
            $checks[] = [
                'name' => 'env.' . $key,
                'ok' => $ok,
                'message' => $ok ? 'present' : 'missing_or_empty',
            ];
        }

        $env = strtolower((string) config('app.env', 'local'));
        $debug = (bool) config('app.debug', false);
        $prodLike = in_array($env, ['production', 'prod', 'staging'], true);
        $checks[] = [
            'name' => 'config.app_env',
            'ok' => $env !== '',
            'message' => $env,
        ];
        $checks[] = [
            'name' => 'config.debug_safe',
            'ok' => !$prodLike || !$debug,
            'message' => $prodLike && $debug ? 'APP_DEBUG must be false in staging/production' : 'ok',
        ];

        $dbOk = false;
        $dbMsg = 'unavailable';
        try {
            \App\Core\Database::connection();
            $ver = (string) \App\Core\Database::query('SELECT 1')->fetchColumn();
            $dbOk = $ver === '1';
            $dbMsg = $dbOk ? 'connected' : 'unexpected_response';
        } catch (\Throwable $e) {
            $dbMsg = 'connection_failed';
        }
        $checks[] = [
            'name' => 'database.connectivity',
            'ok' => $dbOk,
            'message' => $dbMsg,
        ];

        $paths = config('deployment.storage_paths', []);
        $paths = is_array($paths) ? $paths : [];
        foreach ($paths as $rel) {
            $abs = base_path((string) $rel);
            if (!is_dir($abs)) {
                @mkdir($abs, 0755, true);
            }
            $writable = is_dir($abs) && is_writable($abs);
            $checks[] = [
                'name' => 'storage.' . str_replace(['/', '\\'], '.', (string) $rel),
                'ok' => $writable,
                'message' => $writable ? 'writable' : 'not_writable',
            ];
        }

        $ok = true;
        foreach ($checks as $c) {
            if (!($c['ok'] ?? false)) {
                $ok = false;
                break;
            }
        }

        return ['ok' => $ok, 'checks' => $checks];
    }
}
