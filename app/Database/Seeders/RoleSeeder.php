<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds system roles aligned with users.role / role_id foundation.
 */
final class RoleSeeder extends Seeder
{
    public function name(): string
    {
        return 'Roles';
    }

    public function run(): void
    {
        if (!$this->tableExists('roles')) {
            return;
        }

        $now = $this->now();

        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full platform administration', 'is_system' => 1],
            ['name' => 'Employer', 'slug' => 'employer', 'description' => 'Company hiring account', 'is_system' => 1],
            ['name' => 'Job Seeker', 'slug' => 'seeker', 'description' => 'Candidate / job seeker account', 'is_system' => 1],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Internal operations staff', 'is_system' => 1],
        ];

        foreach ($roles as $role) {
            $this->upsertByUnique(
                'roles',
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'slug' => $role['slug'],
                    'description' => $role['description'],
                    'is_system' => $role['is_system'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'description', 'is_system', 'updated_at']
            );
        }
    }
}
