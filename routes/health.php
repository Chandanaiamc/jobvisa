<?php

declare(strict_types=1);

/**
 * Health check routes (URLs unchanged).
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->gets([
    '/health/database' => 'HealthController@database',
    '/health/container' => 'ContainerHealthController@index',
]);
