<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\CareerCoachHistoryRepositoryInterface;

final class CareerCoachHistoryRepository extends BaseRepository implements CareerCoachHistoryRepositoryInterface
{
    protected string $table = 'career_coach_history';

    public function append(int $resumeId, int $userId, array $payload): int
    {
        $snapshot = json_encode($payload['snapshot_json'] ?? $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshot === false) {
            throw new \RuntimeException('Unable to encode career coach history JSON.');
        }

        $this->query(
            'INSERT INTO `career_coach_history`
                (`resume_id`, `user_id`, `target_role`, `headline`, `snapshot_json`, `coach_version`)
             VALUES
                (:resume_id, :user_id, :target_role, :headline, :snapshot_json, :coach_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'target_role' => $payload['target_role'] ?? null,
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'snapshot_json' => $snapshot,
                'coach_version' => (string) ($payload['coach_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listByResumeId(int $resumeId, int $limit = 25): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `resume_id`, `user_id`, `target_role`, `headline`, `coach_version`, `created_at`
             FROM `career_coach_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId, int $limit = 25): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `resume_id`, `user_id`, `target_role`, `headline`, `coach_version`, `created_at`, `deleted_at`
             FROM `career_coach_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        if ($id < 1 || $resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `career_coach_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        if ($id < 1 || $resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `career_coach_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `career_coach_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id, int $resumeId): bool
    {
        if ($this->findDeletedOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `career_coach_history`
             SET `deleted_at` = NULL
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function softDeleteAllForResume(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `career_coach_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return $stmt->rowCount();
    }
}
