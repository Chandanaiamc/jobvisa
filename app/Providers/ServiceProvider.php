<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Container\Container;

/**
 * Base service provider.
 *
 * register() — bind services into the container (no side effects).
 * boot()     — run after all providers have registered (safe to resolve).
 */
abstract class ServiceProvider
{
    public function __construct(
        protected Container $container
    ) {
    }

    /**
     * Register container bindings.
     */
    abstract public function register(): void;

    /**
     * Bootstrap services after the container is fully registered.
     */
    abstract public function boot(): void;

    protected function appBasePath(string $path = ''): string
    {
        $base = base_path($path);

        return $base;
    }
}
