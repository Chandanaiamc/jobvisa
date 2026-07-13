<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Security\SessionManager;

/**
 * HTTP session bindings and secure session start.
 */
final class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(SessionManager::class, static fn (): SessionManager => new SessionManager());
    }

    public function boot(): void
    {
        SessionManager::start();
    }
}
