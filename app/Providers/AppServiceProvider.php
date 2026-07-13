<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Config\Config;

/**
 * Core application bindings.
 */
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $basePath = $this->appBasePath();

        $this->container->singleton(Config::class, static function () use ($basePath): Config {
            $config = new Config($basePath . DIRECTORY_SEPARATOR . 'config');
            $config->loadAll();

            return $config;
        });
    }

    public function boot(): void
    {
        $config = $this->container->get(Config::class);
        $timezone = (string) $config->get('app.timezone', 'Asia/Colombo');

        if ($timezone !== '' && function_exists('date_default_timezone_set')) {
            @date_default_timezone_set($timezone);
        }
    }
}
