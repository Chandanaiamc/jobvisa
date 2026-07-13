<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\User\Entities\User;
use JobVisa\App\Domain\User\Repositories\UserRepositoryInterface as DomainUserRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserRepositoryInterface as InfrastructureUserRepositoryInterface;

/**
 * Enterprise user repository (domain + infrastructure contracts).
 *
 * Distinct from JobVisa\App\Auth\UserRepository — auth behaviour is unchanged.
 */
final class UserRepository extends BaseRepository implements
    InfrastructureUserRepositoryInterface,
    DomainUserRepositoryInterface
{
    protected string $table = 'users';

    public function findById(int|string $id): ?User
    {
        $row = $this->findRecordById($id);

        if ($row === null) {
            return null;
        }

        return User::reconstitute((int) $row['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRecordById(int|string $id): ?array
    {
        if ((int) $id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `users`
             WHERE `id` = :id
               AND (`deleted_at` IS NULL)
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `users`
             WHERE `email` = :email
               AND (`deleted_at` IS NULL)
             LIMIT 1',
            ['email' => $email]
        );
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return (bool) $this->query(
            'SELECT 1 FROM `users`
             WHERE `id` = :id
               AND (`deleted_at` IS NULL)
             LIMIT 1',
            ['id' => $id]
        )->fetchColumn();
    }
}
