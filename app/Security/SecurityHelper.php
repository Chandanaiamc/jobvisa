<?php

declare(strict_types=1);

namespace JobVisa\App\Security;

/**
 * General-purpose security helpers (no I/O beyond reading request meta).
 */
final class SecurityHelper
{
    /**
     * Escape a value for safe HTML output.
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Cryptographically secure random hex token.
     *
     * @param  int  $bytes  Number of random bytes (output length = bytes * 2)
     */
    public static function randomToken(int $bytes = 32): string
    {
        $bytes = max(16, $bytes);

        return bin2hex(random_bytes($bytes));
    }

    /**
     * Best-effort client IP.
     * Proxy headers (CF / X-Forwarded-For) are honored only when REMOTE_ADDR
     * is in production.trusted_proxies (or the allow-list is empty for local).
     */
    public static function clientIp(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $trusted = config('production.trusted_proxies', []);
        $trusted = is_array($trusted) ? $trusted : [];
        $trustForwarded = $trusted === [] || ($remote !== '' && in_array($remote, $trusted, true));

        $candidates = [];
        if ($trustForwarded) {
            $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
            $candidates[] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        }
        $candidates[] = $remote !== '' ? $remote : null;

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            // X-Forwarded-For may contain a list; take the first hop.
            if (str_contains($candidate, ',')) {
                $candidate = trim(explode(',', $candidate, 2)[0]);
            }

            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Request User-Agent string (truncated).
     */
    public static function userAgent(int $maxLength = 500): string
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($ua === '') {
            return '';
        }

        if (mb_strlen($ua) > $maxLength) {
            return mb_substr($ua, 0, $maxLength);
        }

        return $ua;
    }
}
