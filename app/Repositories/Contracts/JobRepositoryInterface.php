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
}
