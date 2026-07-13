<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\LearningPathRepositoryInterface;

final class LearningPathRepository extends BaseRepository implements LearningPathRepositoryInterface
{
    protected string $table = 'learning_paths';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $path = json_encode($payload['path_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($path === false) {
            throw new \RuntimeException('Unable to encode learning path JSON.');
        }

        $this->query(
            'INSERT INTO `learning_paths`
                (`resume_id`, `user_id`, `job_id`, `career_goal`, `timeline_weeks`, `progress_percent`,
                 `milestones_total`, `milestones_done`, `alignment_score`, `path_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :job_id, :career_goal, :timeline_weeks, :progress_percent,
                 :milestones_total, :milestones_done, :alignment_score, :path_json, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'job_id' => isset($payload['job_id']) && (int) $payload['job_id'] > 0 ? (int) $payload['job_id'] : null,
                'career_goal' => mb_substr((string) ($payload['career_goal'] ?? ''), 0, 255),
                'timeline_weeks' => max(0, (int) ($payload['timeline_weeks'] ?? 0)),
                'progress_percent' => max(0, min(100, (int) ($payload['progress_percent'] ?? 0))),
                'milestones_total' => max(0, (int) ($payload['milestones_total'] ?? 0)),
                'milestones_done' => max(0, (int) ($payload['milestones_done'] ?? 0)),
                'alignment_score' => max(0, min(100, (int) ($payload['alignment_score'] ?? 0))),
                'path_json' => $path,
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
            'SELECT * FROM `learning_paths`
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
            'SELECT * FROM `learning_paths`
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
            'SELECT * FROM `learning_paths`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    public function updateProgress(int $id, int $resumeId, int $userId, array $payload): bool
    {
        $path = json_encode($payload['path_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($path === false) {
            throw new \RuntimeException('Unable to encode learning path JSON.');
        }

        $stmt = $this->query(
            'UPDATE `learning_paths`
             SET `path_json` = :path_json,
                 `progress_percent` = :progress_percent,
                 `milestones_total` = :milestones_total,
                 `milestones_done` = :milestones_done
             WHERE `id` = :id AND `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL',
            [
                'path_json' => $path,
                'progress_percent' => max(0, min(100, (int) ($payload['progress_percent'] ?? 0))),
                'milestones_total' => max(0, (int) ($payload['milestones_total'] ?? 0)),
                'milestones_done' => max(0, (int) ($payload['milestones_done'] ?? 0)),
                'id' => $id,
                'resume_id' => $resumeId,
                'uid' => $userId,
            ]
        );

        return $stmt->rowCount() > 0;
    }
}
