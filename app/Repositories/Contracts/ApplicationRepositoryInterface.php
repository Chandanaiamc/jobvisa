<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure contract for job application persistence.
 */
interface ApplicationRepositoryInterface extends RepositoryInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function findByJobId(int $jobId, int $limit = 100): array;

    /**
     * Applications with applicant identity + resume title for ranking.
     *
     * @return list<array<string, mixed>>
     */
    public function findDetailedByJobId(int $jobId, int $limit = 200): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function findByUserId(int $userId, int $limit = 100): array;

    /**
     * Seeker list with job/resume labels.
     *
     * @return list<array<string, mixed>>
     */
    public function findDetailedByUserId(int $userId, int $limit = 100): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailedRecordById(int $applicationId): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByJobAndUser(int $jobId, int $userId): ?array;

    /**
     * Insert application if (job_id, user_id) pair does not exist.
     * Legacy helper — prefer insertApplication via ApplicationService for Phase 1 flows.
     *
     * @return int application id
     */
    public function create(int $jobId, int $userId, ?int $resumeId = null, string $status = 'submitted'): int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function insertApplication(array $data): int;

    public function reopenApplication(int $applicationId, int $resumeId, ?string $coverLetter): bool;

    public function updateApplicationStatus(int $applicationId, string $status, ?string $employerNotes): bool;

    public function insertStatusHistory(
        int $applicationId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void;

    public function incrementJobApplicationsCount(int $jobId): void;

    /**
     * @param  list<int>  $jobIds
     * @return array<string, int> status => count
     */
    public function countByStatusForJobIds(array $jobIds): array;

    public function countHiredByJobId(int $jobId): int;
}
