<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Repositories;

use App\Core\Database;
use PDO;

final class DeviceSessionRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM auth_devices LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data): int
    {
        $existing = Database::query(
            'SELECT `id` FROM `auth_devices`
             WHERE `user_id` = :uid AND `fingerprint_hash` = :fp LIMIT 1',
            ['uid' => (int) $data['user_id'], 'fp' => (string) $data['fingerprint_hash']]
        )->fetch(PDO::FETCH_ASSOC);

        if (is_array($existing)) {
            $id = (int) $existing['id'];
            Database::query(
                'UPDATE `auth_devices` SET
                    `name` = :name,
                    `platform` = :platform,
                    `last_ip` = :ip,
                    `last_user_agent` = :ua,
                    `last_seen_at` = CURRENT_TIMESTAMP(3),
                    `revoked_at` = NULL,
                    `updated_at` = CURRENT_TIMESTAMP(3)
                 WHERE `id` = :id',
                [
                    'id' => $id,
                    'name' => (string) $data['name'],
                    'platform' => $data['platform'] ?? null,
                    'ip' => $data['last_ip'] ?? null,
                    'ua' => $data['last_user_agent'] ?? null,
                ]
            );

            return $id;
        }

        Database::query(
            'INSERT INTO `auth_devices`
                (`user_id`, `fingerprint_hash`, `name`, `platform`, `last_ip`, `last_user_agent`,
                 `last_seen_at`, `created_at`, `updated_at`)
             VALUES
                (:uid, :fp, :name, :platform, :ip, :ua, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'uid' => (int) $data['user_id'],
                'fp' => (string) $data['fingerprint_hash'],
                'name' => (string) $data['name'],
                'platform' => $data['platform'] ?? null,
                'ip' => $data['last_ip'] ?? null,
                'ua' => $data['last_user_agent'] ?? null,
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $rows = Database::query(
            'SELECT `id`, `name`, `platform`, `last_ip`, `last_user_agent`, `last_seen_at`,
                    `revoked_at`, `created_at`
             FROM `auth_devices`
             WHERE `user_id` = :uid
             ORDER BY `last_seen_at` DESC, `id` DESC
             LIMIT 100',
            ['uid' => $userId]
        )->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function revoke(int $deviceId, int $userId): bool
    {
        $stmt = Database::query(
            'UPDATE `auth_devices`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['id' => $deviceId, 'uid' => $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public function revokeAllForUser(int $userId, ?int $exceptDeviceId = null): int
    {
        if ($exceptDeviceId !== null) {
            $stmt = Database::query(
                'UPDATE `auth_devices`
                 SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
                 WHERE `user_id` = :uid AND `revoked_at` IS NULL AND `id` <> :except',
                ['uid' => $userId, 'except' => $exceptDeviceId]
            );
        } else {
            $stmt = Database::query(
                'UPDATE `auth_devices`
                 SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
                 WHERE `user_id` = :uid AND `revoked_at` IS NULL',
                ['uid' => $userId]
            );
        }

        return (int) $stmt->rowCount();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $deviceId, int $userId): ?array
    {
        $row = Database::query(
            'SELECT * FROM `auth_devices` WHERE `id` = :id AND `user_id` = :uid LIMIT 1',
            ['id' => $deviceId, 'uid' => $userId]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function touch(int $deviceId, ?string $ip, ?string $ua): void
    {
        Database::query(
            'UPDATE `auth_devices`
             SET `last_seen_at` = CURRENT_TIMESTAMP(3),
                 `last_ip` = :ip,
                 `last_user_agent` = :ua,
                 `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            [
                'id' => $deviceId,
                'ip' => $ip !== null ? mb_substr($ip, 0, 45) : null,
                'ua' => $ua !== null ? mb_substr($ua, 0, 512) : null,
            ]
        );
    }
}
