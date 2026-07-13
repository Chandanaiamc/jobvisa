<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumeIntelligenceHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(int $resumeId, array $payload): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByResumeId(int $resumeId, int $limit = 25): array;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $resumeId): ?array;

    public function softDelete(int $id, int $resumeId): bool;

    public function softDeleteAllForResume(int $resumeId): int;
}
