<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use JobVisa\App\Domain\Observability\Services\ObservabilityService;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;

/**
 * Runs post-deploy health / readiness probes in-process.
 */
final class HealthCheckRunner
{
    public function __construct(
        private readonly ProductionHealthService $production,
        private readonly PerformanceHealthService $performance,
        private readonly ObservabilityService $observability,
    ) {
    }

    /**
     * @return array{ok: bool, checks: array<string, mixed>}
     */
    public function run(): array
    {
        $live = $this->production->live();
        $ready = $this->production->ready();
        $perf = $this->performance->status();
        $obs = $this->observability->status();

        $checks = [
            'live' => [
                'ok' => ($live['status'] ?? '') === 'ok',
                'payload' => $live,
            ],
            'ready' => [
                'ok' => ($ready['payload']['status'] ?? '') === 'ok',
                'http_status' => $ready['http_status'] ?? 503,
                'payload' => $ready['payload'] ?? [],
            ],
            'performance' => [
                'ok' => ($perf['status'] ?? '') === 'ok',
                'version' => $perf['version'] ?? null,
            ],
            'observability' => [
                'ok' => ($obs['status'] ?? '') === 'ok',
                'version' => $obs['version'] ?? null,
            ],
        ];

        $ok = true;
        foreach ($checks as $c) {
            if (!($c['ok'] ?? false)) {
                $ok = false;
                break;
            }
        }

        return ['ok' => $ok, 'checks' => $checks];
    }

    /**
     * Invoke composer readiness CLIs (optional deeper gate).
     *
     * @return array{ok: bool, results: array<string, array{ok: bool, output: string}>}
     */
    public function runExternalChecks(bool $dryRun = false): array
    {
        $scripts = [
            'production' => 'scripts/production-check.php',
            'performance' => 'scripts/performance-check.php',
            'observability' => 'scripts/observability-check.php',
        ];
        $results = [];
        $allOk = true;
        $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
        foreach ($scripts as $name => $rel) {
            if ($dryRun) {
                $results[$name] = ['ok' => true, 'output' => 'dry_run_skipped'];
                continue;
            }
            $path = base_path($rel);
            if (!is_file($path)) {
                $results[$name] = ['ok' => false, 'output' => 'missing'];
                $allOk = false;
                continue;
            }
            $out = [];
            $code = 0;
            exec(escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1', $out, $code);
            $text = implode("\n", $out);
            $pass = $code === 0 && str_contains($text, 'PASS');
            $results[$name] = ['ok' => $pass, 'output' => mb_substr($text, -500)];
            if (!$pass) {
                $allOk = false;
            }
        }

        return ['ok' => $allOk, 'results' => $results];
    }
}
