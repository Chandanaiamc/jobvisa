<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Employer\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Employer\Entities\Employer;

/**
 * Persistence contract for the Employer aggregate.
 *
 * @extends RepositoryInterface<Employer>
 */
interface EmployerRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Employer;
}
