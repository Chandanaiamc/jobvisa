<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumePersonalRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByResumeId(int $resumeId): ?array;

    /** @param array<string, mixed> $data */
    public function upsert(int $resumeId, array $data): void;

    /** @return list<int> */
    public function listPreferredCountryIds(int $resumeId): array;

    /** @param list<int> $countryIds */
    public function syncPreferredCountries(int $resumeId, array $countryIds): void;

    public function countryExists(int $countryId): bool;
}
