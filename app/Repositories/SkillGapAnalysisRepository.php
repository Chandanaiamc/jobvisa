<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

final class SkillGapAnalysisRepository extends BaseRepository implements SkillGapAnalysisRepositoryInterface
{
    protected string $table = 'skill_gap_analyses';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $analysis = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($analysis === false) {
            throw new \RuntimeException('Unable to encode skill gap analysis JSON.');
        }

        $this->query(
            'INSERT INTO `skill_gap_analyses`
                (`resume_id`, `user_id`, `job_id`, `gap_percentage`, `readiness_score`, `match_skills_score`,
                 `matched_count`, `missing_count`, `job_title`, `analysis_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :job_id, :gap_percentage, :readiness_score, :match_skills_score,
                 :matched_count, :missing_count, :job_title, :analysis_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'job_id' => (int) ($payload['job_id'] ?? 0),
                'gap_percentage' => max(0, min(100, (int) ($payload['gap_percentage'] ?? 0))),
                'readiness_score' => max(0, min(100, (int) ($payload['readiness_score'] ?? 0))),
                'match_skills_score' => max(0, min(100, (int) ($payload['match_skills_score'] ?? 0))),
                'matched_count' => max(0, (int) ($payload['matched_count'] ?? 0)),
                'missing_count' => max(0, (int) ($payload['missing_count'] ?? 0)),
                'job_title' => mb_substr((string) ($payload['job_title'] ?? ''), 0, 191),
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
            'SELECT * FROM `skill_gap_analyses`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    public function findLatestByResumeId(int $resumeId, int $userId, ?int $jobId = null): ?array
    {
        if ($resumeId < 1 || $userId < 1) {
            return null;
        }
        $sql = 'SELECT * FROM `skill_gap_analyses`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL';
        $params = ['resume_id' => $resumeId, 'uid' => $userId];
        if ($jobId !== null && $jobId > 0) {
            $sql .= ' AND `job_id` = :job_id';
            $params['job_id'] = $jobId;
        }
        $sql .= ' ORDER BY `created_at` DESC, `id` DESC LIMIT 1';

        return $this->fetchOne($sql, $params);
    }

    public function listByResumeId(int $resumeId, int $userId, int $limit = 20): array
    {
        if ($resumeId < 1 || $userId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT * FROM `skill_gap_analyses`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }
}
