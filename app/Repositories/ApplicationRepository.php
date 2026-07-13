<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\Application\Entities\Application;
use JobVisa\App\Domain\Application\Repositories\ApplicationRepositoryInterface as DomainApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface as InfrastructureApplicationRepositoryInterface;

/**
 * Enterprise job-application repository.
 */
final class ApplicationRepository extends BaseRepository implements
    InfrastructureApplicationRepositoryInterface,
    DomainApplicationRepositoryInterface
{
    protected string $table = 'applications';

    public function findById(int|string $id): ?Application
    {
        $row = $this->findRecordById($id);

        if ($row === null) {
            return null;
        }

        return Application::reconstitute((int) $row['id']);
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
     * @return list<array<string, mixed>>
     */
    public function findByJobId(int $jobId, int $limit = 100): array
    {
        if ($jobId < 1) {
            return [];
        }

        $limit = max(1, min(500, $limit));

        return $this->fetchAll(
            'SELECT * FROM `applications`
             WHERE `job_id` = :job_id
             ORDER BY `applied_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['job_id' => $jobId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findDetailedByJobId(int $jobId, int $limit = 200): array
    {
        if ($jobId < 1) {
            return [];
        }

        $limit = max(1, min(500, $limit));

        return $this->fetchAll(
            'SELECT a.*,
                    u.`full_name` AS `applicant_name`,
                    u.`email`,
                    r.`title` AS `resume_title`
             FROM `applications` a
             INNER JOIN `users` u ON u.`id` = a.`user_id`
             LEFT JOIN `resumes` r ON r.`id` = a.`resume_id` AND r.`deleted_at` IS NULL
             WHERE a.`job_id` = :job_id
             ORDER BY a.`applied_at` DESC, a.`id` DESC
             LIMIT ' . $limit,
            ['job_id' => $jobId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByUserId(int $userId, int $limit = 100): array
    {
        if ($userId < 1) {
            return [];
        }

        $limit = max(1, min(500, $limit));

        return $this->fetchAll(
            'SELECT * FROM `applications`
             WHERE `user_id` = :user_id
             ORDER BY `applied_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['user_id' => $userId]
        );
    }

    public function create(int $jobId, int $userId, ?int $resumeId = null, string $status = 'submitted'): int
    {
        if ($jobId < 1 || $userId < 1) {
            throw new \InvalidArgumentException('Invalid application identifiers.');
        }

        $existing = $this->fetchOne(
            'SELECT `id` FROM `applications` WHERE `job_id` = :job_id AND `user_id` = :user_id LIMIT 1',
            ['job_id' => $jobId, 'user_id' => $userId]
        );
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $allowed = ['submitted', 'reviewing', 'shortlisted', 'rejected', 'hired', 'withdrawn'];
        if (!in_array($status, $allowed, true)) {
            $status = 'submitted';
        }

        $this->query(
            'INSERT INTO `applications`
                (`job_id`, `user_id`, `resume_id`, `status`, `applied_at`)
             VALUES
                (:job_id, :user_id, :resume_id, :status, CURRENT_TIMESTAMP(3))',
            [
                'job_id' => $jobId,
                'user_id' => $userId,
                'resume_id' => $resumeId !== null && $resumeId > 0 ? $resumeId : null,
                'status' => $status,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function countByStatusForJobIds(array $jobIds): array
    {
        $ids = [];
        foreach ($jobIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        $i = 0;
        foreach ($ids as $id) {
            $key = 'j' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
            $i++;
        }

        $rows = $this->fetchAll(
            'SELECT `status`, COUNT(*) AS `cnt`
             FROM `applications`
             WHERE `job_id` IN (' . implode(', ', $placeholders) . ')
             GROUP BY `status`',
            $params
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(string) ($row['status'] ?? '')] = (int) ($row['cnt'] ?? 0);
        }

        return $out;
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return $this->rowExists($id);
    }
}
