<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

/**
 * Fail-safe rollback: restore backup, reverse last migration batch, restore maintenance.
 */
final class RollbackManager
{
    public function __construct(
        private readonly BackupManager $backups,
        private readonly MigrationRunner $migrations,
        private readonly MaintenanceModeManager $maintenance,
        private readonly DeploymentAuditLog $audit,
    ) {
    }

    /**
     * @return array{ok: bool, steps: array<string, mixed>, instructions: list<string>, message: string}
     */
    public function plan(?string $backupPath = null): array
    {
        $backup = $backupPath ?? $this->backups->latestBackup();
        $mig = $this->migrations->status();
        $instructions = [
            '1. Keep maintenance enabled until rollback completes.',
            '2. Restore DB from backup: ' . ($backup ?? '(no backup found — create one before deploy)'),
            '3. Optionally roll back last migration batch via MigrationRunner::rollbackLast().',
            '4. Clear application cache (CacheInterface::flush).',
            '5. Run health checks (/health/ready).',
            '6. Disable maintenance only after ready=ok.',
            '7. Never commit secrets; verify APP_ENV and CSRF still work.',
        ];

        return [
            'ok' => true,
            'message' => 'rollback_plan',
            'backup' => $backup,
            'migrations' => $mig,
            'maintenance_active' => $this->maintenance->isActive(),
            'instructions' => $instructions,
            'steps' => [
                'restore_backup' => $backup !== null,
                'rollback_migrations' => ($mig['pending'] ?? []) === [] || true,
                'restore_maintenance_state' => true,
            ],
        ];
    }

    /**
     * @return array{ok: bool, steps: array<string, mixed>, message: string, dry_run?: bool}
     */
    public function execute(bool $dryRun = false, bool $restoreBackup = false, bool $rollbackMigrations = true): array
    {
        $steps = [];
        $this->audit->write('rollback_started', ['dry_run' => $dryRun]);

        $steps['maintenance_enable'] = $this->maintenance->enable(['reason' => 'rollback'], $dryRun);

        if ($rollbackMigrations) {
            $steps['migrations'] = $this->migrations->rollbackLast($dryRun);
        }

        if ($restoreBackup) {
            $latest = $this->backups->latestBackup();
            $steps['restore'] = $latest !== null
                ? $this->backups->restore($latest, $dryRun)
                : ['ok' => false, 'message' => 'no_backup'];
        } else {
            $steps['restore'] = ['ok' => true, 'message' => 'skipped_use_migration_rollback'];
        }

        // Leave maintenance on after failed deploy; caller may disable on success path.
        $ok = true;
        foreach (['migrations', 'restore'] as $key) {
            if (isset($steps[$key]['ok']) && $steps[$key]['ok'] === false) {
                $ok = false;
            }
        }

        $report = [
            'ok' => $ok,
            'dry_run' => $dryRun,
            'steps' => $steps,
            'message' => $ok ? 'rollback_complete' : 'rollback_failed',
            'plan' => $this->plan(),
        ];
        $this->audit->write('rollback_finished', ['ok' => $ok, 'dry_run' => $dryRun]);
        $this->audit->saveReport('rollback-' . gmdate('YmdHis'), $report);

        return $report;
    }
}
