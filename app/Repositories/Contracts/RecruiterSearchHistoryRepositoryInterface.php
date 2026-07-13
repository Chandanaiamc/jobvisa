<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface RecruiterSearchHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(int $employerUserId, array $payload): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listByEmployer(int $employerUserId, int $limit = 20): array;

    public function softDelete(int $id, int $employerUserId): bool;

    public function softDeleteAllForEmployer(int $employerUserId): int;
}
