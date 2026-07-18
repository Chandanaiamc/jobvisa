<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Services;

use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;

/**
 * Public published-jobs queries for API + SSR pages.
 */
final class PublicJobsService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly LocationRepositoryInterface $locations,
    ) {
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{
     *   jobs: list<array<string, mixed>>,
     *   pagination: array<string, int>,
     *   filters_applied: array<string, mixed>
     * }
     */
    public function search(array $query): array
    {
        $filters = $this->normalizeFilters($query);
        $result = $this->jobs->searchPublished($filters);
        $totalPages = max(1, (int) ceil($result['total'] / max(1, $result['per_page'])));

        return [
            'jobs' => array_map(
                static fn (array $j): array => ApiResource::jobPublic($j, false),
                $result['jobs']
            ),
            'pagination' => [
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
                'total_pages' => $totalPages,
            ],
            'filters_applied' => [
                'q' => $filters['q'] ?? '',
                'country_id' => $filters['country_id'] ?? null,
                'job_type_id' => $filters['job_type_id'] ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $jobId): ?array
    {
        $row = $this->jobs->findPublishedRecordById($jobId);
        if ($row === null) {
            return null;
        }

        return ApiResource::jobPublic($row, true);
    }

    /**
     * @return array{countries: list<array<string, mixed>>, job_types: list<array<string, mixed>>}
     */
    public function filterOptions(): array
    {
        $countries = [];
        foreach ($this->locations->listCountries() as $row) {
            $countries[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'iso2' => (string) ($row['iso2'] ?? ''),
            ];
        }

        return [
            'countries' => $countries,
            'job_types' => $this->jobs->listActiveJobTypes(),
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{q?: string, country_id?: int, job_type_id?: int, page: int, per_page: int}
     */
    public function normalizeFilters(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        // Backward compatible: `limit` alone still means page size (legacy clients).
        $perPage = (int) ($query['per_page'] ?? $query['limit'] ?? 20);
        $perPage = max(1, min(100, $perPage));

        $out = [
            'page' => $page,
            'per_page' => $perPage,
        ];

        $q = trim((string) ($query['q'] ?? ''));
        if ($q !== '') {
            $out['q'] = mb_substr($q, 0, 120);
        }

        $countryId = (int) ($query['country_id'] ?? 0);
        if ($countryId > 0) {
            $out['country_id'] = $countryId;
        }

        $jobTypeId = (int) ($query['job_type_id'] ?? 0);
        if ($jobTypeId > 0) {
            $out['job_type_id'] = $jobTypeId;
        }

        return $out;
    }
}
