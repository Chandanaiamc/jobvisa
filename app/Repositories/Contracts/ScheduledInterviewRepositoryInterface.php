<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ScheduledInterviewRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailedById(int $id): ?array;

    /**
     * Active = proposed|confirmed.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByApplicationId(int $applicationId): ?array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEmployerUser(int $employerUserId, int $limit = 100): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForCandidateUser(int $candidateUserId, int $limit = 100): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function insert(array $data): int;

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateById(int $id, array $fields): bool;

    public function insertHistory(
        int $interviewId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void;
}
