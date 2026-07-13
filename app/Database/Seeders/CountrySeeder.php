<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds origin and destination countries for overseas employment.
 */
final class CountrySeeder extends Seeder
{
    public function name(): string
    {
        return 'Countries';
    }

    public function run(): void
    {
        if (!$this->tableExists('countries')) {
            return;
        }

        $now = $this->now();

        $countries = [
            ['name' => 'Sri Lanka', 'iso2' => 'LK', 'iso3' => 'LKA', 'phone_code' => '+94', 'is_job_destination' => 0],
            ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '+971', 'is_job_destination' => 1],
            ['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '+966', 'is_job_destination' => 1],
            ['name' => 'Qatar', 'iso2' => 'QA', 'iso3' => 'QAT', 'phone_code' => '+974', 'is_job_destination' => 1],
            ['name' => 'Kuwait', 'iso2' => 'KW', 'iso3' => 'KWT', 'phone_code' => '+965', 'is_job_destination' => 1],
            ['name' => 'Oman', 'iso2' => 'OM', 'iso3' => 'OMN', 'phone_code' => '+968', 'is_job_destination' => 1],
            ['name' => 'Bahrain', 'iso2' => 'BH', 'iso3' => 'BHR', 'phone_code' => '+973', 'is_job_destination' => 1],
            ['name' => 'Malaysia', 'iso2' => 'MY', 'iso3' => 'MYS', 'phone_code' => '+60', 'is_job_destination' => 1],
            ['name' => 'Singapore', 'iso2' => 'SG', 'iso3' => 'SGP', 'phone_code' => '+65', 'is_job_destination' => 1],
            ['name' => 'Japan', 'iso2' => 'JP', 'iso3' => 'JPN', 'phone_code' => '+81', 'is_job_destination' => 1],
            ['name' => 'South Korea', 'iso2' => 'KR', 'iso3' => 'KOR', 'phone_code' => '+82', 'is_job_destination' => 1],
            ['name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS', 'phone_code' => '+61', 'is_job_destination' => 1],
        ];

        foreach ($countries as $country) {
            $this->upsertByUnique(
                'countries',
                ['iso2' => $country['iso2']],
                [
                    'name' => $country['name'],
                    'iso2' => $country['iso2'],
                    'iso3' => $country['iso3'],
                    'phone_code' => $country['phone_code'],
                    'is_job_destination' => $country['is_job_destination'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'iso3', 'phone_code', 'is_job_destination', 'is_active', 'updated_at']
            );
        }
    }
}
