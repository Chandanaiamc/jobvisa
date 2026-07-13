<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiResponse;

/**
 * JSON role gate for API (deny by default).
 */
final class ApiRoleMiddleware implements MiddlewareInterface
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        private readonly array $roles,
    ) {
    }

    public function handle(callable $next): mixed
    {
        if (!ApiAuth::check()) {
            ApiResponse::error('unauthorized', 'Unauthenticated.', 401);

            return null;
        }

        $role = ApiAuth::role();
        if ($role === '' || !in_array($role, $this->roles, true)) {
            ApiResponse::error('forbidden', 'Insufficient role permissions.', 403);

            return null;
        }

        return $next();
    }
}
