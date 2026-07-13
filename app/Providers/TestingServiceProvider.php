<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Deployment\Services\DeploymentManager;
use JobVisa\App\Domain\Frontend\Services\FrontendPolishService;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;
use JobVisa\App\Domain\Security\Services\SecurityHardeningService;
use JobVisa\App\Domain\Testing\Services\QaGateRunner;
use JobVisa\App\Domain\Testing\Services\RegressionSuiteService;
use JobVisa\App\Domain\Testing\Services\ReleaseCandidateService;
use JobVisa\App\Domain\Testing\Services\SmokeTestService;

/**
 * Testing / QA / Release Candidate bindings (Sprint 4.9).
 */
final class TestingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ReleaseCandidateService::class, static fn (): ReleaseCandidateService => new ReleaseCandidateService());
        $this->container->singleton(QaGateRunner::class, static fn (): QaGateRunner => new QaGateRunner());
        $this->container->singleton(SmokeTestService::class, static function ($c): SmokeTestService {
            return new SmokeTestService(
                $c->get(ProductionHealthService::class),
                $c->get(PerformanceHealthService::class),
                $c->get(ObservabilityService::class),
                $c->get(SecurityHardeningService::class),
                $c->get(FrontendPolishService::class),
                $c->get(DeploymentManager::class),
                $c->get(PersonalAccessTokenService::class),
                $c->get(DeveloperPortalService::class),
            );
        });
        $this->container->singleton(RegressionSuiteService::class, static function ($c): RegressionSuiteService {
            return new RegressionSuiteService(
                $c->get(ReleaseCandidateService::class),
                $c->get(SmokeTestService::class),
                $c->get(QaGateRunner::class),
            );
        });
    }

    public function boot(): void
    {
    }
}
