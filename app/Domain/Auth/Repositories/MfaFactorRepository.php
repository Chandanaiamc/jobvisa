<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Repositories;

use App\Core\Database;
use PDO;

/**
 * MFA-ready factor persistence (TOTP/WebAuthn scaffolding — verification deferred).
 */
final class MfaFactorRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM auth_mfa_factors LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $rows = Database::query(
            'SELECT `id`, `type`, `label`, `enabled_at`, `verified_at`, `revoked_at`, `created_at`
             FROM `auth_mfa_factors`
             WHERE `user_id` = :uid AND `revoked_at` IS NULL
             ORDER BY `id` DESC LIMIT 50',
            ['uid' => $userId]
        )->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function registerPlaceholder(int $userId, string $type, string $label): int
    {
        Database::query(
            'INSERT INTO `auth_mfa_factors`
                (`user_id`, `type`, `label`, `secret_hash`, `created_at`, `updated_at`)
             VALUES
                (:uid, :type, :label, NULL, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'uid' => $userId,
                'type' => mb_substr($type, 0, 32),
                'label' => mb_substr($label, 0, 120),
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public function revoke(int $factorId, int $userId): bool
    {
        $stmt = Database::query(
            'UPDATE `auth_mfa_factors`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['id' => $factorId, 'uid' => $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public function hasEnabledFactor(int $userId): bool
    {
        $row = Database::query(
            'SELECT `id` FROM `auth_mfa_factors`
             WHERE `user_id` = :uid AND `enabled_at` IS NOT NULL AND `revoked_at` IS NULL
             LIMIT 1',
            ['uid' => $userId]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row);
    }
}
