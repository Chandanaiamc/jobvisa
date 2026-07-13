<?php

declare(strict_types=1);

/**
 * API routes — Sprint 4.5 Enterprise API Platform.
 * Group prefix: /api  → paths below become /api/v1/...
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

// Public v1
$router->group('api.v1.public', static function ($router): void {
    $router->gets([
        '/v1/health' => 'Api\\V1\\HealthController@index',
        '/v1/jobs' => 'Api\\V1\\JobsController@index',
        '/v1/jobs/{job}' => 'Api\\V1\\JobsController@show',
        '/v1/docs/openapi' => 'Api\\V1\\DocsController@openapi',
        '/v1/portal' => 'Api\\V1\\PortalController@index',
    ]);
}, ['middleware' => []]);

// Authenticated v1 (bearer token; no CSRF)
$router->group('api.v1.auth', static function ($router): void {
    $router->gets([
        '/v1/me' => 'Api\\V1\\MeController@show',
        '/v1/resumes' => 'Api\\V1\\ResumesController@index',
        '/v1/resumes/{resume}' => 'Api\\V1\\ResumesController@show',
        '/v1/resumes/{resume}/intelligence' => 'Api\\V1\\ResumesController@intelligence',
        '/v1/jobs/{job}/match' => 'Api\\V1\\JobMatchController@show',
        '/v1/tokens' => 'Api\\V1\\TokensController@index',
    ]);
    $router->post('/v1/tokens', 'Api\\V1\\TokensController@store');
    // DELETE via POST revoke for router limitation — also register as GET destroy pattern
}, ['middleware' => ['api.auth']]);

$router->group('api.v1.tokens.destroy', static function ($router): void {
    $router->post('/v1/tokens/{token}/revoke', 'Api\\V1\\TokensController@destroy');
}, ['middleware' => ['api.auth']]);

// Employer v1
$router->group('api.v1.employer', static function ($router): void {
    $router->gets([
        '/v1/employer/jobs' => 'Api\\V1\\EmployerJobsController@index',
        '/v1/employer/jobs/{job}/applicants' => 'Api\\V1\\EmployerJobsController@applicants',
        '/v1/employer/jobs/{job}/ranking' => 'Api\\V1\\EmployerJobsController@ranking',
    ]);
}, ['middleware' => ['api.auth', 'api.employer']]);
