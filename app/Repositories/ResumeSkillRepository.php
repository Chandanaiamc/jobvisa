<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;

final class ResumeSkillRepository extends BaseRepository implements ResumeSkillRepositoryInterface
{
    protected string $table = 'resume_skills';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT rs.*, s.name AS skill_name, s.slug AS skill_slug
             FROM `resume_skills` rs
             INNER JOIN `skills` s ON s.id = rs.skill_id
             WHERE rs.resume_id = :resume_id
               AND rs.deleted_at IS NULL
             ORDER BY rs.is_primary DESC, rs.sort_order ASC, s.name ASC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT rs.*, s.name AS skill_name, s.slug AS skill_slug
             FROM `resume_skills` rs
             INNER JOIN `skills` s ON s.id = rs.skill_id
             WHERE rs.resume_id = :resume_id
               AND rs.deleted_at IS NOT NULL
             ORDER BY rs.deleted_at DESC, rs.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT rs.*, s.name AS skill_name, s.slug AS skill_slug
             FROM `resume_skills` rs
             INNER JOIN `skills` s ON s.id = rs.skill_id
             WHERE rs.id = :id AND rs.resume_id = :resume_id AND rs.deleted_at IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT rs.*, s.name AS skill_name, s.slug AS skill_slug
             FROM `resume_skills` rs
             INNER JOIN `skills` s ON s.id = rs.skill_id
             WHERE rs.id = :id AND rs.resume_id = :resume_id AND rs.deleted_at IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findByResumeAndSkill(int $resumeId, int $skillId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_skills`
             WHERE `resume_id` = :resume_id AND `skill_id` = :skill_id
             LIMIT 1',
            ['resume_id' => $resumeId, 'skill_id' => $skillId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_skills`
                (`resume_id`, `skill_id`, `level`, `years_experience`, `last_used_year`,
                 `is_primary`, `sort_order`, `status`)
             VALUES
                (:resume_id, :skill_id, :level, :years_experience, :last_used_year,
                 :is_primary, :sort_order, :status)',
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
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_skills` SET
                `skill_id` = :skill_id,
                `level` = :level,
                `years_experience` = :years_experience,
                `last_used_year` = :last_used_year,
                `is_primary` = :is_primary,
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
            'UPDATE `resume_skills`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `is_primary` = 0, `status` = \'archived\'
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
            'UPDATE `resume_skills`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function clearPrimaryExcept(int $resumeId, ?int $exceptId = null): void
    {
        if ($resumeId < 1) {
            return;
        }

        if ($exceptId !== null && $exceptId > 0) {
            $this->query(
                'UPDATE `resume_skills` SET `is_primary` = 0
                 WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL AND `id` <> :except_id',
                ['resume_id' => $resumeId, 'except_id' => $exceptId]
            );

            return;
        }

        $this->query(
            'UPDATE `resume_skills` SET `is_primary` = 0
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );
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
                'UPDATE `resume_skills` SET `sort_order` = :sort_order
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

        $stmt = $this->query(
            'SELECT COUNT(*) FROM `resume_skills`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        return [
            'resume_id' => $resumeId,
            'skill_id' => (int) $data['skill_id'],
            'level' => (string) ($data['level'] ?? 'intermediate'),
            'years_experience' => $data['years_experience'] ?? null,
            'last_used_year' => $data['last_used_year'] ?? null,
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }
}
