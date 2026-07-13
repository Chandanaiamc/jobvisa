<?php

declare(strict_types=1);

/**
 * Ops / load-balancer probes (no session) — Sprint 4.1.
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->gets([
    '/health' => 'ProductionHealthController@index',
    '/health/live' => 'ProductionHealthController@live',
    '/health/ready' => 'ProductionHealthController@ready',
    '/health/performance' => 'PerformanceHealthController@index',
    '/health/observability' => 'ObservabilityController@index',
    '/metrics' => 'ObservabilityController@metrics',
]);
