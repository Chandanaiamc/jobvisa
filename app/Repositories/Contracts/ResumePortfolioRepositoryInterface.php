<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumePortfolioRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listByResumeId(int $resumeId, array $filters = [], int $page = 1, int $perPage = 10): array;

    /** @return list<array<string, mixed>> */
    public function listPublicByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> */
    public function listForEmployerByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> */
    public function listDeletedByResumeId(int $resumeId): array;

    /** @return list<array<string, mixed>> */
    public function search(int $resumeId, string $query, int $limit = 20): array;

    /** @return array<string, mixed>|null */
    public function findOwned(int $id, int $resumeId): ?array;

    /** @return array<string, mixed>|null */
    public function findDeletedOwned(int $id, int $resumeId): ?array;

    /** @return array<string, mixed>|null */
    public function findDuplicate(int $resumeId, string $title, string $category, ?int $exceptId = null): ?array;

    /** @param array<string, mixed> $data */
    public function create(int $resumeId, array $data): int;

    /** @param array<string, mixed> $data */
    public function update(int $id, int $resumeId, array $data): bool;

    public function delete(int $id, int $resumeId): bool;

    public function restore(int $id, int $resumeId): bool;

    /**
     * @param  array{
     *   path: ?string,
     *   original_name: ?string,
     *   mime: ?string,
     *   size: ?int
     * }  $meta
     */
    public function updateFeaturedImageMeta(int $id, int $resumeId, array $meta): bool;

    /** @param list<int> $orderedIds */
    public function reorder(int $resumeId, array $orderedIds): void;

    public function countActive(int $resumeId): int;

    /** @return list<array<string, mixed>> */
    public function listGallery(int $portfolioId): array;

    /**
     * @param  array{
     *   image_path: string,
     *   original_name: ?string,
     *   mime: ?string,
     *   file_size: ?int,
     *   sort_order?: int
     * }  $data
     */
    public function addGalleryImage(int $portfolioId, array $data): int;

    public function softDeleteGalleryImage(int $imageId, int $portfolioId): bool;

    /** @return array<string, mixed>|null */
    public function findGalleryOwned(int $imageId, int $portfolioId, int $resumeId): ?array;

    public function countGallery(int $portfolioId): int;
}
