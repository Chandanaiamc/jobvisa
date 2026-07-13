<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

/**
 * Sends enterprise security headers on every response that reaches PHP.
 * CSP is config-driven (Sprint 4.7) with backward-compatible defaults.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!(bool) config('production.security_headers', true)) {
            return $next();
        }

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: ' . (string) config('production.frame_options', 'SAMEORIGIN'));
            header('Referrer-Policy: ' . (string) config('production.referrer_policy', 'strict-origin-when-cross-origin'));
            header('Permissions-Policy: ' . (string) config('production.permissions_policy', 'geolocation=(), microphone=(), camera=()'));
            header('X-XSS-Protection: 0');
            header('Cross-Origin-Opener-Policy: same-origin');

            if ((bool) config('security.csp_enabled', true)) {
                $csp = trim((string) config('security.csp_policy', ''));
                if ($csp !== '') {
                    header('Content-Security-Policy: ' . $csp);
                }
            }

            if ((bool) config('production.hsts_enabled', false) && $this->isHttps()) {
                $maxAge = (int) config('production.hsts_max_age', 31536000);
                header('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
            }
        }

        return $next();
    }

    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $trusted = config('production.trusted_proxies', []);
        $trusted = is_array($trusted) ? $trusted : [];
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($trusted !== [] && ($remote === '' || !in_array($remote, $trusted, true))) {
            return false;
        }

        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return $proto === 'https';
    }
}
