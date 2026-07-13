<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface InterviewSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): int;

    /** @return array<string, mixed>|null */
    public function findOwned(int $sessionId, int $employerUserId): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByEmployer(int $employerUserId, int $limit = 25): array;

    /**
     * Applicants for a job with ranking/match context for interview prep.
     *
     * @return list<array<string, mixed>>
     */
    public function listCandidatesForJob(int $jobId, int $limit = 100): array;

    public function updateStatus(int $sessionId, int $employerUserId, string $status): bool;

    public function softDelete(int $sessionId, int $employerUserId): bool;

    public function softDeleteAllForEmployer(int $employerUserId): int;
}
