<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface UserLanguageRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listByUserId(int $userId): array;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $userId): ?array;

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): int;

    /** @param array<string, mixed> $data */
    public function update(int $id, int $userId, array $data): bool;

    public function delete(int $id, int $userId): bool;
}
