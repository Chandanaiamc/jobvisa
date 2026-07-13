<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Auth;

use App\Core\Database;
use PDO;

/**
 * Personal access token persistence (hashed secrets only).
 */
final class PersonalAccessTokenRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM api_personal_access_tokens LIMIT 1');

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
            'INSERT INTO `api_personal_access_tokens`
                (`user_id`, `name`, `token_hash`, `token_prefix`, `abilities`, `expires_at`, `created_at`, `updated_at`)
             VALUES
                (:user_id, :name, :token_hash, :token_prefix, :abilities, :expires_at, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'user_id' => (int) $data['user_id'],
                'name' => (string) $data['name'],
                'token_hash' => (string) $data['token_hash'],
                'token_prefix' => (string) $data['token_prefix'],
                'abilities' => $data['abilities'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
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
            'SELECT * FROM `api_personal_access_tokens` WHERE `token_hash` = :h LIMIT 1',
            ['h' => $hash]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdForUser(int $id, int $userId): ?array
    {
        $row = Database::query(
            'SELECT * FROM `api_personal_access_tokens`
             WHERE `id` = :id AND `user_id` = :uid LIMIT 1',
            ['id' => $id, 'uid' => $userId]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $rows = Database::query(
            'SELECT `id`, `name`, `token_prefix`, `abilities`, `last_used_at`, `last_used_ip`,
                    `expires_at`, `revoked_at`, `created_at`
             FROM `api_personal_access_tokens`
             WHERE `user_id` = :uid
             ORDER BY `id` DESC
             LIMIT 100',
            ['uid' => $userId]
        )->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function revoke(int $id, int $userId): bool
    {
        $stmt = Database::query(
            'UPDATE `api_personal_access_tokens`
             SET `revoked_at` = CURRENT_TIMESTAMP(3), `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `revoked_at` IS NULL',
            ['id' => $id, 'uid' => $userId]
        );

        return $stmt->rowCount() > 0;
    }

    public function touchUsage(int $id, string $ip, string $userAgent): void
    {
        Database::query(
            'UPDATE `api_personal_access_tokens`
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
