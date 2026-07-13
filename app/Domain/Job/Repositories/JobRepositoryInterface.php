<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Job\Entities\Job;

/**
 * Persistence contract for the Job aggregate.
 *
 * @extends RepositoryInterface<Job>
 */
interface JobRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Job;
}
