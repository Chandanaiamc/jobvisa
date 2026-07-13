<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Security\SessionManager;

/**
 * Ensures the HTTP session is started (web stack).
 */
final class StartSessionMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        SessionManager::start();

        return $next();
    }
}
