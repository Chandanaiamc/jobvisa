<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface AiResumeBuilderHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(int $resumeId, int $userId, array $payload): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByResumeId(int $resumeId, int $limit = 25): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listDeletedByResumeId(int $resumeId, int $limit = 25): array;

    public function softDelete(int $id, int $resumeId): bool;

    public function restore(int $id, int $resumeId): bool;

    public function softDeleteAllForResume(int $resumeId): int;
}
