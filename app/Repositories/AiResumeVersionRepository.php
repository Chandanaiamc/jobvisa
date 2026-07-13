<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\AiResumeVersionRepositoryInterface;

final class AiResumeVersionRepository extends BaseRepository implements AiResumeVersionRepositoryInterface
{
    protected string $table = 'ai_resume_versions';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $content = $this->encode($payload['content_json'] ?? []);
        $missing = $this->encode($payload['missing_keywords_json'] ?? []);
        $suggestions = $this->encode($payload['keyword_suggestions_json'] ?? []);

        $this->query(
            'INSERT INTO `ai_resume_versions`
                (`resume_id`, `user_id`, `version_label`, `status`, `target_role`, `professional_summary`,
                 `content_json`, `ats_score`, `missing_keywords_json`, `keyword_suggestions_json`,
                 `builder_version`, `is_active`)
             VALUES
                (:resume_id, :user_id, :version_label, :status, :target_role, :professional_summary,
                 :content_json, :ats_score, :missing_keywords_json, :keyword_suggestions_json,
                 :builder_version, :is_active)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'version_label' => mb_substr((string) ($payload['version_label'] ?? 'AI draft'), 0, 120),
                'status' => mb_substr((string) ($payload['status'] ?? 'preview'), 0, 32),
                'target_role' => $payload['target_role'] ?? null,
                'professional_summary' => (string) ($payload['professional_summary'] ?? ''),
                'content_json' => $content,
                'ats_score' => max(0, min(100, (int) ($payload['ats_score'] ?? 0))),
                'missing_keywords_json' => $missing,
                'keyword_suggestions_json' => $suggestions,
                'builder_version' => (string) ($payload['builder_version'] ?? ''),
                'is_active' => !empty($payload['is_active']) ? 1 : 0,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        if ($id < 1 || $resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `ai_resume_versions`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function listByResumeId(int $resumeId, int $limit = 20): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT * FROM `ai_resume_versions`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `is_active` DESC, `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function markSaved(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `ai_resume_versions`
             SET `status` = \'saved\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function setActive(int $id, int $resumeId): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }
        $this->query(
            'UPDATE `ai_resume_versions`
             SET `is_active` = 0
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );
        $stmt = $this->query(
            'UPDATE `ai_resume_versions`
             SET `is_active` = 1, `status` = \'saved\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `ai_resume_versions`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `is_active` = 0
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    /** @param array<mixed> $data */
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode AI resume version JSON.');
        }

        return $json;
    }
}
