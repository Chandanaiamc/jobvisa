<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\Application\Entities\Application;

/**
 * Persistence contract for the Application aggregate.
 *
 * @extends RepositoryInterface<Application>
 */
interface ApplicationRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?Application;
}
