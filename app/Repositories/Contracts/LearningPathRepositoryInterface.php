<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface LearningPathRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(int $resumeId, int $userId, array $payload): int;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $resumeId, int $userId): ?array;

    /** @return array<string, mixed>|null */
    public function findLatestByResumeId(int $resumeId, int $userId): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByResumeId(int $resumeId, int $userId, int $limit = 20): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProgress(int $id, int $resumeId, int $userId, array $payload): bool;
}
