<?php

declare(strict_types=1);

/**
 * Admin portal routes (placeholder). Prefix /admin from routing config.
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->get('/', 'PortalController@admin');
$router->get('/seekers/{id}', 'Admin\\SeekerProfileController@show');
