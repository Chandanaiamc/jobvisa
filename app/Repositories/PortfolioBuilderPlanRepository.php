<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;

final class PortfolioBuilderPlanRepository extends BaseRepository implements PortfolioBuilderPlanRepositoryInterface
{
    protected string $table = 'portfolio_builder_plans';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $plan = json_encode($payload['plan_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($plan === false) {
            throw new \RuntimeException('Unable to encode portfolio builder plan JSON.');
        }

        $this->query(
            'INSERT INTO `portfolio_builder_plans`
                (`resume_id`, `user_id`, `job_id`, `career_goal`, `strength_score`,
                 `project_count`, `recruiter_score`, `plan_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :job_id, :career_goal, :strength_score,
                 :project_count, :recruiter_score, :plan_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'job_id' => isset($payload['job_id']) && (int) $payload['job_id'] > 0 ? (int) $payload['job_id'] : null,
                'career_goal' => mb_substr((string) ($payload['career_goal'] ?? ''), 0, 255),
                'strength_score' => max(0, min(100, (int) ($payload['strength_score'] ?? 0))),
                'project_count' => max(0, (int) ($payload['project_count'] ?? 0)),
                'recruiter_score' => max(0, min(100, (int) ($payload['recruiter_score'] ?? 0))),
                'plan_json' => $plan,
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
            'SELECT * FROM `portfolio_builder_plans`
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
            'SELECT * FROM `portfolio_builder_plans`
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
            'SELECT * FROM `portfolio_builder_plans`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }
}
