<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface EducationRepositoryInterface
{
    /** @return list<array<string, mixed>> Active (non-deleted) records */
    public function listByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> Soft-deleted records */
    public function listDeletedByResumeId(int $resumeId): array;

    /** @return array<string, mixed>|null Active owned row */
    public function findOwned(int $id, int $resumeId): ?array;

    /** @return array<string, mixed>|null Soft-deleted owned row */
    public function findDeletedOwned(int $id, int $resumeId): ?array;

    /** @param array<string, mixed> $data */
    public function create(int $resumeId, array $data): int;

    /** @param array<string, mixed> $data */
    public function update(int $id, int $resumeId, array $data): bool;

    /** Soft-delete */
    public function delete(int $id, int $resumeId): bool;

    public function restore(int $id, int $resumeId): bool;

    /** Clear is_current on other active rows for this resume. */
    public function clearCurrentExcept(int $resumeId, ?int $exceptId = null): void;

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(int $resumeId, array $orderedIds): void;

    public function countryExists(int $countryId): bool;
}
