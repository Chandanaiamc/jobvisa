<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Container\Container;
use JobVisa\App\Providers\ServiceProvider as BaseServiceProvider;

/**
 * Registers and boots configured service providers in order.
 */
final class ProviderManager
{
    /** @var list<class-string<BaseServiceProvider>> */
    private array $providerClasses;

    /** @var list<BaseServiceProvider> */
    private array $providers = [];

    private bool $registered = false;

    private bool $booted = false;

    /**
     * @param  list<class-string<BaseServiceProvider>>  $providerClasses
     */
    public function __construct(
        private Container $container,
        array $providerClasses
    ) {
        $this->providerClasses = $providerClasses;
    }

    /**
     * Call register() on every provider.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->providerClasses as $providerClass) {
            /** @var BaseServiceProvider $provider */
            $provider = new $providerClass($this->container);
            $provider->register();
            $this->providers[] = $provider;
        }

        $this->registered = true;
    }

    /**
     * Call boot() on every provider after registration completes.
     */
    public function boot(): void
    {
        if (!$this->registered) {
            $this->register();
        }

        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }
}
