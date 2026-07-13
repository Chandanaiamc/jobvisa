<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumeJobMatchRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByResumeAndJob(int $resumeId, int $jobId): ?array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(int $resumeId, int $jobId, array $payload): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function listTopForResume(int $resumeId, int $limit = 20): array;

    /**
     * Active match snapshots for a set of jobs.
     *
     * @param  list<int>  $jobIds
     * @return list<array<string, mixed>>
     */
    public function listActiveByJobIds(array $jobIds, int $limit = 500): array;

    public function softDelete(int $resumeId, int $jobId): bool;
}
