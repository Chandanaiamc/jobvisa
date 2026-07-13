<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Security\SessionManager;

/**
 * Requires authentication for HTML pages; redirects guests to /login.
 */
final class RequireAuthWebMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly RememberMeCookie $rememberCookie
    ) {
    }

    public function handle(callable $next): mixed
    {
        if (!$this->auth->check()) {
            $this->rememberCookie->attemptRestore($this->auth);
        }

        if (!$this->auth->check()) {
            SessionManager::flash('error', 'Please sign in to continue.');
            redirect(app_url('/login'));
        }

        return $next();
    }
}
