<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\OfferEvaluationAnalysisRepositoryInterface;

final class OfferEvaluationAnalysisRepository extends BaseRepository implements OfferEvaluationAnalysisRepositoryInterface
{
    protected string $table = 'offer_evaluation_analyses';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $json = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode offer evaluation JSON.');
        }

        $this->query(
            'INSERT INTO `offer_evaluation_analyses`
                (`resume_id`, `user_id`, `job_id`, `company_name`, `job_title`, `currency`, `base_salary`,
                 `overall_score`, `compensation_score`, `benefits_score`, `growth_score`, `lifestyle_score`,
                 `recommendation`, `analysis_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :job_id, :company_name, :job_title, :currency, :base_salary,
                 :overall_score, :compensation_score, :benefits_score, :growth_score, :lifestyle_score,
                 :recommendation, :analysis_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'job_id' => isset($payload['job_id']) && (int) $payload['job_id'] > 0 ? (int) $payload['job_id'] : null,
                'company_name' => mb_substr((string) ($payload['company_name'] ?? ''), 0, 191),
                'job_title' => mb_substr((string) ($payload['job_title'] ?? ''), 0, 191),
                'currency' => mb_substr((string) ($payload['currency'] ?? 'USD'), 0, 3),
                'base_salary' => round((float) ($payload['base_salary'] ?? 0), 2),
                'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
                'compensation_score' => max(0, min(100, (int) ($payload['compensation_score'] ?? 0))),
                'benefits_score' => max(0, min(100, (int) ($payload['benefits_score'] ?? 0))),
                'growth_score' => max(0, min(100, (int) ($payload['growth_score'] ?? 0))),
                'lifestyle_score' => max(0, min(100, (int) ($payload['lifestyle_score'] ?? 0))),
                'recommendation' => mb_substr((string) ($payload['recommendation'] ?? 'negotiate'), 0, 32),
                'analysis_json' => $json,
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
            'SELECT * FROM `offer_evaluation_analyses`
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
            'SELECT * FROM `offer_evaluation_analyses`
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
            'SELECT * FROM `offer_evaluation_analyses`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }
}
