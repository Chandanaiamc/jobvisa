<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;

final class ResumeLanguageRepository extends BaseRepository implements ResumeLanguageRepositoryInterface
{
    protected string $table = 'resume_languages';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT rl.*, l.name AS language_name, l.code AS language_code
             FROM `resume_languages` rl
             INNER JOIN `languages` l ON l.id = rl.language_id
             WHERE rl.resume_id = :resume_id
               AND rl.deleted_at IS NULL
             ORDER BY rl.is_native DESC, rl.sort_order ASC, l.name ASC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT rl.*, l.name AS language_name, l.code AS language_code
             FROM `resume_languages` rl
             INNER JOIN `languages` l ON l.id = rl.language_id
             WHERE rl.resume_id = :resume_id
               AND rl.deleted_at IS NOT NULL
             ORDER BY rl.deleted_at DESC, rl.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT rl.*, l.name AS language_name, l.code AS language_code
             FROM `resume_languages` rl
             INNER JOIN `languages` l ON l.id = rl.language_id
             WHERE rl.id = :id AND rl.resume_id = :resume_id AND rl.deleted_at IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT rl.*, l.name AS language_name, l.code AS language_code
             FROM `resume_languages` rl
             INNER JOIN `languages` l ON l.id = rl.language_id
             WHERE rl.id = :id AND rl.resume_id = :resume_id AND rl.deleted_at IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findByResumeAndLanguage(int $resumeId, int $languageId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_languages`
             WHERE `resume_id` = :resume_id AND `language_id` = :language_id
             LIMIT 1',
            ['resume_id' => $resumeId, 'language_id' => $languageId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_languages`
                (`resume_id`, `language_id`, `speaking`, `reading`, `writing`, `listening`,
                 `is_native`, `certificate_type`, `certificate_score`, `certificate_issued_at`,
                 `certificate_expires_at`, `certificate_path`, `sort_order`, `status`)
             VALUES
                (:resume_id, :language_id, :speaking, :reading, :writing, :listening,
                 :is_native, :certificate_type, :certificate_score, :certificate_issued_at,
                 :certificate_expires_at, :certificate_path, :sort_order, :status)',
            $this->bindPayload($resumeId, $data)
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $resumeId, array $data): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $payload = $this->bindPayload($resumeId, $data);
        unset($payload['certificate_path']);
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_languages` SET
                `language_id` = :language_id,
                `speaking` = :speaking,
                `reading` = :reading,
                `writing` = :writing,
                `listening` = :listening,
                `is_native` = :is_native,
                `certificate_type` = :certificate_type,
                `certificate_score` = :certificate_score,
                `certificate_issued_at` = :certificate_issued_at,
                `certificate_expires_at` = :certificate_expires_at,
                `sort_order` = :sort_order,
                `status` = :status
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            $payload
        );

        return true;
    }

    public function delete(int $id, int $resumeId): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_languages`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `status` = \'archived\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function restore(int $id, int $resumeId): bool
    {
        if ($this->findDeletedOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_languages`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateCertificatePath(int $id, int $resumeId, ?string $path): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_languages` SET `certificate_path` = :path
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['path' => $path, 'id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function reorder(int $resumeId, array $orderedIds): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $this->query(
                'UPDATE `resume_languages` SET `sort_order` = :sort_order
                 WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
                ['sort_order' => $order++, 'id' => $id, 'resume_id' => $resumeId]
            );
        }
    }

    public function countActive(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `resume_languages`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        return [
            'resume_id' => $resumeId,
            'language_id' => (int) $data['language_id'],
            'speaking' => (string) ($data['speaking'] ?? 'B1'),
            'reading' => (string) ($data['reading'] ?? 'B1'),
            'writing' => (string) ($data['writing'] ?? 'B1'),
            'listening' => (string) ($data['listening'] ?? 'B1'),
            'is_native' => !empty($data['is_native']) ? 1 : 0,
            'certificate_type' => $data['certificate_type'] ?? null,
            'certificate_score' => $data['certificate_score'] ?? null,
            'certificate_issued_at' => $data['certificate_issued_at'] ?? null,
            'certificate_expires_at' => $data['certificate_expires_at'] ?? null,
            'certificate_path' => $data['certificate_path'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }
}
