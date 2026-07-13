<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\CoverLetterHistoryRepositoryInterface;

final class CoverLetterHistoryRepository extends BaseRepository implements CoverLetterHistoryRepositoryInterface
{
    protected string $table = 'cover_letter_history';

    public function append(int $resumeId, int $userId, array $payload): int
    {
        $snapshot = json_encode($payload['snapshot_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshot === false) {
            throw new \RuntimeException('Unable to encode cover letter history JSON.');
        }

        $this->query(
            'INSERT INTO `cover_letter_history`
                (`resume_id`, `user_id`, `version_id`, `action`, `style`, `headline`,
                 `snapshot_json`, `ats_score`, `rules_version`)
             VALUES
                (:resume_id, :user_id, :version_id, :action, :style, :headline,
                 :snapshot_json, :ats_score, :rules_version)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'version_id' => isset($payload['version_id']) ? (int) $payload['version_id'] : null,
                'action' => mb_substr((string) ($payload['action'] ?? 'generate'), 0, 32),
                'style' => $payload['style'] ?? null,
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'snapshot_json' => $snapshot,
                'ats_score' => max(0, min(100, (int) ($payload['ats_score'] ?? 0))),
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listByResumeId(int $resumeId, int $limit = 25): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `resume_id`, `user_id`, `version_id`, `action`, `style`, `headline`,
                    `ats_score`, `rules_version`, `created_at`
             FROM `cover_letter_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId, int $limit = 25): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `resume_id`, `user_id`, `version_id`, `action`, `style`, `headline`,
                    `ats_score`, `rules_version`, `created_at`, `deleted_at`
             FROM `cover_letter_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `cover_letter_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function restore(int $id, int $resumeId): bool
    {
        $row = $this->fetchOne(
            'SELECT `id` FROM `cover_letter_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
        if ($row === null) {
            return false;
        }
        $this->query(
            'UPDATE `cover_letter_history`
             SET `deleted_at` = NULL
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function permanentDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'DELETE FROM `cover_letter_history`
             WHERE `id` = :id AND `resume_id` = :resume_id',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForResume(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `cover_letter_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return $stmt->rowCount();
    }
}
