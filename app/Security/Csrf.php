<?php

declare(strict_types=1);

namespace JobVisa\App\Security;

/**
 * Cross-Site Request Forgery (CSRF) protection.
 */
final class Csrf
{
    /**
     * Session key for CSRF token (configurable via security.csrf_token_key).
     */
    private static function sessionKey(): string
    {
        $key = (string) config('security.csrf_token_key', '_csrf_token');

        return $key !== '' ? $key : '_csrf_token';
    }

    /**
     * Get the current CSRF token, generating one if needed.
     */
    public static function token(): string
    {
        SessionManager::start();

        $token = SessionManager::get(self::sessionKey());

        if (!is_string($token) || $token === '') {
            $token = self::rotate();
        }

        return $token;
    }

    /**
     * Generate and store a new CSRF token.
     */
    public static function rotate(): string
    {
        SessionManager::start();
        $token = SecurityHelper::randomToken(40);
        SessionManager::set(self::sessionKey(), $token);

        return $token;
    }

    /**
     * Validate a submitted token using timing-safe comparison.
     */
    public static function validate(?string $token): bool
    {
        SessionManager::start();

        $sessionToken = SessionManager::get(self::sessionKey());

        if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Validate then rotate the token (recommended after successful POST).
     */
    public static function validateAndRotate(?string $token): bool
    {
        if (!self::validate($token)) {
            return false;
        }

        self::rotate();

        return true;
    }

    /**
     * HTML hidden input for forms.
     */
    public static function field(): string
    {
        $token = e(self::token());

        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}
