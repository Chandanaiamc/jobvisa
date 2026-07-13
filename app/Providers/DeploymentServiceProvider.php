<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Domain\Deployment\Services\BackupManager;
use JobVisa\App\Domain\Deployment\Services\DeploymentAuditLog;
use JobVisa\App\Domain\Deployment\Services\DeploymentManager;
use JobVisa\App\Domain\Deployment\Services\EnvironmentValidator;
use JobVisa\App\Domain\Deployment\Services\HealthCheckRunner;
use JobVisa\App\Domain\Deployment\Services\MaintenanceModeManager;
use JobVisa\App\Domain\Deployment\Services\MigrationRunner;
use JobVisa\App\Domain\Deployment\Services\ReleaseManager;
use JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager;
use JobVisa\App\Domain\Deployment\Services\RollbackManager;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Production\Services\ProductionEnvironmentGuard;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;

/**
 * Deployment automation bindings (Sprint 4.4).
 */
final class DeploymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(MaintenanceModeManager::class, static fn (): MaintenanceModeManager => new MaintenanceModeManager());
        $this->container->singleton(EnvironmentValidator::class, static fn (): EnvironmentValidator => new EnvironmentValidator());
        $this->container->singleton(BackupManager::class, static fn (): BackupManager => new BackupManager());
        $this->container->singleton(MigrationRunner::class, static fn (): MigrationRunner => new MigrationRunner());
        $this->container->singleton(ReleaseVersionManager::class, static fn (): ReleaseVersionManager => new ReleaseVersionManager());
        $this->container->singleton(DeploymentAuditLog::class, static fn (): DeploymentAuditLog => new DeploymentAuditLog());

        $this->container->singleton(HealthCheckRunner::class, static function ($c): HealthCheckRunner {
            return new HealthCheckRunner(
                $c->get(ProductionHealthService::class),
                $c->get(PerformanceHealthService::class),
                $c->get(ObservabilityService::class),
            );
        });

        $this->container->singleton(ReleaseManager::class, static function ($c): ReleaseManager {
            return new ReleaseManager(
                $c->get(CacheInterface::class),
                $c->get(ReleaseVersionManager::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(LanguageCatalogRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
            );
        });

        $this->container->singleton(RollbackManager::class, static function ($c): RollbackManager {
            return new RollbackManager(
                $c->get(BackupManager::class),
                $c->get(MigrationRunner::class),
                $c->get(MaintenanceModeManager::class),
                $c->get(DeploymentAuditLog::class),
            );
        });

        $this->container->singleton(DeploymentManager::class, static function ($c): DeploymentManager {
            return new DeploymentManager(
                $c->get(EnvironmentValidator::class),
                $c->get(MaintenanceModeManager::class),
                $c->get(BackupManager::class),
                $c->get(MigrationRunner::class),
                $c->get(HealthCheckRunner::class),
                $c->get(ReleaseManager::class),
                $c->get(ReleaseVersionManager::class),
                $c->get(RollbackManager::class),
                $c->get(DeploymentAuditLog::class),
                $c->get(ProductionEnvironmentGuard::class),
            );
        });
    }

    public function boot(): void
    {
        foreach ([
            'storage/framework',
            'storage/backups',
            'storage/deployments',
            'storage/releases',
        ] as $rel) {
            $dir = base_path($rel);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
            if (!is_file($deny)) {
                @file_put_contents($deny, "Require all denied\n");
            }
            $keep = $dir . DIRECTORY_SEPARATOR . '.gitkeep';
            if (!is_file($keep)) {
                @file_put_contents($keep, '');
            }
        }
    }
}
