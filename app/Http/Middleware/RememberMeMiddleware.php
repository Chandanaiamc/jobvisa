<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\RememberMeCookie;

/**
 * Attempts remember-me restoration for the web stack (non-blocking).
 */
final class RememberMeMiddleware implements MiddlewareInterface
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

        return $next();
    }
}
