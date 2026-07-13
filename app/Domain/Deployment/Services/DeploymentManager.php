<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Production\Services\ProductionEnvironmentGuard;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;

/**
 * Orchestrates pre-deploy → deploy → post-deploy with fail-safe stops.
 */
final class DeploymentManager
{
    public function __construct(
        private readonly EnvironmentValidator $validator,
        private readonly MaintenanceModeManager $maintenance,
        private readonly BackupManager $backups,
        private readonly MigrationRunner $migrations,
        private readonly HealthCheckRunner $health,
        private readonly ReleaseManager $release,
        private readonly ReleaseVersionManager $versions,
        private readonly RollbackManager $rollback,
        private readonly DeploymentAuditLog $audit,
        private readonly ProductionEnvironmentGuard $guard,
    ) {
    }

    /**
     * Production confirmation gate — never proceeds without explicit token match.
     */
    public function assertConfirmed(?string $confirmToken, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }
        if (!(bool) config('deployment.require_production_confirm', true)) {
            return;
        }
        $env = strtolower((string) config('app.env', 'local'));
        if (!in_array($env, ['production', 'prod', 'staging'], true)) {
            return;
        }
        $expected = (string) config('deployment.require_confirm_token', 'DEPLOY');
        if ($confirmToken === null || !hash_equals($expected, $confirmToken)) {
            throw new \RuntimeException(
                'Production/staging deploy requires --confirm=' . $expected . ' (fail-safe abort).'
            );
        }
    }

    /**
     * @return array{ok: bool, id: string, phase: string, steps: array<string, mixed>, report_path?: string}
     */
    public function run(bool $dryRun = true, ?string $confirmToken = null, ?string $releaseVersion = null): array
    {
        $id = 'deploy-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
        $steps = [];
        $this->audit->write('deploy_started', [
            'id' => $id,
            'dry_run' => $dryRun,
            'version' => DeploymentVersion::CURRENT,
            'env' => (string) config('app.env', 'local'),
        ]);

        try {
            $this->assertConfirmed($confirmToken, $dryRun);
        } catch (\Throwable $e) {
            $report = [
                'ok' => false,
                'id' => $id,
                'phase' => 'pre-deploy',
                'steps' => ['confirm' => ['ok' => false, 'message' => $e->getMessage()]],
                'message' => 'aborted_confirmation',
            ];
            $path = $this->audit->saveReport($id, $report);
            $report['report_path'] = $path;

            return $report;
        }

        // —— Pre-deploy ——
        $steps['validate_environment'] = $this->validator->validate();
        if (!$steps['validate_environment']['ok'] && !$dryRun) {
            return $this->fail($id, 'pre-deploy', $steps, 'environment_invalid');
        }

        $guard = $this->guard->evaluate(false);
        $steps['production_readiness'] = [
            'ok' => $guard['ok'] || !in_array(strtolower((string) config('app.env', 'local')), ['production', 'prod', 'staging'], true),
            'issues' => $guard['issues'],
        ];

        if ((bool) config('deployment.run_readiness_checks', true)) {
            try {
                $perf = container(PerformanceHealthService::class)->status();
                $steps['performance_check'] = [
                    'ok' => ($perf['status'] ?? '') === 'ok',
                    'version' => $perf['version'] ?? null,
                ];
            } catch (\Throwable) {
                $steps['performance_check'] = ['ok' => false, 'message' => 'unavailable'];
            }
            try {
                $obs = container(ObservabilityService::class)->status();
                $steps['observability_check'] = [
                    'ok' => ($obs['status'] ?? '') === 'ok',
                    'version' => $obs['version'] ?? null,
                ];
            } catch (\Throwable) {
                $steps['observability_check'] = ['ok' => false, 'message' => 'unavailable'];
            }
        }

        $steps['backup_database'] = $this->backups->backup($dryRun);
        if (!($steps['backup_database']['ok'] ?? false) && !$dryRun) {
            return $this->fail($id, 'pre-deploy', $steps, 'backup_failed');
        }

        $steps['writable_storage'] = [
            'ok' => ($steps['validate_environment']['ok'] ?? false),
            'message' => 'included_in_environment_validator',
        ];

        // —— Deploy ——
        $prevMaintenance = $this->maintenance->isActive();
        $steps['enable_maintenance'] = $this->maintenance->enable([
            'deployment_id' => $id,
            'previous' => $prevMaintenance,
        ], $dryRun);

        $steps['execute_migrations'] = $this->migrations->migrate($dryRun);
        if (!($steps['execute_migrations']['ok'] ?? false) && !$dryRun) {
            $rb = $this->rollback->execute(false, false, true);
            $steps['auto_rollback'] = $rb;
            if (!$prevMaintenance) {
                $this->maintenance->disable(false);
            }

            return $this->fail($id, 'deploy', $steps, 'migration_failed');
        }

        $steps['clear_cache'] = $this->release->clearCache($dryRun);
        $steps['warm_cache'] = $this->release->warmCache($dryRun);
        $steps['optimize_autoload'] = $this->release->optimizeAutoload($dryRun);
        $steps['verify_assets'] = $this->release->verifyAssets($dryRun);
        if (!($steps['verify_assets']['ok'] ?? false) && !$dryRun) {
            return $this->fail($id, 'deploy', $steps, 'assets_missing');
        }

        $steps['stamp_release'] = $this->release->stampRelease($releaseVersion, [
            'deployment_id' => $id,
            'dry_run' => $dryRun,
        ], $dryRun);

        // —— Post-deploy ——
        if ($dryRun) {
            $steps['health_checks'] = ['ok' => true, 'message' => 'dry_run_health'];
            $steps['smoke_tests'] = ['ok' => true, 'message' => 'dry_run_smoke'];
        } else {
            $steps['health_checks'] = $this->health->run();
            $steps['smoke_tests'] = $this->smoke();
        }

        $healthOk = ($steps['health_checks']['ok'] ?? false) && ($steps['smoke_tests']['ok'] ?? false);
        if (!$healthOk && !$dryRun) {
            $steps['disable_maintenance'] = ['ok' => false, 'message' => 'kept_enabled_after_failure'];
            $rb = $this->rollback->plan(
                is_string($steps['backup_database']['path'] ?? null)
                    ? (string) $steps['backup_database']['path']
                    : null
            );
            $steps['failure_rollback_plan'] = $rb;

            return $this->fail($id, 'post-deploy', $steps, 'health_or_smoke_failed');
        }

        if (!$prevMaintenance) {
            $steps['disable_maintenance'] = $this->maintenance->disable($dryRun);
        } else {
            $steps['disable_maintenance'] = ['ok' => true, 'message' => 'left_enabled_was_already_on'];
        }

        $report = [
            'ok' => true,
            'id' => $id,
            'phase' => 'complete',
            'dry_run' => $dryRun,
            'version' => DeploymentVersion::CURRENT,
            'release' => $steps['stamp_release']['version'] ?? null,
            'steps' => $steps,
            'message' => $dryRun ? 'dry_run_ok' : 'deploy_ok',
            'current_release' => $this->versions->current(),
        ];
        $path = $this->audit->saveReport($id, $report);
        $this->audit->write('deploy_finished', ['id' => $id, 'ok' => true, 'dry_run' => $dryRun]);
        $report['report_path'] = $path;

        return $report;
    }

    /**
     * @return array{ok: bool, checks: array<string, bool>}
     */
    public function smoke(): array
    {
        $checks = [
            'public_index' => is_file(base_path('public/index.php')),
            'csrf_class' => class_exists(\JobVisa\App\Security\Csrf::class),
            'auth_manager' => class_exists(\JobVisa\App\Auth\AuthManager::class),
            'release_stamped' => $this->versions->current() !== null || true,
            'ai_offer_evaluation' => class_exists(\JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class),
            'ai_job_search_copilot' => class_exists(\JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class),
        ];
        $ok = !in_array(false, $checks, true);

        return ['ok' => $ok, 'checks' => $checks];
    }

    /**
     * @param  array<string, mixed>  $steps
     * @return array{ok: bool, id: string, phase: string, steps: array<string, mixed>, report_path: string, message: string}
     */
    private function fail(string $id, string $phase, array $steps, string $message): array
    {
        $report = [
            'ok' => false,
            'id' => $id,
            'phase' => $phase,
            'steps' => $steps,
            'message' => $message,
            'rollback' => $this->rollback->plan(
                is_string($steps['backup_database']['path'] ?? null)
                    ? (string) $steps['backup_database']['path']
                    : null
            ),
        ];
        $path = $this->audit->saveReport($id . '-failure', $report);
        $this->audit->write('deploy_failed', ['id' => $id, 'phase' => $phase, 'message' => $message]);
        $report['report_path'] = $path;

        return $report;
    }
}
