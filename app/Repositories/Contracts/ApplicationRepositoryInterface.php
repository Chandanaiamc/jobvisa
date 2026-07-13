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
     * Insert application if (job_id, user_id) pair does not exist.
     *
     * @return int application id
     */
    public function create(int $jobId, int $userId, ?int $resumeId = null, string $status = 'submitted'): int;

    /**
     * @param  list<int>  $jobIds
     * @return array<string, int> status => count
     */
    public function countByStatusForJobIds(array $jobIds): array;
}
