<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Security\SessionManager;

/**
 * Requires a verified email address for sensitive authenticated areas.
 */
final class VerifiedEmailMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthManager $auth
    ) {
    }

    public function handle(callable $next): mixed
    {
        $user = $this->auth->user();

        if ($user === null) {
            SessionManager::flash('error', 'Please sign in to continue.');
            redirect(app_url('/login'));
        }

        if (empty($user['email_verified_at'])) {
            SessionManager::flash('error', 'Please verify your email address to continue.');
            redirect(app_url('/email/verify'));
        }

        return $next();
    }
}
