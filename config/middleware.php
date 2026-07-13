<?php

declare(strict_types=1);

/**
 * Middleware alias map for the HTTP pipeline.
 */

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Http\Middleware\ApiAuthenticateMiddleware;
use JobVisa\App\Http\Middleware\ApiMiddleware;
use JobVisa\App\Http\Middleware\ApiRoleMiddleware;
use JobVisa\App\Http\Middleware\AuthMiddleware;
use JobVisa\App\Http\Middleware\AuthenticateMiddleware;
use JobVisa\App\Http\Middleware\CsrfMiddleware;
use JobVisa\App\Http\Middleware\ForceHttpsMiddleware;
use JobVisa\App\Http\Middleware\GuestMiddleware;
use JobVisa\App\Http\Middleware\MaintenanceModeMiddleware;
use JobVisa\App\Http\Middleware\RedirectIfAuthenticatedMiddleware;
use JobVisa\App\Http\Middleware\RememberMeMiddleware;
use JobVisa\App\Http\Middleware\ObservabilityMiddleware;
use JobVisa\App\Http\Middleware\RequestTimingMiddleware;
use JobVisa\App\Http\Middleware\RequireAuthWebMiddleware;
use JobVisa\App\Http\Middleware\RoleMiddleware;
use JobVisa\App\Http\Middleware\SecurityHeadersMiddleware;
use JobVisa\App\Http\Middleware\StartSessionMiddleware;
use JobVisa\App\Http\Middleware\VerifiedEmailMiddleware;

return [
    'aliases' => [
        'web' => StartSessionMiddleware::class,
        'csrf' => CsrfMiddleware::class,
        'security.headers' => SecurityHeadersMiddleware::class,
        'maintenance' => MaintenanceModeMiddleware::class,
        'https' => ForceHttpsMiddleware::class,
        'api' => ApiMiddleware::class,
        'api.auth' => ApiAuthenticateMiddleware::class,
        'api.employer' => static function (): ApiRoleMiddleware {
            return new ApiRoleMiddleware(['employer']);
        },
        'api.jobseeker' => static function (): ApiRoleMiddleware {
            return new ApiRoleMiddleware(['seeker']);
        },
        'api.admin' => static function (): ApiRoleMiddleware {
            return new ApiRoleMiddleware(['admin', 'super_admin', 'staff']);
        },
        'timing' => RequestTimingMiddleware::class,
        'observability' => ObservabilityMiddleware::class,

        // JSON / API auth
        'auth' => AuthenticateMiddleware::class,
        'AuthMiddleware' => AuthMiddleware::class,

        // HTML auth
        'auth.web' => RequireAuthWebMiddleware::class,
        'guest' => GuestMiddleware::class,
        'guest.web' => RedirectIfAuthenticatedMiddleware::class,
        'remember' => RememberMeMiddleware::class,
        'verified' => VerifiedEmailMiddleware::class,

        // Roles (RoleMiddleware)
        'admin' => static function (): RoleMiddleware {
            return new RoleMiddleware(container(AuthManager::class), ['admin', 'super_admin', 'staff']);
        },
        'employer' => static function (): RoleMiddleware {
            return new RoleMiddleware(container(AuthManager::class), ['employer']);
        },
        'jobseeker' => static function (): RoleMiddleware {
            return new RoleMiddleware(container(AuthManager::class), ['seeker']);
        },
    ],
];
