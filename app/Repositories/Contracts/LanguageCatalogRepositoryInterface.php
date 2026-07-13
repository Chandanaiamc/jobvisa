<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface LanguageCatalogRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listActive(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 15): array;

    public function isActive(int $languageId): bool;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;
}
