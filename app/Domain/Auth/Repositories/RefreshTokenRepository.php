<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Repositories;

use App\Core\Database;
use PDO;

final class RefreshTokenRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM auth_refresh_tokens LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO `auth_refresh_tokens`
                (`user_id`, `device_id`, `family_id`, `token_hash`, `token_prefix`, `access_token_id`,
                 `expires_at`, `created_at`, `updated_at`)
             VALUES
                (:uid, :device, :family, :hash, :prefix, :access,
                 :expires, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'uid' => (int) $data['user_id'],
                'device' => $data['device_id'] ?? null,
                'family' => (string) $data['family_id'],
                'hash' => (string) $data['token_hash'],
                'prefix' => (string) $data['token_prefix'],
                'access' => $data['access_token_id'] ?? null,
                'expires' => (string) $data['expires_at'],
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByHash(string $hash): ?array
    {
        $row = Database::query(
            'SELECT * FROM `auth_refresh_tokens` WHERE `token_hash` = :h LIMIT 1',
            ['h' => $hash]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Lock the refresh row (and implicitly serialize family rotation) within an open transaction.
     *
     * @return array<string, mixed>|null
     */
    public function findByHashForUpdate(string $hash): ?array
    {
        $row = Database::query(
            'SELECT * FROM `auth_refresh_tokens` WHERE `token_hash` = :h LIMIT 1 FOR UPDATE',
            ['h' => $hash]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Access token IDs linked to refresh rows for a device (before revoke).
     *
     * @return list<int>
     */
    public function listAccessTokenIdsForDevice(int $deviceId, int $userId): array
    {
        $rows = Database::query(
            'SELECT DISTINCT `access_token_id` FROM `auth_refresh_tokens`
             WHERE `device_id` = :did AND `user_id` = :uid
               AND `access_token_id` IS NOT NULL',
            ['did' => $deviceId, 'uid' => $userId]
        )->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['access_token_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function markRotated(int $id, int $replacedById): void
    {
        Database::query(
            'UPDATE `auth_refresh_tokens`
             SET `rotated_at` = CURRENT_TIMESTAMP(3),
                 `revoked_at` = CURRENT_TIMESTAMP(3),
                 `replaced_by_id` = :rep,
                 `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            ['id' => $id, 'rep' => $replacedById]
        );
    }

    public function revokeFamily(string $familyId): int
    {
        $stmt = Database::query(
            'UPDATE `auth_refresh_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `family_id` = :family AND `revoked_at` IS NULL',
            ['family' => $familyId]
        );

        return (int) $stmt->rowCount();
    }

    public function revokeAllForUser(int $userId): int
    {
        $stmt = Database::query(
            'UPDATE `auth_refresh_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `user_id` = :uid AND `revoked_at` IS NULL',
            ['uid' => $userId]
        );

        return (int) $stmt->rowCount();
    }

    public function revokeForDevice(int $deviceId, int $userId): int
    {
        $stmt = Database::query(
            'UPDATE `auth_refresh_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `device_id` = :did AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['did' => $deviceId, 'uid' => $userId]
        );

        return (int) $stmt->rowCount();
    }

    public function touch(int $id, string $ip): void
    {
        Database::query(
            'UPDATE `auth_refresh_tokens`
             SET `last_used_at` = CURRENT_TIMESTAMP(3),
                 `last_used_ip` = :ip,
                 `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            ['id' => $id, 'ip' => mb_substr($ip, 0, 45)]
        );
    }
}
