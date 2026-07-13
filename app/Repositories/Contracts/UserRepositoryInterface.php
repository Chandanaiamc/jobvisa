<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure contract for user persistence (not the Auth user repository).
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array;
}
