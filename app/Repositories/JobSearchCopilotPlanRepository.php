<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\JobSearchCopilotPlanRepositoryInterface;

final class JobSearchCopilotPlanRepository extends BaseRepository implements JobSearchCopilotPlanRepositoryInterface
{
    protected string $table = 'job_search_copilot_plans';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $plan = json_encode($payload['plan_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($plan === false) {
            throw new \RuntimeException('Unable to encode job search copilot plan JSON.');
        }

        $this->query(
            'INSERT INTO `job_search_copilot_plans`
                (`resume_id`, `user_id`, `career_goal`, `copilot_score`,
                 `recommendation_count`, `plan_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :career_goal, :copilot_score,
                 :recommendation_count, :plan_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'career_goal' => mb_substr((string) ($payload['career_goal'] ?? ''), 0, 255),
                'copilot_score' => max(0, min(100, (int) ($payload['copilot_score'] ?? 0))),
                'recommendation_count' => max(0, (int) ($payload['recommendation_count'] ?? 0)),
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
            'SELECT * FROM `job_search_copilot_plans`
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
            'SELECT * FROM `job_search_copilot_plans`
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
            'SELECT * FROM `job_search_copilot_plans`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }
}
