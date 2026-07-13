<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\User\Repositories;

use JobVisa\App\Domain\Contracts\RepositoryInterface;
use JobVisa\App\Domain\User\Entities\User;

/**
 * Persistence contract for the User aggregate.
 *
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findById(int|string $id): ?User;
}
