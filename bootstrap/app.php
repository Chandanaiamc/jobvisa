<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Loads autoload, env/config, DI container, service providers, then App.
 */

use App\Core\App;
use App\Core\Config;
use JobVisa\App\Providers\ProviderManager;

require_once __DIR__ . '/autoload.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

$basePath = dirname(__DIR__);

Config::loadEnv($basePath . '/.env');
Config::load($basePath . '/config');

/** @var \JobVisa\App\Container\Container $container */
$container = require __DIR__ . '/container.php';
$GLOBALS['jobvisa_container'] = $container;

/** @var list<class-string<\JobVisa\App\Providers\ServiceProvider>> $providerClasses */
$providerClasses = require $basePath . '/config/providers.php';

$providerManager = new ProviderManager($container, $providerClasses);
$providerManager->register();
$providerManager->boot();

$container->instance(ProviderManager::class, $providerManager);

$app = new App($basePath);

return $app;
