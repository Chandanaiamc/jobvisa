<?php

declare(strict_types=1);

/**
 * Build the empty application service container.
 *
 * Service bindings are registered by Service Providers (see config/providers.php).
 */

use JobVisa\App\Container\Container;

$container = new Container();
$container->instance(Container::class, $container);

return $container;
