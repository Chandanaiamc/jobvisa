<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds common skills for CV and job matching.
 */
final class SkillSeeder extends Seeder
{
    public function name(): string
    {
        return 'Skills';
    }

    public function run(): void
    {
        if (!$this->tableExists('skills')) {
            return;
        }

        $now = $this->now();

        $skills = [
            ['name' => 'English Communication', 'slug' => 'english-communication'],
            ['name' => 'Customer Service', 'slug' => 'customer-service'],
            ['name' => 'Nursing', 'slug' => 'nursing'],
            ['name' => 'Caregiving', 'slug' => 'caregiving'],
            ['name' => 'Cooking', 'slug' => 'cooking'],
            ['name' => 'Housekeeping', 'slug' => 'housekeeping'],
            ['name' => 'Welding', 'slug' => 'welding'],
            ['name' => 'Electrical Work', 'slug' => 'electrical-work'],
            ['name' => 'Plumbing', 'slug' => 'plumbing'],
            ['name' => 'Heavy Vehicle Driving', 'slug' => 'heavy-vehicle-driving'],
            ['name' => 'Warehouse Operations', 'slug' => 'warehouse-operations'],
            ['name' => 'Software Development', 'slug' => 'software-development'],
            ['name' => 'Project Management', 'slug' => 'project-management'],
            ['name' => 'Accounting', 'slug' => 'accounting'],
            ['name' => 'Teaching', 'slug' => 'teaching'],
        ];

        foreach ($skills as $skill) {
            $this->upsertByUnique(
                'skills',
                ['slug' => $skill['slug']],
                [
                    'name' => $skill['name'],
                    'slug' => $skill['slug'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'is_active', 'updated_at']
            );
        }
    }
}
