<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\Job\Entities\Job;
use JobVisa\App\Domain\Job\Repositories\JobRepositoryInterface as DomainJobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface as InfrastructureJobRepositoryInterface;

/**
 * Enterprise job repository.
 */
final class JobRepository extends BaseRepository implements
    InfrastructureJobRepositoryInterface,
    DomainJobRepositoryInterface
{
    protected string $table = 'jobs';

    public function findById(int|string $id): ?Job
    {
        $row = $this->findRecordById($id);

        if ($row === null) {
            return null;
        }

        return Job::reconstitute((int) $row['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRecordById(int|string $id): ?array
    {
        if ((int) $id < 1) {
            return null;
        }

        return $this->findRowById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `jobs` WHERE `slug` = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublished(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        return $this->fetchAll(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug
             FROM `jobs` j
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE j.`status` = :status
             ORDER BY j.`published_at` DESC, j.`id` DESC
             LIMIT ' . $limit,
            ['status' => 'published']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedRecordById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug
             FROM `jobs` j
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE j.`id` = :id AND j.`status` = :status
             LIMIT 1',
            ['id' => $id, 'status' => 'published']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedByEmployerUser(int $jobId, int $employerUserId): ?array
    {
        if ($jobId < 1 || $employerUserId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug,
                    e.`id` AS employer_profile_id, e.`user_id` AS employer_user_id, e.`company_id`
             FROM `jobs` j
             INNER JOIN `employers` e ON e.`id` = j.`employer_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE j.`id` = :job_id AND e.`user_id` = :user_id
             LIMIT 1',
            ['job_id' => $jobId, 'user_id' => $employerUserId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedByEmployerUser(int $employerUserId, int $limit = 50): array
    {
        if ($employerUserId < 1) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        return $this->fetchAll(
            'SELECT j.`id`, j.`title`, j.`slug`, j.`status`, j.`published_at`, j.`applications_count`,
                    c.`name` AS country_name
             FROM `jobs` j
             INNER JOIN `employers` e ON e.`id` = j.`employer_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             WHERE e.`user_id` = :user_id
             ORDER BY j.`published_at` DESC, j.`id` DESC
             LIMIT ' . $limit,
            ['user_id' => $employerUserId]
        );
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return $this->rowExists($id);
    }
}
