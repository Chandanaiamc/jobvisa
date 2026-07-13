<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds top-level job categories for overseas placements.
 */
final class JobCategorySeeder extends Seeder
{
    public function name(): string
    {
        return 'Job Categories';
    }

    public function run(): void
    {
        if (!$this->tableExists('job_categories')) {
            return;
        }

        $now = $this->now();

        $categories = [
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Nurses, caregivers, medical support', 'sort_order' => 10],
            ['name' => 'Hospitality', 'slug' => 'hospitality', 'description' => 'Hotels, restaurants, tourism', 'sort_order' => 20],
            ['name' => 'Construction', 'slug' => 'construction', 'description' => 'Building trades and site work', 'sort_order' => 30],
            ['name' => 'Information Technology', 'slug' => 'information-technology', 'description' => 'Software and IT services', 'sort_order' => 40],
            ['name' => 'Engineering', 'slug' => 'engineering', 'description' => 'Mechanical, civil, electrical', 'sort_order' => 50],
            ['name' => 'Logistics', 'slug' => 'logistics', 'description' => 'Warehousing, driving, supply chain', 'sort_order' => 60],
            ['name' => 'Domestic & Care', 'slug' => 'domestic-care', 'description' => 'Household and personal care roles', 'sort_order' => 70],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Teaching and training roles', 'sort_order' => 80],
            ['name' => 'Manufacturing', 'slug' => 'manufacturing', 'description' => 'Factory and production roles', 'sort_order' => 90],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'General / uncategorized roles', 'sort_order' => 100],
        ];

        foreach ($categories as $category) {
            $this->upsertByUnique(
                'job_categories',
                ['slug' => $category['slug']],
                [
                    'parent_id' => null,
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'is_active' => 1,
                    'sort_order' => $category['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'description', 'is_active', 'sort_order', 'updated_at']
            );
        }
    }
}
