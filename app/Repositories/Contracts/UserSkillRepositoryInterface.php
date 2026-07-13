<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface UserSkillRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listByUserId(int $userId): array;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $userId): ?array;

    public function attach(int $userId, int $skillId, string $level): int;

    public function updateLevel(int $id, int $userId, string $level): bool;

    public function detach(int $id, int $userId): bool;
}
