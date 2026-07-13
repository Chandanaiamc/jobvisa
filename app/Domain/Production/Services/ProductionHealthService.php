<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Production\Services;

use App\Core\Database;
use JobVisa\App\Domain\Production\Support\ProductionReadinessVersion;
use Throwable;

/**
 * Aggregates live/ready health payloads for ops probes.
 */
final class ProductionHealthService
{
    public function __construct(
        private readonly ProductionEnvironmentGuard $guard
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function live(): array
    {
        return [
            'status' => 'ok',
            'check' => 'live',
            'app' => (string) config('app.name', 'JobVisa.lk'),
            'env' => (string) config('app.env', 'local'),
            'version' => ProductionReadinessVersion::CURRENT,
            'time' => gmdate('c'),
        ];
    }

    /**
     * @return array{payload: array<string, mixed>, http_status: int}
     */
    public function ready(): array
    {
        $dbOk = false;
        $dbError = null;
        $mysqlVersion = null;
        try {
            Database::connection();
            $mysqlVersion = (string) Database::query('SELECT VERSION()')->fetchColumn();
            $dbOk = true;
        } catch (Throwable $e) {
            $dbError = (bool) config('app.debug', false)
                ? $e->getMessage()
                : 'database_unavailable';
        }

        $storageWritable = is_writable(base_path('storage/logs'));
        $uploadsWritable = is_writable(base_path('storage/uploads')) || is_dir(base_path('storage/uploads'));
        $guard = $this->guard->evaluate(false);
        $env = strtolower((string) config('app.env', 'local'));
        $isProd = in_array($env, ['production', 'prod', 'staging'], true);
        $guardBlocks = $isProd && !$guard['ok'];

        $ready = $dbOk && $storageWritable && !$guardBlocks;
        $payload = [
            'status' => $ready ? 'ok' : 'degraded',
            'check' => 'ready',
            'app' => (string) config('app.name', 'JobVisa.lk'),
            'env' => (string) config('app.env', 'local'),
            'version' => ProductionReadinessVersion::CURRENT,
            'php' => PHP_VERSION,
            'time' => gmdate('c'),
            'checks' => [
                'database' => [
                    'ok' => $dbOk,
                    'mysql_version' => $mysqlVersion,
                    'error' => $dbError,
                ],
                'storage_logs_writable' => $storageWritable,
                'storage_uploads_present' => $uploadsWritable,
                'production_guard' => [
                    'ok' => $guard['ok'],
                    'issues' => $isProd ? $guard['issues'] : [],
                ],
            ],
        ];

        return [
            'payload' => $payload,
            'http_status' => $ready ? 200 : 503,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $live = $this->live();
        $ready = $this->ready();

        return [
            'live' => $live,
            'ready' => $ready['payload'],
            'http_status' => $ready['http_status'],
            'rules_version' => ProductionReadinessVersion::CURRENT,
        ];
    }
}
