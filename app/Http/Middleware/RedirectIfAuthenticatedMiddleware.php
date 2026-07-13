<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\RememberMeCookie;

/**
 * Redirects authenticated users away from guest HTML pages (login/register).
 */
final class RedirectIfAuthenticatedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly RememberMeCookie $rememberCookie,
        private readonly DashboardRedirector $redirector
    ) {
    }

    public function handle(callable $next): mixed
    {
        if (!$this->auth->check()) {
            $this->rememberCookie->attemptRestore($this->auth);
        }

        if ($this->auth->check()) {
            $target = $this->redirector->forUser($this->auth->user());
            redirect(app_url($target['path']));
        }

        return $next();
    }
}
