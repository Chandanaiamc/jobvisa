<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager;
use JobVisa\App\Domain\Release\Services\EnterpriseReleaseService;
use JobVisa\App\Domain\Release\Services\ReleaseManifestBuilder;

/**
 * Enterprise product release bindings (v1.0.0).
 */
final class ReleaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ReleaseManifestBuilder::class, static fn (): ReleaseManifestBuilder => new ReleaseManifestBuilder());
        $this->container->singleton(EnterpriseReleaseService::class, static function ($c): EnterpriseReleaseService {
            return new EnterpriseReleaseService(
                $c->get(ReleaseManifestBuilder::class),
                $c->get(ReleaseVersionManager::class),
            );
        });
    }

    public function boot(): void
    {
    }
}
