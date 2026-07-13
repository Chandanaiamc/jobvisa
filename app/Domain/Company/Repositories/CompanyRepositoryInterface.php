<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Company\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Company\Entities\Company;

/**
 * Persistence contract for the Company aggregate.
 *
 * @extends RepositoryInterface<Company>
 */
interface CompanyRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Company;
}
