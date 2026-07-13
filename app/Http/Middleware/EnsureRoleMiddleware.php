<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use App\Core\View;

/**
 * Ensures the authenticated user has one of the allowed role slugs.
 */
class EnsureRoleMiddleware implements MiddlewareInterface
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        private readonly AuthManager $auth,
        private readonly array $roles
    ) {
    }

    public function handle(callable $next): mixed
    {
        $user = $this->auth->user();
        $role = is_array($user) && isset($user['role']) ? (string) $user['role'] : '';

        if ($role === '' || !in_array($role, $this->roles, true)) {
            http_response_code(403);
            (new View())->display('errors/403', [
                'title' => 'Forbidden',
                'message' => 'You do not have access to this area.',
            ]);

            return null;
        }

        return $next();
    }
}
