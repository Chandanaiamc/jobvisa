<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface LocationRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listCountries(): array;

    /** @return list<array<string, mixed>> */
    public function listCities(?int $countryId = null): array;

    public function countryExists(int $countryId): bool;

    public function cityBelongsToCountry(int $cityId, int $countryId): bool;
}
