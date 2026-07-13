<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\JobSearchCopilotHistoryRepositoryInterface;

final class JobSearchCopilotHistoryRepository extends BaseRepository implements JobSearchCopilotHistoryRepositoryInterface
{
    protected string $table = 'job_search_copilot_history';

    public function append(int $resumeId, int $userId, array $payload): int
    {
        $snapshot = json_encode($payload['snapshot_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshot === false) {
            throw new \RuntimeException('Unable to encode job search copilot history JSON.');
        }

        $this->query(
            'INSERT INTO `job_search_copilot_history`
                (`resume_id`, `user_id`, `plan_id`, `action`, `headline`,
                 `copilot_score`, `recommendation_count`, `snapshot_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :plan_id, :action, :headline,
                 :copilot_score, :recommendation_count, :snapshot_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'plan_id' => isset($payload['plan_id']) ? (int) $payload['plan_id'] : null,
                'action' => mb_substr((string) ($payload['action'] ?? 'generate'), 0, 32),
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'copilot_score' => max(0, min(100, (int) ($payload['copilot_score'] ?? 0))),
                'recommendation_count' => max(0, (int) ($payload['recommendation_count'] ?? 0)),
                'snapshot_json' => $snapshot,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
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
            'SELECT `id`, `resume_id`, `user_id`, `plan_id`, `action`, `headline`,
                    `copilot_score`, `recommendation_count`, `rules_version`, `created_at`
             FROM `job_search_copilot_history`
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
            'SELECT `id`, `resume_id`, `user_id`, `plan_id`, `action`, `headline`,
                    `copilot_score`, `recommendation_count`, `rules_version`, `created_at`, `deleted_at`
             FROM `job_search_copilot_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `job_search_copilot_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id, int $resumeId): bool
    {
        $row = $this->fetchOne(
            'SELECT `id` FROM `job_search_copilot_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
        if ($row === null) {
            return false;
        }
        $this->query(
            'UPDATE `job_search_copilot_history`
             SET `deleted_at` = NULL
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function permanentDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'DELETE FROM `job_search_copilot_history`
             WHERE `id` = :id AND `resume_id` = :resume_id',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForResume(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `job_search_copilot_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return $stmt->rowCount();
    }
}
