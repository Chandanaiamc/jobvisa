<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Observability\Services\AlertNotifier;
use JobVisa\App\Domain\Observability\Services\ErrorTracker;
use JobVisa\App\Domain\Observability\Services\MetricsStore;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;

/**
 * Observability bindings (Sprint 4.3).
 */
final class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(MetricsStore::class, static function (): MetricsStore {
            return new MetricsStore(
                base_path('storage/metrics'),
                (bool) config('observability.enabled', true)
                    && (bool) config('observability.metrics_enabled', true),
            );
        });

        $this->container->singleton(ErrorTracker::class, static function (): ErrorTracker {
            return new ErrorTracker(
                base_path('storage/metrics/errors-recent.json'),
                (bool) config('observability.enabled', true)
                    && (bool) config('observability.error_tracking', true),
                (int) config('observability.error_ring_size', 50),
            );
        });

        $this->container->singleton(AlertNotifier::class, static function (): AlertNotifier {
            return new AlertNotifier(
                (string) config('observability.alert_webhook_url', ''),
                (bool) config('observability.alert_on_5xx', false),
            );
        });

        $this->container->singleton(ObservabilityService::class, static function ($c): ObservabilityService {
            return new ObservabilityService(
                $c->get(MetricsStore::class),
                $c->get(ErrorTracker::class),
                $c->get(AlertNotifier::class),
            );
        });
    }

    public function boot(): void
    {
        $dir = base_path('storage/metrics');
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
