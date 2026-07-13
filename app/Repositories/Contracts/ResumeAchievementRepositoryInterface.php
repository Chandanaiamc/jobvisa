<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumeAchievementRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listByResumeId(int $resumeId, ?string $query = null): array;

    /** @return list<array<string, mixed>> */
    public function listPublicByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> */
    public function listDeletedByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> */
    public function search(int $resumeId, string $query, int $limit = 20): array;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $resumeId): ?array;

    /** @return array<string, mixed>|null */
    public function findDeletedOwned(int $id, int $resumeId): ?array;

    /** @param array<string, mixed> $data */
    public function create(int $resumeId, array $data): int;

    /** @param array<string, mixed> $data */
    public function update(int $id, int $resumeId, array $data): bool;

    public function delete(int $id, int $resumeId): bool;

    public function restore(int $id, int $resumeId): bool;

    /**
     * @param  array{
     *   path: ?string,
     *   original_name: ?string,
     *   mime: ?string,
     *   size: ?int
     * }  $meta
     */
    public function updateCertificateMeta(int $id, int $resumeId, array $meta): bool;

    /** @deprecated Use updateCertificateMeta */
    public function updateCertificatePath(int $id, int $resumeId, ?string $path): bool;

    /** @param list<int> $orderedIds */
    public function reorder(int $resumeId, array $orderedIds): void;

    public function countActive(int $resumeId): int;
}
