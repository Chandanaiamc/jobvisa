<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\MockInterviewSessionRepositoryInterface;

final class MockInterviewSessionRepository extends BaseRepository implements MockInterviewSessionRepositoryInterface
{
    protected string $table = 'mock_interview_sessions';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $json = json_encode($payload['session_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode mock interview session JSON.');
        }

        $this->query(
            'INSERT INTO `mock_interview_sessions`
                (`resume_id`, `user_id`, `job_id`, `job_title`, `career_level`, `status`,
                 `overall_score`, `communication_score`, `technical_score`, `confidence_score`,
                 `star_score`, `session_json`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :job_id, :job_title, :career_level, :status,
                 :overall_score, :communication_score, :technical_score, :confidence_score,
                 :star_score, :session_json, :rules_version)',
            $this->bindPayload($resumeId, $userId, $payload, $json)
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $resumeId, int $userId, array $payload): bool
    {
        $json = json_encode($payload['session_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode mock interview session JSON.');
        }
        $params = $this->bindPayload($resumeId, $userId, $payload, $json);
        $params['id'] = $id;

        $stmt = $this->query(
            'UPDATE `mock_interview_sessions`
             SET `job_id` = :job_id,
                 `job_title` = :job_title,
                 `career_level` = :career_level,
                 `status` = :status,
                 `overall_score` = :overall_score,
                 `communication_score` = :communication_score,
                 `technical_score` = :technical_score,
                 `confidence_score` = :confidence_score,
                 `star_score` = :star_score,
                 `session_json` = :session_json,
                 `rules_version` = :rules_version
             WHERE `id` = :id AND `resume_id` = :resume_id AND `user_id` = :user_id AND `deleted_at` IS NULL',
            $params
        );

        return $stmt->rowCount() > 0;
    }

    public function findOwned(int $id, int $resumeId, int $userId): ?array
    {
        if ($id < 1 || $resumeId < 1 || $userId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `mock_interview_sessions`
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
            'SELECT * FROM `mock_interview_sessions`
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
            'SELECT * FROM `mock_interview_sessions`
             WHERE `resume_id` = :resume_id AND `user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId, 'uid' => $userId]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, int $userId, array $payload, string $json): array
    {
        return [
            'resume_id' => $resumeId,
            'user_id' => $userId,
            'job_id' => isset($payload['job_id']) && (int) $payload['job_id'] > 0 ? (int) $payload['job_id'] : null,
            'job_title' => mb_substr((string) ($payload['job_title'] ?? ''), 0, 191),
            'career_level' => mb_substr((string) ($payload['career_level'] ?? ''), 0, 64),
            'status' => mb_substr((string) ($payload['status'] ?? 'generated'), 0, 32),
            'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
            'communication_score' => max(0, min(100, (int) ($payload['communication_score'] ?? 0))),
            'technical_score' => max(0, min(100, (int) ($payload['technical_score'] ?? 0))),
            'confidence_score' => max(0, min(100, (int) ($payload['confidence_score'] ?? 0))),
            'star_score' => max(0, min(100, (int) ($payload['star_score'] ?? 0))),
            'session_json' => $json,
            'rules_version' => (string) ($payload['rules_version'] ?? ''),
        ];
    }
}
