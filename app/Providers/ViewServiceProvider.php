<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use App\Core\View;

/**
 * View renderer bindings.
 */
final class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(View::class, static function (): View {
            return new View(base_path('app/views'));
        });
    }

    public function boot(): void
    {
        // No UI changes — views resolve on demand.
    }
}
