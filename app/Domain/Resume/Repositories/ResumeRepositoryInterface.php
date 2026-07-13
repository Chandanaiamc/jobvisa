<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Entities\Resume;

/**
 * Persistence contract for the Resume aggregate.
 *
 * @extends RepositoryInterface<Resume>
 */
interface ResumeRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Resume;

    public function findAggregateById(int $id): ?ResumeAggregate;

    public function findOwnedAggregate(int $id, int $userId): ?ResumeAggregate;

    /**
     * @return list<Resume>
     */
    public function listActiveForUser(int $userId): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listActiveRecordsForUser(int $userId): array;

    public function saveAggregate(ResumeAggregate $aggregate): ResumeAggregate;

    public function softDeleteAggregate(ResumeAggregate $aggregate): void;

    public function clearDefaultForUser(int $userId, ?int $exceptResumeId = null): void;
}
