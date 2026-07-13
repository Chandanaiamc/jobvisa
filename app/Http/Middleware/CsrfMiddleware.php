<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;
use JobVisa\App\Security\Csrf;

/**
 * Validates CSRF tokens on unsafe HTTP methods.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const UNSAFE = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(callable $next): mixed
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (!in_array($method, self::UNSAFE, true)) {
            return $next();
        }

        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (is_array($token)) {
            $token = null;
        }

        if (!Csrf::validate(is_string($token) ? $token : null)) {
            try {
                if (function_exists('container')) {
                    container(SecurityAuditLogger::class)->log('csrf_rejected', null, 'http', null, [], [
                        'method' => $method,
                        'path' => (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'),
                    ]);
                }
            } catch (\Throwable) {
            }

            if ($this->wantsJson()) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'CSRF token mismatch.',
                    'errors' => ['_token' => ['CSRF token mismatch.']],
                ], JSON_UNESCAPED_UNICODE);

                return null;
            }

            \JobVisa\App\Security\SessionManager::flash('error', 'Your session expired. Please try again.');
            $referer = $_SERVER['HTTP_REFERER'] ?? app_url('/login');
            redirect(is_string($referer) && $referer !== '' ? $referer : app_url('/login'));
        }

        return $next();
    }

    private function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');

        return str_contains($accept, 'application/json')
            || str_contains(strtolower($contentType), 'application/json');
    }
}
