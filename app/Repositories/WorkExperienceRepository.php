<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

final class WorkExperienceRepository extends BaseRepository implements WorkExperienceRepositoryInterface
{
    protected string $table = 'work_experience';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT w.*, c.name AS country_name
             FROM `work_experience` w
             LEFT JOIN `countries` c ON c.id = w.country_id
             WHERE w.resume_id = :resume_id
               AND w.deleted_at IS NULL
             ORDER BY w.is_current DESC, w.sort_order ASC, w.start_date DESC, w.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT w.*, c.name AS country_name
             FROM `work_experience` w
             LEFT JOIN `countries` c ON c.id = w.country_id
             WHERE w.resume_id = :resume_id
               AND w.deleted_at IS NOT NULL
             ORDER BY w.deleted_at DESC, w.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `work_experience`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `work_experience`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `work_experience`
                (`resume_id`, `company_name`, `job_title`, `employment_type`, `industry`, `country_id`, `city`,
                 `start_date`, `end_date`, `is_current`, `description`, `responsibilities`, `achievements`,
                 `reason_for_leaving`, `supervisor_name`, `supervisor_contact`, `sort_order`, `status`)
             VALUES
                (:resume_id, :company_name, :job_title, :employment_type, :industry, :country_id, :city,
                 :start_date, :end_date, :is_current, :description, :responsibilities, :achievements,
                 :reason_for_leaving, :supervisor_name, :supervisor_contact, :sort_order, :status)',
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
            'UPDATE `work_experience` SET
                `company_name` = :company_name,
                `job_title` = :job_title,
                `employment_type` = :employment_type,
                `industry` = :industry,
                `country_id` = :country_id,
                `city` = :city,
                `start_date` = :start_date,
                `end_date` = :end_date,
                `is_current` = :is_current,
                `description` = :description,
                `responsibilities` = :responsibilities,
                `achievements` = :achievements,
                `reason_for_leaving` = :reason_for_leaving,
                `supervisor_name` = :supervisor_name,
                `supervisor_contact` = :supervisor_contact,
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
            'UPDATE `work_experience`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `is_current` = 0, `status` = \'archived\'
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
            'UPDATE `work_experience`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
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
                'UPDATE `work_experience` SET `sort_order` = :sort_order
                 WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
                ['sort_order' => $order++, 'id' => $id, 'resume_id' => $resumeId]
            );
        }
    }

    public function countryExists(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }

        return (bool) $this->query(
            'SELECT 1 FROM `countries` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $countryId]
        )->fetchColumn();
    }

    public function listSkillIds(int $experienceId): array
    {
        if ($experienceId < 1) {
            return [];
        }

        $rows = $this->fetchAll(
            'SELECT `skill_id` FROM `work_experience_skills`
             WHERE `work_experience_id` = :id
             ORDER BY `skill_id` ASC',
            ['id' => $experienceId]
        );

        return array_map(static fn (array $r): int => (int) $r['skill_id'], $rows);
    }

    public function syncSkills(int $experienceId, array $skillIds): void
    {
        if ($experienceId < 1) {
            return;
        }

        $this->query(
            'DELETE FROM `work_experience_skills` WHERE `work_experience_id` = :id',
            ['id' => $experienceId]
        );

        $seen = [];
        foreach ($skillIds as $skillId) {
            $skillId = (int) $skillId;
            if ($skillId < 1 || isset($seen[$skillId])) {
                continue;
            }
            $seen[$skillId] = true;
            $this->query(
                'INSERT INTO `work_experience_skills` (`work_experience_id`, `skill_id`)
                 VALUES (:experience_id, :skill_id)',
                ['experience_id' => $experienceId, 'skill_id' => $skillId]
            );
        }
    }

    public function mapSkillsForExperiences(array $experienceIds): array
    {
        $experienceIds = array_values(array_filter(array_map('intval', $experienceIds), static fn (int $id): bool => $id > 0));
        if ($experienceIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($experienceIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT wes.work_experience_id, s.id, s.name
             FROM `work_experience_skills` wes
             INNER JOIN `skills` s ON s.id = wes.skill_id
             WHERE wes.work_experience_id IN ({$placeholders})
             ORDER BY s.name ASC"
        );
        $stmt->execute($experienceIds);

        $map = [];
        foreach ($experienceIds as $id) {
            $map[$id] = [];
        }
        while ($row = $stmt->fetch()) {
            $eid = (int) $row['work_experience_id'];
            $map[$eid][] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return $map;
    }

    public function filterActiveSkillIds(array $skillIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $skillIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT `id` FROM `skills` WHERE `is_active` = 1 AND `id` IN ({$placeholders})"
        );
        $stmt->execute($ids);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        $responsibilities = $data['responsibilities'] ?? $data['description'] ?? null;

        return [
            'resume_id' => $resumeId,
            'company_name' => $data['company_name'],
            'job_title' => $data['job_title'],
            'employment_type' => $data['employment_type'] ?? null,
            'industry' => $data['industry'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'city' => $data['city'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => !empty($data['is_current']) ? 1 : 0,
            // Keep legacy description in sync for Sprint 2C profile UI.
            'description' => $responsibilities,
            'responsibilities' => $responsibilities,
            'achievements' => $data['achievements'] ?? null,
            'reason_for_leaving' => $data['reason_for_leaving'] ?? null,
            'supervisor_name' => $data['supervisor_name'] ?? null,
            'supervisor_contact' => $data['supervisor_contact'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }
}
