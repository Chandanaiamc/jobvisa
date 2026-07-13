<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\SalaryIntelligenceHistoryRepositoryInterface;

final class SalaryIntelligenceHistoryRepository extends BaseRepository implements SalaryIntelligenceHistoryRepositoryInterface
{
    protected string $table = 'salary_intelligence_history';

    public function append(int $resumeId, int $userId, array $payload): int
    {
        $snapshot = json_encode($payload['snapshot_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshot === false) {
            throw new \RuntimeException('Unable to encode salary intelligence history JSON.');
        }

        $this->query(
            'INSERT INTO `salary_intelligence_history`
                (`resume_id`, `user_id`, `prediction_id`, `action`, `headline`,
                 `predicted_salary`, `currency`, `confidence_score`, `snapshot_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :prediction_id, :action, :headline,
                 :predicted_salary, :currency, :confidence_score, :snapshot_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'prediction_id' => isset($payload['prediction_id']) ? (int) $payload['prediction_id'] : null,
                'action' => mb_substr((string) ($payload['action'] ?? 'calculate'), 0, 32),
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'predicted_salary' => (float) ($payload['predicted_salary'] ?? 0),
                'currency' => strtoupper(substr((string) ($payload['currency'] ?? 'USD'), 0, 3)),
                'confidence_score' => max(0, min(100, (int) ($payload['confidence_score'] ?? 0))),
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
            'SELECT `id`, `resume_id`, `user_id`, `prediction_id`, `action`, `headline`,
                    `predicted_salary`, `currency`, `confidence_score`, `rules_version`, `created_at`
             FROM `salary_intelligence_history`
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
            'SELECT `id`, `resume_id`, `user_id`, `prediction_id`, `action`, `headline`,
                    `predicted_salary`, `currency`, `confidence_score`, `rules_version`, `created_at`, `deleted_at`
             FROM `salary_intelligence_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `salary_intelligence_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id, int $resumeId): bool
    {
        $row = $this->fetchOne(
            'SELECT `id` FROM `salary_intelligence_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
        if ($row === null) {
            return false;
        }
        $this->query(
            'UPDATE `salary_intelligence_history`
             SET `deleted_at` = NULL
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function permanentDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'DELETE FROM `salary_intelligence_history`
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
            'UPDATE `salary_intelligence_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return $stmt->rowCount();
    }
}
