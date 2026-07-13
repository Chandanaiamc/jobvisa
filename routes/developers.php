<?php

declare(strict_types=1);

/**
 * Developer portal routes (Sprint 4.6).
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->gets([
    '/developers' => 'Developers\\DeveloperPortalController@index',
    '/developers/getting-started' => 'Developers\\DeveloperPortalController@gettingStarted',
    '/developers/authentication' => 'Developers\\DeveloperPortalController@authentication',
    '/developers/endpoints' => 'Developers\\DeveloperPortalController@endpoints',
    '/developers/errors' => 'Developers\\DeveloperPortalController@errors',
    '/developers/webhooks' => 'Developers\\DeveloperPortalController@webhooks',
    '/developers/sdk' => 'Developers\\DeveloperPortalController@sdk',
    '/developers/openapi' => 'Developers\\DeveloperPortalController@openapi',
]);

$router->group('developers.tokens', static function ($router): void {
    $router->get('/developers/tokens', 'Developers\\DeveloperPortalController@tokens');
    $router->post('/developers/tokens', 'Developers\\DeveloperPortalController@createToken');
    $router->post('/developers/tokens/{token}/revoke', 'Developers\\DeveloperPortalController@revokeToken');
}, [
    'middleware' => ['remember', 'auth.web', 'csrf'],
]);
