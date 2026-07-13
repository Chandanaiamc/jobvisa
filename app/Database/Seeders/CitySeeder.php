<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds major cities keyed by country ISO2.
 */
final class CitySeeder extends Seeder
{
    public function name(): string
    {
        return 'Cities';
    }

    public function run(): void
    {
        if (!$this->tableExists('cities') || !$this->tableExists('countries')) {
            return;
        }

        $now = $this->now();

        $cities = [
            'LK' => [
                ['name' => 'Colombo', 'slug' => 'colombo'],
                ['name' => 'Kandy', 'slug' => 'kandy'],
                ['name' => 'Galle', 'slug' => 'galle'],
                ['name' => 'Jaffna', 'slug' => 'jaffna'],
            ],
            'AE' => [
                ['name' => 'Dubai', 'slug' => 'dubai'],
                ['name' => 'Abu Dhabi', 'slug' => 'abu-dhabi'],
                ['name' => 'Sharjah', 'slug' => 'sharjah'],
            ],
            'SA' => [
                ['name' => 'Riyadh', 'slug' => 'riyadh'],
                ['name' => 'Jeddah', 'slug' => 'jeddah'],
                ['name' => 'Dammam', 'slug' => 'dammam'],
            ],
            'QA' => [
                ['name' => 'Doha', 'slug' => 'doha'],
            ],
            'KW' => [
                ['name' => 'Kuwait City', 'slug' => 'kuwait-city'],
            ],
            'OM' => [
                ['name' => 'Muscat', 'slug' => 'muscat'],
            ],
            'BH' => [
                ['name' => 'Manama', 'slug' => 'manama'],
            ],
            'MY' => [
                ['name' => 'Kuala Lumpur', 'slug' => 'kuala-lumpur'],
            ],
            'SG' => [
                ['name' => 'Singapore', 'slug' => 'singapore'],
            ],
            'JP' => [
                ['name' => 'Tokyo', 'slug' => 'tokyo'],
            ],
            'KR' => [
                ['name' => 'Seoul', 'slug' => 'seoul'],
            ],
            'AU' => [
                ['name' => 'Sydney', 'slug' => 'sydney'],
                ['name' => 'Melbourne', 'slug' => 'melbourne'],
            ],
        ];

        foreach ($cities as $iso2 => $cityRows) {
            $countryId = $this->findIdBy('countries', 'iso2', $iso2);

            if ($countryId === null) {
                continue;
            }

            foreach ($cityRows as $city) {
                $this->upsertByUnique(
                    'cities',
                    ['country_id' => $countryId, 'slug' => $city['slug']],
                    [
                        'country_id' => $countryId,
                        'name' => $city['name'],
                        'slug' => $city['slug'],
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['name', 'is_active', 'updated_at']
                );
            }
        }
    }
}
