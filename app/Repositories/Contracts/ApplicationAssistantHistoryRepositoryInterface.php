<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ApplicationAssistantHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(int $userId, int $jobId, int $resumeId, array $payload): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByUserJob(int $userId, int $jobId, int $limit = 25): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listDeletedByUserJob(int $userId, int $jobId, int $limit = 25): array;

    public function softDelete(int $id, int $userId, int $jobId): bool;

    public function restore(int $id, int $userId, int $jobId): bool;

    public function permanentDelete(int $id, int $userId, int $jobId): bool;

    public function softDeleteAllForUserJob(int $userId, int $jobId): int;
}
