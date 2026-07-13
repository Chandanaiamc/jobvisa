<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;

final class EducationRepository extends BaseRepository implements EducationRepositoryInterface
{
    protected string $table = 'education';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT e.*, c.name AS country_name
             FROM `education` e
             LEFT JOIN `countries` c ON c.id = e.country_id
             WHERE e.resume_id = :resume_id
               AND e.deleted_at IS NULL
             ORDER BY e.sort_order ASC, e.start_date DESC, e.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT e.*, c.name AS country_name
             FROM `education` e
             LEFT JOIN `countries` c ON c.id = e.country_id
             WHERE e.resume_id = :resume_id
               AND e.deleted_at IS NOT NULL
             ORDER BY e.deleted_at DESC, e.id DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `education`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `education`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `education`
                (`resume_id`, `school`, `institution`, `qualification_type`, `degree`, `field_of_study`, `grade`,
                 `country_id`, `city`, `start_date`, `end_date`, `is_current`, `description`, `sort_order`, `status`)
             VALUES
                (:resume_id, :school, :institution, :qualification_type, :degree, :field_of_study, :grade,
                 :country_id, :city, :start_date, :end_date, :is_current, :description, :sort_order, :status)',
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
            'UPDATE `education` SET
                `school` = :school,
                `institution` = :institution,
                `qualification_type` = :qualification_type,
                `degree` = :degree,
                `field_of_study` = :field_of_study,
                `grade` = :grade,
                `country_id` = :country_id,
                `city` = :city,
                `start_date` = :start_date,
                `end_date` = :end_date,
                `is_current` = :is_current,
                `description` = :description,
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
            'UPDATE `education`
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
            'UPDATE `education`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function clearCurrentExcept(int $resumeId, ?int $exceptId = null): void
    {
        if ($resumeId < 1) {
            return;
        }

        if ($exceptId !== null && $exceptId > 0) {
            $this->query(
                'UPDATE `education` SET `is_current` = 0
                 WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL AND `id` <> :except_id',
                ['resume_id' => $resumeId, 'except_id' => $exceptId]
            );

            return;
        }

        $this->query(
            'UPDATE `education` SET `is_current` = 0
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
                'UPDATE `education` SET `sort_order` = :sort_order
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        return [
            'resume_id' => $resumeId,
            'school' => $data['school'] ?? null,
            'institution' => $data['institution'],
            'qualification_type' => $data['qualification_type'] ?? null,
            'degree' => $data['degree'],
            'field_of_study' => $data['field_of_study'] ?? null,
            'grade' => $data['grade'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'city' => $data['city'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => !empty($data['is_current']) ? 1 : 0,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }
}
