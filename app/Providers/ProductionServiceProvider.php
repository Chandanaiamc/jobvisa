<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Production\Services\ProductionEnvironmentGuard;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;
use JobVisa\App\Logging\Logger;

/**
 * Production readiness bindings and soft environment audit (Sprint 4.1).
 */
final class ProductionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ProductionEnvironmentGuard::class, static fn (): ProductionEnvironmentGuard => new ProductionEnvironmentGuard());
        $this->container->singleton(ProductionHealthService::class, static function ($c): ProductionHealthService {
            return new ProductionHealthService($c->get(ProductionEnvironmentGuard::class));
        });
    }

    public function boot(): void
    {
        $env = strtolower((string) config('app.env', 'local'));
        if (!in_array($env, ['production', 'prod', 'staging'], true)) {
            return;
        }

        $guard = $this->container->get(ProductionEnvironmentGuard::class);
        $result = $guard->evaluate(false);
        foreach ($result['issues'] as $issue) {
            $level = (string) ($issue['level'] ?? 'warning');
            $payload = [
                'code' => $issue['code'] ?? '',
                'message' => $issue['message'] ?? '',
            ];
            if ($level === 'critical') {
                Logger::security('production_readiness_critical', $payload);
            } else {
                Logger::warning('production_readiness_warning', $payload);
            }
        }

        // Hard-fail only when explicitly production and fail flag is on.
        if ($env === 'production' || $env === 'prod') {
            $guard->evaluate(true);
        }
    }
}
