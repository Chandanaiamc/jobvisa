<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

/**
 * Redirects HTTP → HTTPS when FORCE_HTTPS is enabled.
 */
final class ForceHttpsMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!(bool) config('production.force_https', false)) {
            return $next();
        }

        if ($this->isHttps()) {
            return $next();
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if ($host === '') {
            return $next();
        }

        http_response_code(301);
        header('Location: https://' . $host . $uri);
        header('Cache-Control: no-store');

        return null;
    }

    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $trusted = config('production.trusted_proxies', []);
        $trusted = is_array($trusted) ? $trusted : [];
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $trustForwarded = $trusted === [] || ($remote !== '' && in_array($remote, $trusted, true));

        if ($trustForwarded) {
            $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            if ($proto === 'https') {
                return true;
            }
        }

        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }
}
