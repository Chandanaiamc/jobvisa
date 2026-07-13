<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ApplicationAssistantHistoryRepositoryInterface;

final class ApplicationAssistantHistoryRepository extends BaseRepository implements ApplicationAssistantHistoryRepositoryInterface
{
    protected string $table = 'application_assistant_history';

    public function append(int $userId, int $jobId, int $resumeId, array $payload): int
    {
        $snapshot = json_encode($payload['snapshot_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshot === false) {
            throw new \RuntimeException('Unable to encode application assistant history JSON.');
        }

        $this->query(
            'INSERT INTO `application_assistant_history`
                (`user_id`, `job_id`, `resume_id`, `analysis_id`, `action`, `headline`,
                 `readiness_score`, `snapshot_json`, `rules_version`)
             VALUES
                (:user_id, :job_id, :resume_id, :analysis_id, :action, :headline,
                 :readiness_score, :snapshot_json, :rules_version)',
            [
                'user_id' => $userId,
                'job_id' => $jobId,
                'resume_id' => $resumeId,
                'analysis_id' => isset($payload['analysis_id']) ? (int) $payload['analysis_id'] : null,
                'action' => mb_substr((string) ($payload['action'] ?? 'analyze'), 0, 32),
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'readiness_score' => max(0, min(100, (int) ($payload['readiness_score'] ?? 0))),
                'snapshot_json' => $snapshot,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listByUserJob(int $userId, int $jobId, int $limit = 25): array
    {
        if ($userId < 1 || $jobId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `user_id`, `job_id`, `resume_id`, `analysis_id`, `action`, `headline`,
                    `readiness_score`, `rules_version`, `created_at`
             FROM `application_assistant_history`
             WHERE `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['uid' => $userId, 'job_id' => $jobId]
        );
    }

    public function listDeletedByUserJob(int $userId, int $jobId, int $limit = 25): array
    {
        if ($userId < 1 || $jobId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `user_id`, `job_id`, `resume_id`, `analysis_id`, `action`, `headline`,
                    `readiness_score`, `rules_version`, `created_at`, `deleted_at`
             FROM `application_assistant_history`
             WHERE `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['uid' => $userId, 'job_id' => $jobId]
        );
    }

    public function softDelete(int $id, int $userId, int $jobId): bool
    {
        $stmt = $this->query(
            'UPDATE `application_assistant_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NULL',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id, int $userId, int $jobId): bool
    {
        $row = $this->fetchOne(
            'SELECT `id` FROM `application_assistant_history`
             WHERE `id` = :id AND `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );
        if ($row === null) {
            return false;
        }
        $this->query(
            'UPDATE `application_assistant_history`
             SET `deleted_at` = NULL
             WHERE `id` = :id AND `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );

        return true;
    }

    public function permanentDelete(int $id, int $userId, int $jobId): bool
    {
        $stmt = $this->query(
            'DELETE FROM `application_assistant_history`
             WHERE `id` = :id AND `user_id` = :uid AND `job_id` = :job_id',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForUserJob(int $userId, int $jobId): int
    {
        if ($userId < 1 || $jobId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `application_assistant_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NULL',
            ['uid' => $userId, 'job_id' => $jobId]
        );

        return $stmt->rowCount();
    }
}
