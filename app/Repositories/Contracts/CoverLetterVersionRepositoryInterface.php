<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface CoverLetterVersionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(int $resumeId, int $userId, array $payload): int;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $resumeId): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByResumeId(int $resumeId, int $limit = 20): array;

    public function markSaved(int $id, int $resumeId): bool;

    public function softDelete(int $id, int $resumeId): bool;
}
