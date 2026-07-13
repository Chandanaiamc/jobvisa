<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Logging\Logger;

/**
 * Logging service bindings.
 */
final class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Logger::class, static fn (): Logger => new Logger());
    }

    public function boot(): void
    {
        // File logger is ready for use via Logger::* or container(Logger::class).
    }
}
