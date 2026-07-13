<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds employment type taxonomy.
 */
final class JobTypeSeeder extends Seeder
{
    public function name(): string
    {
        return 'Job Types';
    }

    public function run(): void
    {
        if (!$this->tableExists('job_types')) {
            return;
        }

        $now = $this->now();

        $types = [
            ['name' => 'Full-time', 'slug' => 'full-time', 'description' => 'Permanent full-time employment'],
            ['name' => 'Part-time', 'slug' => 'part-time', 'description' => 'Part-time employment'],
            ['name' => 'Contract', 'slug' => 'contract', 'description' => 'Fixed-term contract'],
            ['name' => 'Temporary', 'slug' => 'temporary', 'description' => 'Short-term temporary work'],
            ['name' => 'Internship', 'slug' => 'internship', 'description' => 'Internship / trainee'],
        ];

        foreach ($types as $type) {
            $this->upsertByUnique(
                'job_types',
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'slug' => $type['slug'],
                    'description' => $type['description'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'description', 'is_active', 'updated_at']
            );
        }
    }
}
