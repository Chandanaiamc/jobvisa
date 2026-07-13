<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface JobApplicantRankingRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(int $jobId, int $applicationId, array $payload): void;

    /**
     * Soft-delete existing rankings for a job before a full recalculate.
     */
    public function softDeleteAllForJob(int $jobId): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByJobId(int $jobId, int $limit = 200): array;

    /**
     * Rankings across many jobs (employer dashboard aggregation).
     *
     * @param  list<int>  $jobIds
     * @return list<array<string, mixed>>
     */
    public function listByJobIds(array $jobIds, int $limit = 500): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function appendHistory(int $jobId, int $applicationId, array $payload): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listHistoryByJobId(int $jobId, int $limit = 100): array;

    public function softDeleteHistory(int $historyId, int $jobId): bool;

    public function softDeleteAllHistoryForJob(int $jobId): int;
}
