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

    /**
     * @return list<array<string, mixed>>
     */
    public function findDetailedByUserId(int $userId, int $limit = 100): array
    {
        if ($userId < 1) {
            return [];
        }
        $limit = max(1, min(500, $limit));

        return $this->fetchAll(
            'SELECT a.*,
                    j.`title` AS `job_title`,
                    j.`status` AS `job_status`,
                    c.`name` AS `country_name`,
                    r.`title` AS `resume_title`
             FROM `applications` a
             INNER JOIN `jobs` j ON j.`id` = a.`job_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `resumes` r ON r.`id` = a.`resume_id` AND r.`deleted_at` IS NULL
             WHERE a.`user_id` = :user_id
             ORDER BY a.`applied_at` DESC, a.`id` DESC
             LIMIT ' . $limit,
            ['user_id' => $userId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailedRecordById(int $applicationId): ?array
    {
        if ($applicationId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT a.*,
                    u.`full_name` AS `applicant_name`,
                    u.`email`,
                    j.`title` AS `job_title`,
                    j.`status` AS `job_status`,
                    c.`name` AS `country_name`,
                    r.`title` AS `resume_title`
             FROM `applications` a
             INNER JOIN `users` u ON u.`id` = a.`user_id`
             INNER JOIN `jobs` j ON j.`id` = a.`job_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `resumes` r ON r.`id` = a.`resume_id` AND r.`deleted_at` IS NULL
             WHERE a.`id` = :id
             LIMIT 1',
            ['id' => $applicationId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByJobAndUser(int $jobId, int $userId): ?array
    {
        if ($jobId < 1 || $userId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `applications`
             WHERE `job_id` = :job_id AND `user_id` = :user_id
             LIMIT 1',
            ['job_id' => $jobId, 'user_id' => $userId]
        );
    }

    public function create(int $jobId, int $userId, ?int $resumeId = null, string $status = 'submitted'): int
    {
        if ($jobId < 1 || $userId < 1) {
            throw new \InvalidArgumentException('Invalid application identifiers.');
        }

        $existing = $this->findByJobAndUser($jobId, $userId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $allowed = ['submitted', 'reviewing', 'shortlisted', 'rejected', 'hired', 'withdrawn'];
        if (!in_array($status, $allowed, true)) {
            $status = 'submitted';
        }

        return $this->insertApplication([
            'job_id' => $jobId,
            'user_id' => $userId,
            'resume_id' => $resumeId,
            'cover_letter' => null,
            'status' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function insertApplication(array $data): int
    {
        $this->query(
            'INSERT INTO `applications`
                (`job_id`, `user_id`, `resume_id`, `cover_letter`, `status`, `applied_at`, `status_updated_at`)
             VALUES
                (:job_id, :user_id, :resume_id, :cover_letter, :status, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
            [
                'job_id' => (int) ($data['job_id'] ?? 0),
                'user_id' => (int) ($data['user_id'] ?? 0),
                'resume_id' => isset($data['resume_id']) && (int) $data['resume_id'] > 0 ? (int) $data['resume_id'] : null,
                'cover_letter' => $data['cover_letter'] ?? null,
                'status' => (string) ($data['status'] ?? 'submitted'),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function reopenApplication(int $applicationId, int $resumeId, ?string $coverLetter): bool
    {
        if ($applicationId < 1) {
            return false;
        }

        $this->query(
            'UPDATE `applications`
             SET `status` = \'submitted\',
                 `resume_id` = :resume_id,
                 `cover_letter` = :cover_letter,
                 `employer_notes` = NULL,
                 `applied_at` = CURRENT_TIMESTAMP(3),
                 `status_updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            [
                'id' => $applicationId,
                'resume_id' => $resumeId > 0 ? $resumeId : null,
                'cover_letter' => $coverLetter,
            ]
        );

        return true;
    }

    public function updateApplicationStatus(int $applicationId, string $status, ?string $employerNotes): bool
    {
        if ($applicationId < 1) {
            return false;
        }

        $this->query(
            'UPDATE `applications`
             SET `status` = :status,
                 `employer_notes` = :employer_notes,
                 `status_updated_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id',
            [
                'id' => $applicationId,
                'status' => $status,
                'employer_notes' => $employerNotes,
            ]
        );

        return true;
    }

    public function insertStatusHistory(
        int $applicationId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void {
        if ($applicationId < 1 || $toStatus === '') {
            return;
        }

        $this->query(
            'INSERT INTO `application_status_history`
                (`application_id`, `from_status`, `to_status`, `actor_user_id`, `note`)
             VALUES
                (:application_id, :from_status, :to_status, :actor_user_id, :note)',
            [
                'application_id' => $applicationId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null,
                'note' => $note !== null ? mb_substr($note, 0, 500) : null,
            ]
        );
    }

    public function incrementJobApplicationsCount(int $jobId): void
    {
        if ($jobId < 1) {
            return;
        }

        $this->query(
            'UPDATE `jobs`
             SET `applications_count` = `applications_count` + 1
             WHERE `id` = :id',
            ['id' => $jobId]
        );
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

    public function countHiredByJobId(int $jobId): int
    {
        if ($jobId < 1) {
            return 0;
        }
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS `cnt` FROM `applications`
             WHERE `job_id` = :job_id AND `status` = \'hired\'',
            ['job_id' => $jobId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return $this->rowExists($id);
    }
}
