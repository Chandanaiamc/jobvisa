<?php

declare(strict_types=1);

namespace JobVisa\App\Support;

/**
 * Shared pagination math for list endpoints.
 */
final class Paginator
{
    /**
     * @return array{page: int, per_page: int, offset: int, last_page: int, total: int}
     */
    public static function resolve(int $total, ?int $page = null, ?int $perPage = null): array
    {
        $defaultPer = (int) config('performance.default_per_page', 15);
        $maxPer = (int) config('performance.max_per_page', 50);
        $perPage = $perPage ?? $defaultPer;
        $perPage = max(1, min($maxPer, $perPage));
        $total = max(0, $total);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = $page ?? 1;
        $page = max(1, min($lastPage, $page));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset,
            'last_page' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * @param  array{page: int, per_page: int, last_page: int, total: int}  $meta
     * @return array{total: int, page: int, per_page: int, last_page: int}
     */
    public static function meta(array $meta): array
    {
        return [
            'total' => (int) ($meta['total'] ?? 0),
            'page' => (int) ($meta['page'] ?? 1),
            'per_page' => (int) ($meta['per_page'] ?? 15),
            'last_page' => (int) ($meta['last_page'] ?? 1),
        ];
    }
}
