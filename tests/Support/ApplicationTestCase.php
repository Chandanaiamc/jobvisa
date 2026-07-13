<?php

declare(strict_types=1);

namespace JobVisa\Tests\Support;

use JobVisa\App\Container\Container;
use JobVisa\App\Providers\ProviderManager;
use PHPUnit\Framework\TestCase;

/**
 * Boots the full application container once per process for Feature/Integration/Api tests.
 */
abstract class ApplicationTestCase extends TestCase
{
    private static bool $booted = false;

    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$booted) {
            $base = dirname(__DIR__, 2);
            /** @var Container $container */
            $container = require $base . '/bootstrap/container.php';
            $GLOBALS['jobvisa_container'] = $container;
            $providers = require $base . '/config/providers.php';
            $manager = new ProviderManager($container, $providers);
            $manager->register();
            $manager->boot();
            self::$booted = true;
        }

        /** @var Container $container */
        $container = $GLOBALS['jobvisa_container'];
        $this->container = $container;
    }
}
