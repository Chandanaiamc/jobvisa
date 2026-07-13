<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

/**
 * HTTP cookie layer for remember-me tokens.
 */
final class RememberMeCookie
{
    public const COOKIE_NAME = 'jobvisa_remember';

    public function __construct(
        private readonly RememberMeService $rememberMe
    ) {
    }

    /**
     * Queue the remember-me cookie (userId|plainToken).
     */
    public function queue(int $userId, string $plainToken, int $days = 30): void
    {
        if ($userId < 1 || $plainToken === '') {
            return;
        }

        $value = $userId . '|' . $plainToken;
        $expires = time() + max(1, $days) * 86400;
        $secure = (bool) config('session.secure', false);
        $sameSite = (string) config('session.same_site', 'Lax');

        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    public function forget(): void
    {
        $secure = (bool) config('session.secure', false);
        $sameSite = (string) config('session.same_site', 'Lax');

        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Restore an authenticated session from the remember-me cookie when valid.
     */
    public function attemptRestore(AuthManager $auth): bool
    {
        if ($auth->check()) {
            return true;
        }

        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (!is_string($raw) || !str_contains($raw, '|')) {
            return false;
        }

        [$userIdRaw, $plain] = explode('|', $raw, 2);
        $userId = (int) $userIdRaw;

        if ($userId < 1 || $plain === '') {
            $this->forget();

            return false;
        }

        if (!$this->rememberMe->validate($userId, $plain)) {
            $this->forget();

            return false;
        }

        $user = $auth->userById($userId);

        if ($user === null) {
            $this->forget();

            return false;
        }

        $auth->loginUser($user);

        // Rotate remember token after successful restore.
        $issued = $this->rememberMe->issue($userId);
        $this->queue($userId, $issued['plain']);

        return true;
    }
}
