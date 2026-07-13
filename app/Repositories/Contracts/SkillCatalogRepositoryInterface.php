<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface SkillCatalogRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listActive(): array;

    /**
     * Autocomplete search against active catalogue.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 15): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    public function findOrCreateCustom(string $name): int;

    public function isActive(int $skillId): bool;
}
