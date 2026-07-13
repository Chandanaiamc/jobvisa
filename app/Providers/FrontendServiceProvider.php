<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Frontend\Services\FrontendPolishService;

/**
 * Frontend polish & accessibility bindings (Sprint 4.8).
 */
final class FrontendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(FrontendPolishService::class, static fn (): FrontendPolishService => new FrontendPolishService());
    }

    public function boot(): void
    {
    }
}
