<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Frontend\Auth;

/**
 * httpOnly cookies for short-lived access + refresh (browser never sees plaintext in JS).
 */
final class ApiAuthTokenCookie
{
    public function accessCookieName(): string
    {
        return (string) config('frontend.api_auth.access_cookie', 'jobvisa_api_access');
    }

    public function refreshCookieName(): string
    {
        return (string) config('frontend.api_auth.refresh_cookie', 'jobvisa_api_refresh');
    }

    public function queueAccess(string $plainToken, string $expiresAt): void
    {
        $this->queue($this->accessCookieName(), $plainToken, $expiresAt);
    }

    public function queueRefresh(string $plainToken, string $expiresAt): void
    {
        $this->queue($this->refreshCookieName(), $plainToken, $expiresAt);
    }

    /**
     * @param  array<string, mixed>  $bundle  login/refresh response with token + expiry keys
     */
    public function queueFromBundle(array $bundle): void
    {
        $access = (string) ($bundle['access_token'] ?? '');
        $refresh = (string) ($bundle['refresh_token'] ?? '');
        if ($access !== '') {
            $this->queueAccess($access, (string) ($bundle['access_expires_at'] ?? ''));
        }
        if ($refresh !== '') {
            $this->queueRefresh($refresh, (string) ($bundle['refresh_expires_at'] ?? ''));
        }
    }

    public function clear(): void
    {
        $this->expire($this->accessCookieName());
        $this->expire($this->refreshCookieName());
    }

    public function access(): ?string
    {
        return $this->read($this->accessCookieName());
    }

    public function refresh(): ?string
    {
        return $this->read($this->refreshCookieName());
    }

    private function read(string $name): ?string
    {
        $raw = $_COOKIE[$name] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        return $raw;
    }

    private function queue(string $name, string $value, string $expiresAt): void
    {
        if ($value === '') {
            return;
        }

        $expires = $this->expiresTimestamp($expiresAt);
        $opts = $this->cookieOptions($expires);
        setcookie($name, $value, $opts);
        $_COOKIE[$name] = $value;
    }

    private function expire(string $name): void
    {
        $opts = $this->cookieOptions(time() - 3600);
        setcookie($name, '', $opts);
        unset($_COOKIE[$name]);
    }

    /**
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    private function cookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => (bool) config('session.secure', false),
            'httponly' => true,
            'samesite' => (string) config('session.same_site', 'Lax'),
        ];
    }

    private function expiresTimestamp(string $expiresAt): int
    {
        if ($expiresAt === '') {
            return time() + max(60, (int) config('auth_lifecycle.access_ttl_seconds', 3600));
        }

        $ts = strtotime($expiresAt . ' UTC');
        if ($ts === false) {
            $ts = strtotime($expiresAt);
        }

        return $ts !== false ? $ts : time() + 3600;
    }
}
