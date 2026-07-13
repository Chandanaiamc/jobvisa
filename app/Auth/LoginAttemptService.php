<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use App\Core\Database;
use JobVisa\App\Security\SecurityHelper;

/**
 * Persists login attempts using the login_attempts table.
 */
final class LoginAttemptService
{
    public function record(?string $email, bool $wasSuccessful): void
    {
        $normalizedEmail = $email !== null ? strtolower(trim($email)) : null;

        if ($normalizedEmail === '') {
            $normalizedEmail = null;
        }

        Database::query(
            'INSERT INTO `login_attempts` (`email`, `ip_address`, `user_agent`, `was_successful`, `attempted_at`)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [
                $normalizedEmail,
                SecurityHelper::clientIp(),
                SecurityHelper::userAgent(500),
                $wasSuccessful ? 1 : 0,
            ]
        );
    }

    /**
     * Count recent failed attempts for an email within a window.
     */
    public function countRecentFailuresByEmail(string $email, int $withinMinutes = 15): int
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return 0;
        }

        $withinMinutes = max(1, $withinMinutes);

        $sql = 'SELECT COUNT(*) FROM `login_attempts`
                WHERE `email` = ?
                  AND `was_successful` = 0
                  AND `attempted_at` >= (CURRENT_TIMESTAMP - INTERVAL ' . (int) $withinMinutes . ' MINUTE)';

        return (int) Database::query($sql, [$email])->fetchColumn();
    }

    /**
     * Count recent failed attempts for the current client IP.
     */
    public function countRecentFailuresByIp(int $withinMinutes = 15): int
    {
        $withinMinutes = max(1, $withinMinutes);
        $ip = SecurityHelper::clientIp();

        $sql = 'SELECT COUNT(*) FROM `login_attempts`
                WHERE `ip_address` = ?
                  AND `was_successful` = 0
                  AND `attempted_at` >= (CURRENT_TIMESTAMP - INTERVAL ' . (int) $withinMinutes . ' MINUTE)';

        return (int) Database::query($sql, [$ip])->fetchColumn();
    }
}
