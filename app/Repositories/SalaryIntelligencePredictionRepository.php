<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;

final class SalaryIntelligencePredictionRepository extends BaseRepository implements SalaryIntelligencePredictionRepositoryInterface
{
    protected string $table = 'salary_intelligence_predictions';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $analysis = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($analysis === false) {
            throw new \RuntimeException('Unable to encode salary intelligence analysis JSON.');
        }

        $this->query(
            'INSERT INTO `salary_intelligence_predictions`
                (`resume_id`, `user_id`, `currency`, `predicted_salary`, `min_salary`, `max_salary`,
                 `market_average`, `recommended_target`, `confidence_score`, `career_level`,
                 `job_title`, `location_label`, `industry`, `analysis_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :currency, :predicted_salary, :min_salary, :max_salary,
                 :market_average, :recommended_target, :confidence_score, :career_level,
                 :job_title, :location_label, :industry, :analysis_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'currency' => strtoupper(substr((string) ($payload['currency'] ?? 'USD'), 0, 3)),
                'predicted_salary' => (float) ($payload['predicted_salary'] ?? 0),
                'min_salary' => (float) ($payload['min_salary'] ?? 0),
                'max_salary' => (float) ($payload['max_salary'] ?? 0),
                'market_average' => (float) ($payload['market_average'] ?? 0),
                'recommended_target' => (float) ($payload['recommended_target'] ?? 0),
                'confidence_score' => max(0, min(100, (int) ($payload['confidence_score'] ?? 0))),
                'career_level' => mb_substr((string) ($payload['career_level'] ?? ''), 0, 64),
                'job_title' => mb_substr((string) ($payload['job_title'] ?? ''), 0, 191),
                'location_label' => mb_substr((string) ($payload['location_label'] ?? ''), 0, 191),
                'industry' => mb_substr((string) ($payload['industry'] ?? ''), 0, 150),
                'analysis_json' => $analysis,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function findOwned(int $id, int $resumeId, int $userId): ?array
    {
        if ($id < 1 || $resumeId < 1 || $userId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `salary_intelligence_predictions`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    public function findLatestByResumeId(int $resumeId, int $userId): ?array
    {
        if ($resumeId < 1 || $userId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `salary_intelligence_predictions`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT 1',
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    public function listByResumeId(int $resumeId, int $userId, int $limit = 20): array
    {
        if ($resumeId < 1 || $userId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT * FROM `salary_intelligence_predictions`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    public function softDelete(int $id, int $resumeId, int $userId): bool
    {
        $stmt = $this->query(
            'UPDATE `salary_intelligence_predictions`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId, 'uid' => $userId]
        );

        return $stmt->rowCount() > 0;
    }
}
