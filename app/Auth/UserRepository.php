<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use App\Core\Database;

/**
 * User lookups and writes for authentication workflows.
 */
final class UserRepository
{
    /**
     * Find an active (non-soft-deleted) user by email.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $sql = 'SELECT `id`, `email`, `password_hash`, `full_name`, `role`, `role_id`, `status`,
                       `remember_token`, `email_verified_at`, `deleted_at`
                FROM `users`
                WHERE `email` = ?
                  AND (`deleted_at` IS NULL)
                  AND (`status` IS NULL OR `status` <> ?)
                LIMIT 1';

        $row = Database::query($sql, [$email, 'deleted'])->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Find an active user by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveById(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $sql = 'SELECT `id`, `email`, `password_hash`, `full_name`, `role`, `role_id`, `status`,
                       `remember_token`, `email_verified_at`, `deleted_at`
                FROM `users`
                WHERE `id` = ?
                  AND (`deleted_at` IS NULL)
                  AND (`status` IS NULL OR `status` <> ?)
                LIMIT 1';

        $row = Database::query($sql, [$userId, 'deleted'])->fetch();

        return $row === false ? null : $row;
    }

    public function emailExists(string $email): bool
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        $id = Database::query(
            'SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1',
            [$email]
        )->fetchColumn();

        return $id !== false;
    }

    public function findRoleIdBySlug(string $slug): ?int
    {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return null;
        }

        $id = Database::query(
            'SELECT `id` FROM `roles` WHERE `slug` = ? LIMIT 1',
            [$slug]
        )->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function findRoleSlugById(?int $roleId): ?string
    {
        if ($roleId === null || $roleId < 1) {
            return null;
        }

        $slug = Database::query(
            'SELECT `slug` FROM `roles` WHERE `id` = ? LIMIT 1',
            [$roleId]
        )->fetchColumn();

        return $slug === false ? null : (string) $slug;
    }

    /**
     * @param  array{
     *   email: string,
     *   password_hash: string,
     *   full_name: string,
     *   phone?: ?string,
     *   role: string,
     *   role_id?: ?int,
     *   status?: string
     * }  $data
     */
    public function create(array $data): int
    {
        Database::query(
            'INSERT INTO `users`
                (`email`, `password_hash`, `full_name`, `phone`, `role`, `role_id`, `status`,
                 `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                strtolower(trim($data['email'])),
                $data['password_hash'],
                trim($data['full_name']),
                $data['phone'] ?? null,
                $data['role'],
                $data['role_id'] ?? null,
                $data['status'] ?? 'pending',
            ]
        );

        return (int) Database::connection()->lastInsertId();
    }

    public function markEmailVerified(int $userId): void
    {
        Database::query(
            'UPDATE `users`
             SET `email_verified_at` = CURRENT_TIMESTAMP(3),
                 `status` = CASE WHEN `status` = ? THEN ? ELSE `status` END,
                 `updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = ?',
            ['pending', 'active', $userId]
        );
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        Database::query(
            'UPDATE `users` SET `password_hash` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [$hash, $userId]
        );
    }

    public function updateRememberToken(int $userId, ?string $tokenHash): void
    {
        Database::query(
            'UPDATE `users` SET `remember_token` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [$tokenHash, $userId]
        );
    }

    public function touchLastLogin(int $userId): void
    {
        Database::query(
            'UPDATE `users` SET `last_login_at` = CURRENT_TIMESTAMP, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [$userId]
        );
    }
}
