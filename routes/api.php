<?php

declare(strict_types=1);

/**
 * API routes — Sprint 4.5 Enterprise API Platform (+ v1 hardening).
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
        '/v1/auth/status' => 'Api\\V1\\AuthLifecycleController@status',
    ]);
    $router->post('/v1/auth/login', 'Api\\V1\\AuthLifecycleController@login');
    $router->post('/v1/auth/refresh', 'Api\\V1\\AuthLifecycleController@refresh');
    $router->post('/v1/auth/password/forgot', 'Api\\V1\\AuthLifecycleController@forgotPassword');
    $router->post('/v1/auth/password/reset', 'Api\\V1\\AuthLifecycleController@resetPassword');
    $router->post('/v1/auth/email/verify', 'Api\\V1\\AuthLifecycleController@verifyEmail');
    $router->post('/v1/auth/email/resend', 'Api\\V1\\AuthLifecycleController@resendVerification');
}, ['middleware' => []]);

// Authenticated v1 — any role (bearer; no CSRF)
$router->group('api.v1.auth', static function ($router): void {
    $router->gets([
        '/v1/me' => 'Api\\V1\\MeController@show',
        '/v1/tokens' => 'Api\\V1\\TokensController@index',
        '/v1/auth/devices' => 'Api\\V1\\AuthLifecycleController@devices',
        '/v1/auth/mfa' => 'Api\\V1\\AuthLifecycleController@mfaStatus',
    ]);
    $router->post('/v1/tokens', 'Api\\V1\\TokensController@store');
    $router->post('/v1/tokens/revoke-all', 'Api\\V1\\TokensController@revokeAll');
    $router->post('/v1/auth/logout', 'Api\\V1\\AuthLifecycleController@logout');
    $router->post('/v1/auth/logout-everywhere', 'Api\\V1\\AuthLifecycleController@logoutEverywhere');
    $router->post('/v1/auth/devices/{device}/revoke', 'Api\\V1\\AuthLifecycleController@revokeDevice');
    $router->post('/v1/auth/mfa/register', 'Api\\V1\\AuthLifecycleController@mfaRegister');
}, ['middleware' => ['api.auth']]);

$router->group('api.v1.tokens.destroy', static function ($router): void {
    $router->post('/v1/tokens/{token}/revoke', 'Api\\V1\\TokensController@destroy');
}, ['middleware' => ['api.auth']]);

// Job seeker scoped APIs
$router->group('api.v1.jobseeker', static function ($router): void {
    $router->gets([
        '/v1/resumes' => 'Api\\V1\\ResumesController@index',
        '/v1/resumes/{resume}' => 'Api\\V1\\ResumesController@show',
        '/v1/resumes/{resume}/intelligence' => 'Api\\V1\\ResumesController@intelligence',
        '/v1/jobs/{job}/match' => 'Api\\V1\\JobMatchController@show',
        '/v1/applications' => 'Api\\V1\\ApplicationsController@index',
        '/v1/applications/{application}' => 'Api\\V1\\ApplicationsController@show',
        '/v1/interviews' => 'Api\\V1\\InterviewsController@index',
        '/v1/interviews/{interview}' => 'Api\\V1\\InterviewsController@show',
        '/v1/offers' => 'Api\\V1\\OffersController@index',
        '/v1/offers/{offer}' => 'Api\\V1\\OffersController@show',
    ]);
    $router->post('/v1/jobs/{job}/applications', 'Api\\V1\\ApplicationsController@store');
    $router->post('/v1/applications/{application}/withdraw', 'Api\\V1\\ApplicationsController@withdraw');
    $router->post('/v1/interviews/{interview}/confirm', 'Api\\V1\\InterviewsController@confirm');
    $router->post('/v1/interviews/{interview}/decline', 'Api\\V1\\InterviewsController@decline');
    $router->post('/v1/offers/{offer}/accept', 'Api\\V1\\OffersController@accept');
    $router->post('/v1/offers/{offer}/decline', 'Api\\V1\\OffersController@decline');
}, ['middleware' => ['api.auth', 'api.jobseeker']]);

// Employer v1
$router->group('api.v1.employer', static function ($router): void {
    $router->gets([
        '/v1/employer/jobs' => 'Api\\V1\\EmployerJobsController@index',
        '/v1/employer/jobs/{job}' => 'Api\\V1\\EmployerJobsController@show',
        '/v1/employer/jobs/{job}/applicants' => 'Api\\V1\\EmployerJobsController@applicants',
        '/v1/employer/jobs/{job}/ranking' => 'Api\\V1\\EmployerJobsController@ranking',
        '/v1/employer/applications/{application}' => 'Api\\V1\\EmployerApplicationsController@show',
        '/v1/employer/interviews' => 'Api\\V1\\EmployerInterviewsController@index',
        '/v1/employer/interviews/{interview}' => 'Api\\V1\\EmployerInterviewsController@show',
        '/v1/employer/offers' => 'Api\\V1\\EmployerOffersController@index',
        '/v1/employer/offers/{offer}' => 'Api\\V1\\EmployerOffersController@show',
    ]);
    $router->post('/v1/employer/jobs', 'Api\\V1\\EmployerJobsController@store');
    $router->post('/v1/employer/jobs/{job}', 'Api\\V1\\EmployerJobsController@update');
    $router->post('/v1/employer/jobs/{job}/publish', 'Api\\V1\\EmployerJobsController@publish');
    $router->post('/v1/employer/jobs/{job}/unpublish', 'Api\\V1\\EmployerJobsController@unpublish');
    $router->post('/v1/employer/jobs/{job}/archive', 'Api\\V1\\EmployerJobsController@archive');
    $router->post('/v1/employer/applications/{application}/status', 'Api\\V1\\EmployerApplicationsController@updateStatus');
    $router->post('/v1/employer/applications/{application}/interviews', 'Api\\V1\\EmployerInterviewsController@store');
    $router->post('/v1/employer/interviews/{interview}/reschedule', 'Api\\V1\\EmployerInterviewsController@reschedule');
    $router->post('/v1/employer/interviews/{interview}/cancel', 'Api\\V1\\EmployerInterviewsController@cancel');
    $router->post('/v1/employer/interviews/{interview}/complete', 'Api\\V1\\EmployerInterviewsController@complete');
    $router->post('/v1/employer/applications/{application}/offers', 'Api\\V1\\EmployerOffersController@store');
    $router->post('/v1/employer/offers/{offer}/send', 'Api\\V1\\EmployerOffersController@send');
    $router->post('/v1/employer/offers/{offer}/withdraw', 'Api\\V1\\EmployerOffersController@withdraw');
    $router->post('/v1/employer/offers/{offer}/expire', 'Api\\V1\\EmployerOffersController@expire');
}, ['middleware' => ['api.auth', 'api.employer']]);
