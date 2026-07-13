<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure contract for company persistence.
 */
interface CompanyRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array;
}
