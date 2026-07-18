<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure contract for job persistence.
 */
interface JobRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublished(int $limit = 50): array;

    /**
     * Search published jobs with filters and pagination.
     *
     * @param  array{
     *   q?: string,
     *   country_id?: int,
     *   job_type_id?: int,
     *   page?: int,
     *   per_page?: int
     * }  $filters
     * @return array{jobs: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function searchPublished(array $filters = []): array;

    /**
     * Active job types for public filter dropdowns.
     *
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function listActiveJobTypes(): array;

    /**
     * Published job with location / type labels for matching.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedRecordById(int $id): ?array;

    /**
     * Job owned by employer user (includes employer_user_id).
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedByEmployerUser(int $jobId, int $employerUserId): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedByEmployerUser(int $employerUserId, int $limit = 50): array;

    /**
     * Employer profile row for a user (`employers.id`, `company_id`).
     *
     * @return array{id: int, company_id: int, user_id: int}|null
     */
    public function findEmployerProfileByUserId(int $userId): ?array;

    public function slugExists(string $slug, ?int $exceptJobId = null): bool;

    public function jobCategoryExists(int $id): bool;

    public function jobTypeExists(int $id): bool;

    public function countryExists(int $id): bool;

    public function cityExists(int $id): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function insertJob(array $data): int;

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateJobById(int $jobId, array $fields): bool;
}
