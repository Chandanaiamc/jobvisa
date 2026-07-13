<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface RecruiterCandidateSearchRepositoryInterface
{
    /**
     * Search applicants across employer-owned jobs with structured filters.
     *
     * @param  list<int>  $jobIds
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function search(array $jobIds, array $filters, int $limit = 25): array;
}
