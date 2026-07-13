<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\RememberMeCookie;

/**
 * Blocks authenticated users from guest-only endpoints.
 */
final class GuestMiddleware implements MiddlewareInterface
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

        if ($this->auth->check()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Already authenticated.',
            ], JSON_UNESCAPED_UNICODE);

            return null;
        }

        return $next();
    }
}
