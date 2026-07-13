<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\CoverLetterVersionRepositoryInterface;

final class CoverLetterVersionRepository extends BaseRepository implements CoverLetterVersionRepositoryInterface
{
    protected string $table = 'cover_letter_versions';

    public function create(int $resumeId, int $userId, array $payload): int
    {
        $highlights = $this->encode($payload['highlights_json'] ?? []);
        $context = $this->encode($payload['context_json'] ?? []);

        $this->query(
            'INSERT INTO `cover_letter_versions`
                (`resume_id`, `user_id`, `job_id`, `version_label`, `status`, `style`, `tone`,
                 `body_text`, `highlights_json`, `context_json`, `ats_score`, `rules_version`, `is_active`)
             VALUES
                (:resume_id, :user_id, :job_id, :version_label, :status, :style, :tone,
                 :body_text, :highlights_json, :context_json, :ats_score, :rules_version, :is_active)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'job_id' => $payload['job_id'] ?? null,
                'version_label' => mb_substr((string) ($payload['version_label'] ?? 'Cover letter'), 0, 120),
                'status' => mb_substr((string) ($payload['status'] ?? 'preview'), 0, 32),
                'style' => mb_substr((string) ($payload['style'] ?? 'professional'), 0, 32),
                'tone' => $payload['tone'] ?? null,
                'body_text' => (string) ($payload['body_text'] ?? ''),
                'highlights_json' => $highlights,
                'context_json' => $context,
                'ats_score' => max(0, min(100, (int) ($payload['ats_score'] ?? 0))),
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
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
            'SELECT v.*, j.`title` AS `job_title`
             FROM `cover_letter_versions` v
             LEFT JOIN `jobs` j ON j.`id` = v.`job_id`
             WHERE v.`id` = :id AND v.`resume_id` = :resume_id AND v.`deleted_at` IS NULL
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
            'SELECT v.*, j.`title` AS `job_title`
             FROM `cover_letter_versions` v
             LEFT JOIN `jobs` j ON j.`id` = v.`job_id`
             WHERE v.`resume_id` = :resume_id AND v.`deleted_at` IS NULL
             ORDER BY v.`created_at` DESC, v.`id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function markSaved(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `cover_letter_versions`
             SET `status` = \'saved\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `cover_letter_versions`
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
            throw new \RuntimeException('Unable to encode cover letter JSON.');
        }

        return $json;
    }
}
