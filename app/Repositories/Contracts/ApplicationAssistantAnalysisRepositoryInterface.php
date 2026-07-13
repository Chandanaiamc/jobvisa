<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ApplicationAssistantAnalysisRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(int $userId, int $jobId, int $resumeId, array $payload): int;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $userId, int $jobId): ?array;

    /** @return array<string, mixed>|null */
    public function findLatestForUserJob(int $userId, int $jobId, ?int $resumeId = null): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByUserJob(int $userId, int $jobId, int $limit = 20): array;

    public function softDelete(int $id, int $userId, int $jobId): bool;
}
