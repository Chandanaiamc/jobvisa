<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure repository contract for row-oriented reads.
 *
 * Domain aggregate contracts remain under JobVisa\App\Domain\*\Repositories.
 * Implementations may satisfy both layers without changing Auth repositories.
 */
interface RepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findRecordById(int|string $id): ?array;

    public function exists(int|string $id): bool;
}
