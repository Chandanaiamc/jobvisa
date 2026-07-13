<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ApplicationAssistantAnalysisRepositoryInterface;

final class ApplicationAssistantAnalysisRepository extends BaseRepository implements ApplicationAssistantAnalysisRepositoryInterface
{
    protected string $table = 'application_assistant_analyses';

    public function create(int $userId, int $jobId, int $resumeId, array $payload): int
    {
        $analysis = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($analysis === false) {
            throw new \RuntimeException('Unable to encode application assistant analysis JSON.');
        }

        $this->query(
            'INSERT INTO `application_assistant_analyses`
                (`user_id`, `job_id`, `resume_id`, `readiness_score`, `skills_score`, `experience_score`,
                 `education_score`, `certification_score`, `portfolio_score`, `match_overall`, `resume_overall`,
                 `analysis_json`, `rules_version`)
             VALUES
                (:user_id, :job_id, :resume_id, :readiness_score, :skills_score, :experience_score,
                 :education_score, :certification_score, :portfolio_score, :match_overall, :resume_overall,
                 :analysis_json, :rules_version)',
            [
                'user_id' => $userId,
                'job_id' => $jobId,
                'resume_id' => $resumeId,
                'readiness_score' => max(0, min(100, (int) ($payload['readiness_score'] ?? 0))),
                'skills_score' => max(0, min(100, (int) ($payload['skills_score'] ?? 0))),
                'experience_score' => max(0, min(100, (int) ($payload['experience_score'] ?? 0))),
                'education_score' => max(0, min(100, (int) ($payload['education_score'] ?? 0))),
                'certification_score' => max(0, min(100, (int) ($payload['certification_score'] ?? 0))),
                'portfolio_score' => max(0, min(100, (int) ($payload['portfolio_score'] ?? 0))),
                'match_overall' => max(0, min(100, (int) ($payload['match_overall'] ?? 0))),
                'resume_overall' => max(0, min(100, (int) ($payload['resume_overall'] ?? 0))),
                'analysis_json' => $analysis,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function findOwned(int $id, int $userId, int $jobId): ?array
    {
        if ($id < 1 || $userId < 1 || $jobId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT a.*, j.`title` AS `job_title`, r.`title` AS `resume_title`
             FROM `application_assistant_analyses` a
             INNER JOIN `jobs` j ON j.`id` = a.`job_id`
             INNER JOIN `resumes` r ON r.`id` = a.`resume_id`
             WHERE a.`id` = :id AND a.`user_id` = :uid AND a.`job_id` = :job_id AND a.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );
    }

    public function findLatestForUserJob(int $userId, int $jobId, ?int $resumeId = null): ?array
    {
        if ($userId < 1 || $jobId < 1) {
            return null;
        }
        $sql = 'SELECT a.*, j.`title` AS `job_title`, r.`title` AS `resume_title`
             FROM `application_assistant_analyses` a
             INNER JOIN `jobs` j ON j.`id` = a.`job_id`
             INNER JOIN `resumes` r ON r.`id` = a.`resume_id`
             WHERE a.`user_id` = :uid AND a.`job_id` = :job_id AND a.`deleted_at` IS NULL';
        $params = ['uid' => $userId, 'job_id' => $jobId];
        if ($resumeId !== null && $resumeId > 0) {
            $sql .= ' AND a.`resume_id` = :resume_id';
            $params['resume_id'] = $resumeId;
        }
        $sql .= ' ORDER BY a.`created_at` DESC, a.`id` DESC LIMIT 1';

        return $this->fetchOne($sql, $params);
    }

    public function listByUserJob(int $userId, int $jobId, int $limit = 20): array
    {
        if ($userId < 1 || $jobId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT a.*, j.`title` AS `job_title`, r.`title` AS `resume_title`
             FROM `application_assistant_analyses` a
             INNER JOIN `jobs` j ON j.`id` = a.`job_id`
             INNER JOIN `resumes` r ON r.`id` = a.`resume_id`
             WHERE a.`user_id` = :uid AND a.`job_id` = :job_id AND a.`deleted_at` IS NULL
             ORDER BY a.`created_at` DESC, a.`id` DESC
             LIMIT ' . $limit,
            ['uid' => $userId, 'job_id' => $jobId]
        );
    }

    public function softDelete(int $id, int $userId, int $jobId): bool
    {
        $stmt = $this->query(
            'UPDATE `application_assistant_analyses`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `user_id` = :uid AND `job_id` = :job_id AND `deleted_at` IS NULL',
            ['id' => $id, 'uid' => $userId, 'job_id' => $jobId]
        );

        return $stmt->rowCount() > 0;
    }
}
