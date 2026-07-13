<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use JobVisa\App\Security\SessionManager as HttpSessionManager;

/**
 * Authentication session façade over the HTTP SessionManager.
 *
 * Stores authenticated identity keys and regenerates the session ID on login.
 */
final class SessionManager
{
    private const USER_ID = 'auth.user_id';
    private const ROLE_ID = 'auth.role_id';
    private const LOGIN_AT = 'auth.login_at';

    public function __construct(
        private readonly HttpSessionManager $httpSession
    ) {
    }

    /**
     * Establish an authenticated session (regenerates session ID).
     */
    public function establish(int $userId, ?int $roleId = null): void
    {
        // Ensure HTTP session layer is available (injected for DI graph).
        $_ = $this->httpSession;

        HttpSessionManager::start();
        HttpSessionManager::regenerate(true);
        HttpSessionManager::set(self::USER_ID, $userId);
        HttpSessionManager::set(self::LOGIN_AT, time());

        if ($roleId !== null) {
            HttpSessionManager::set(self::ROLE_ID, $roleId);
        } else {
            HttpSessionManager::remove(self::ROLE_ID);
        }
    }

    /**
     * Clear authentication keys and regenerate the session ID.
     */
    public function clear(): void
    {
        $_ = $this->httpSession;

        HttpSessionManager::start();
        HttpSessionManager::remove(self::USER_ID);
        HttpSessionManager::remove(self::ROLE_ID);
        HttpSessionManager::remove(self::LOGIN_AT);
        HttpSessionManager::regenerate(true);
    }

    public function check(): bool
    {
        return $this->userId() !== null;
    }

    public function userId(): ?int
    {
        HttpSessionManager::start();
        $value = HttpSessionManager::get(self::USER_ID);

        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    public function roleId(): ?int
    {
        HttpSessionManager::start();
        $value = HttpSessionManager::get(self::ROLE_ID);

        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    public function loginAt(): ?int
    {
        HttpSessionManager::start();
        $value = HttpSessionManager::get(self::LOGIN_AT);

        return is_numeric($value) ? (int) $value : null;
    }
}
