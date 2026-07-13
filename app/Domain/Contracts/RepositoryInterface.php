<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Persistence boundary for a domain aggregate.
 *
 * Implementations live outside this foundation (e.g. infrastructure).
 *
 * @template T of object
 */
interface RepositoryInterface
{
    /**
     * @return T|null
     */
    public function findById(int|string $id): ?object;
}
