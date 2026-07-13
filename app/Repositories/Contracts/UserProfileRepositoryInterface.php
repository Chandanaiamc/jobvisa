<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface UserProfileRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByUserId(int $userId): ?array;

    /** @param array<string, mixed> $data */
    public function upsertForUser(int $userId, array $data): void;

    public function updateAvatar(int $userId, ?string $path): void;
}
