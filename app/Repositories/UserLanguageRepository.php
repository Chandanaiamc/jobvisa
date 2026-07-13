<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\UserLanguageRepositoryInterface;

final class UserLanguageRepository extends BaseRepository implements UserLanguageRepositoryInterface
{
    protected string $table = 'user_languages';

    public function listByUserId(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT ul.*, l.name AS language_name, l.code AS language_code
             FROM `user_languages` ul
             INNER JOIN `languages` l ON l.id = ul.language_id
             WHERE ul.user_id = :user_id
             ORDER BY l.name ASC',
            ['user_id' => $userId]
        );
    }

    public function findOwned(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `user_languages` WHERE `id` = :id AND `user_id` = :user_id LIMIT 1',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function create(int $userId, array $data): int
    {
        $speaking = (string) ($data['speaking'] ?? 'conversational');
        $this->query(
            'INSERT INTO `user_languages`
                (`user_id`, `language_id`, `speaking`, `reading`, `writing`, `proficiency`)
             VALUES
                (:user_id, :language_id, :speaking, :reading, :writing, :proficiency)',
            [
                'user_id' => $userId,
                'language_id' => (int) $data['language_id'],
                'speaking' => $speaking,
                'reading' => $data['reading'] ?? $speaking,
                'writing' => $data['writing'] ?? $speaking,
                'proficiency' => $data['proficiency'] ?? $speaking,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool
    {
        if ($this->findOwned($id, $userId) === null) {
            return false;
        }

        $speaking = (string) ($data['speaking'] ?? 'conversational');
        $this->query(
            'UPDATE `user_languages` SET
                `language_id` = :language_id,
                `speaking` = :speaking,
                `reading` = :reading,
                `writing` = :writing,
                `proficiency` = :proficiency
             WHERE `id` = :id AND `user_id` = :user_id',
            [
                'language_id' => (int) $data['language_id'],
                'speaking' => $speaking,
                'reading' => $data['reading'] ?? $speaking,
                'writing' => $data['writing'] ?? $speaking,
                'proficiency' => $data['proficiency'] ?? $speaking,
                'id' => $id,
                'user_id' => $userId,
            ]
        );

        return true;
    }

    public function delete(int $id, int $userId): bool
    {
        if ($this->findOwned($id, $userId) === null) {
            return false;
        }

        $this->query(
            'DELETE FROM `user_languages` WHERE `id` = :id AND `user_id` = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );

        return true;
    }
}
