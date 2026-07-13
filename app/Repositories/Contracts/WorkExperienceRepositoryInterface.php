<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface WorkExperienceRepositoryInterface
{
    /** @return list<array<string, mixed>> Active (non-deleted) records */
    public function listByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> Soft-deleted records */
    public function listDeletedByResumeId(int $resumeId): array;

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
     * @param  list<int>  $orderedIds
     */
    public function reorder(int $resumeId, array $orderedIds): void;

    public function countryExists(int $countryId): bool;

    /** @return list<int> */
    public function listSkillIds(int $experienceId): array;

    /**
     * @param  list<int>  $skillIds
     */
    public function syncSkills(int $experienceId, array $skillIds): void;

    /**
     * @param  list<int>  $experienceIds
     * @return array<int, list<array{id: int, name: string}>>
     */
    public function mapSkillsForExperiences(array $experienceIds): array;

    /** @return list<int> Active skill ids from catalogue */
    public function filterActiveSkillIds(array $skillIds): array;
}
