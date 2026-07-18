<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Repositories;

use App\Core\Database;
use PDO;

/**
 * Short-lived session access token persistence (hashed secrets only).
 */
final class AccessTokenRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM auth_access_tokens LIMIT 1');

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
            'INSERT INTO `auth_access_tokens`
                (`user_id`, `device_id`, `token_hash`, `token_prefix`, `name`, `expires_at`,
                 `created_at`, `updated_at`)
             VALUES
                (:uid, :did, :hash, :prefix, :name, :expires,
                 CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'uid' => (int) $data['user_id'],
                'did' => $data['device_id'] ?? null,
                'hash' => (string) $data['token_hash'],
                'prefix' => (string) $data['token_prefix'],
                'name' => (string) ($data['name'] ?? 'access'),
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
            'SELECT * FROM `auth_access_tokens` WHERE `token_hash` = :h LIMIT 1',
            ['h' => $hash]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function revoke(int $id, int $userId): bool
    {
        $stmt = Database::query(
            'UPDATE `auth_access_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['id' => $id, 'uid' => $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public function revokeAllForUser(int $userId): int
    {
        $stmt = Database::query(
            'UPDATE `auth_access_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `user_id` = :uid AND `revoked_at` IS NULL',
            ['uid' => $userId]
        );

        return (int) $stmt->rowCount();
    }

    public function revokeForDevice(int $deviceId, int $userId): int
    {
        $stmt = Database::query(
            'UPDATE `auth_access_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `device_id` = :did AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['did' => $deviceId, 'uid' => $userId]
        );

        return (int) $stmt->rowCount();
    }

    /**
     * @param  list<int>  $ids
     */
    public function revokeIds(array $ids, int $userId): int
    {
        $n = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && $this->revoke($id, $userId)) {
                $n++;
            }
        }

        return $n;
    }

    public function touchUsage(int $id, string $ip, string $userAgent): void
    {
        Database::query(
            'UPDATE `auth_access_tokens`
             SET `last_used_at` = CURRENT_TIMESTAMP(3),
                 `last_used_ip` = :ip,
                 `last_used_user_agent` = :ua,
                 `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            [
                'id' => $id,
                'ip' => mb_substr($ip, 0, 45),
                'ua' => mb_substr($userAgent, 0, 512),
            ]
        );
    }
}
