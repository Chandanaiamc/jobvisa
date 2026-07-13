<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use PDO;

final class LocationRepository extends BaseRepository implements LocationRepositoryInterface
{
    protected string $table = 'countries';

    public function __construct(
        PDO $pdo,
        private readonly ?CacheInterface $cache = null,
    ) {
        parent::__construct($pdo);
    }

    public function listCountries(): array
    {
        $loader = fn (): array => $this->fetchAll(
            'SELECT `id`, `name`, `iso2` FROM `countries` WHERE `is_active` = 1 ORDER BY `name` ASC'
        );

        if ($this->cache === null) {
            return $loader();
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->cache->remember(
            'catalog.countries.active',
            (int) config('performance.catalog_cache_ttl', 3600),
            $loader
        );

        return is_array($rows) ? $rows : [];
    }

    public function listCities(?int $countryId = null): array
    {
        if ($countryId !== null && $countryId > 0) {
            $key = 'catalog.cities.country.' . $countryId;
            $loader = fn (): array => $this->fetchAll(
                'SELECT `id`, `country_id`, `name` FROM `cities`
                 WHERE `is_active` = 1 AND `country_id` = :country_id
                 ORDER BY `name` ASC',
                ['country_id' => $countryId]
            );

            if ($this->cache === null) {
                return $loader();
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = $this->cache->remember(
                $key,
                (int) config('performance.catalog_cache_ttl', 3600),
                $loader
            );

            return is_array($rows) ? $rows : [];
        }

        return $this->fetchAll(
            'SELECT `id`, `country_id`, `name` FROM `cities` WHERE `is_active` = 1 ORDER BY `name` ASC'
        );
    }

    public function countryExists(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `countries` WHERE `id` = :id AND `is_active` = 1',
            ['id' => $countryId]
        )->fetchColumn() > 0;
    }

    public function cityBelongsToCountry(int $cityId, int $countryId): bool
    {
        if ($cityId < 1 || $countryId < 1) {
            return false;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `cities`
             WHERE `id` = :city_id AND `country_id` = :country_id AND `is_active` = 1',
            ['city_id' => $cityId, 'country_id' => $countryId]
        )->fetchColumn() > 0;
    }
}
