<?php

declare(strict_types=1);

namespace JobVisa\App\Security;

/**
 * Secure PHP session manager.
 */
final class SessionManager
{
    private static bool $started = false;

    /**
     * Start the session with secure cookie parameters.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (headers_sent()) {
            return;
        }

        $name = (string) config('session.name', 'jobvisa_session');
        $lifetimeMinutes = (int) config('session.lifetime', 120);
        $lifetimeSeconds = max(0, $lifetimeMinutes * 60);
        $secure = (bool) config('session.secure', false);
        $httpOnly = (bool) config('session.http_only', true);
        $sameSite = (string) config('session.same_site', 'Lax');
        $path = (string) config('session.path', '/');

        if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            $sameSite = 'Lax';
        }

        session_name($name);

        session_set_cookie_params([
            'lifetime' => $lifetimeSeconds,
            'path' => $path,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', $httpOnly ? '1' : '0');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string) max($lifetimeSeconds, 1440));

        session_start();
        self::$started = true;
    }

    /**
     * Regenerate the session ID (e.g. after privilege changes / login).
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();

        return array_key_exists($key, $_SESSION);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Store a one-time flash message.
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Read and clear a flash message.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();

        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        if ($_SESSION['_flash'] === []) {
            unset($_SESSION['_flash']);
        }

        return $value;
    }

    /**
     * Destroy the session and cookie.
     */
    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?: '',
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::$started = false;
    }
}
