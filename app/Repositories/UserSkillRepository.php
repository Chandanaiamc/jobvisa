<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\UserSkillRepositoryInterface;

final class UserSkillRepository extends BaseRepository implements UserSkillRepositoryInterface
{
    protected string $table = 'user_skills';

    public function listByUserId(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT us.*, s.name AS skill_name, s.slug AS skill_slug
             FROM `user_skills` us
             INNER JOIN `skills` s ON s.id = us.skill_id
             WHERE us.user_id = :user_id
             ORDER BY s.name ASC',
            ['user_id' => $userId]
        );
    }

    public function findOwned(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `user_skills` WHERE `id` = :id AND `user_id` = :user_id LIMIT 1',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function attach(int $userId, int $skillId, string $level): int
    {
        $existing = $this->fetchOne(
            'SELECT `id` FROM `user_skills` WHERE `user_id` = :user_id AND `skill_id` = :skill_id LIMIT 1',
            ['user_id' => $userId, 'skill_id' => $skillId]
        );

        if ($existing !== null) {
            $this->updateLevel((int) $existing['id'], $userId, $level);

            return (int) $existing['id'];
        }

        $this->query(
            'INSERT INTO `user_skills` (`user_id`, `skill_id`, `proficiency`)
             VALUES (:user_id, :skill_id, :proficiency)',
            ['user_id' => $userId, 'skill_id' => $skillId, 'proficiency' => $level]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function updateLevel(int $id, int $userId, string $level): bool
    {
        if ($this->findOwned($id, $userId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `user_skills` SET `proficiency` = :level WHERE `id` = :id AND `user_id` = :user_id',
            ['level' => $level, 'id' => $id, 'user_id' => $userId]
        );

        return true;
    }

    public function detach(int $id, int $userId): bool
    {
        if ($this->findOwned($id, $userId) === null) {
            return false;
        }

        $this->query(
            'DELETE FROM `user_skills` WHERE `id` = :id AND `user_id` = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );

        return true;
    }
}
