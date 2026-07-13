<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds fine-grained permissions when permissions tables exist.
 *
 * Compatible with current schema: no-op until `permissions` (and optionally
 * `role_permissions`) are created by a future migration. Does not alter schema.
 */
final class PermissionSeeder extends Seeder
{
    public function name(): string
    {
        return 'Permissions';
    }

    public function run(): void
    {
        if (!$this->tableExists('permissions')) {
            // Schema does not include permissions yet — safe skip.
            return;
        }

        $now = $this->now();

        $permissions = [
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'description' => 'Create and manage user accounts'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'description' => 'Assign roles and permissions'],
            ['name' => 'Manage Jobs', 'slug' => 'jobs.manage', 'description' => 'Moderate and manage job listings'],
            ['name' => 'Publish Jobs', 'slug' => 'jobs.publish', 'description' => 'Publish employer job listings'],
            ['name' => 'Manage Companies', 'slug' => 'companies.manage', 'description' => 'Verify and manage companies'],
            ['name' => 'Manage Applications', 'slug' => 'applications.manage', 'description' => 'Review job applications'],
            ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'description' => 'View and reconcile payments'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'description' => 'Access operational reports'],
        ];

        foreach ($permissions as $permission) {
            $this->upsertByUnique(
                'permissions',
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'description' => $permission['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'description', 'updated_at']
            );
        }

        if (!$this->tableExists('role_permissions') || !$this->tableExists('roles')) {
            return;
        }

        $adminRoleId = $this->findIdBy('roles', 'slug', 'admin');

        if ($adminRoleId === null) {
            return;
        }

        foreach ($permissions as $permission) {
            $permissionId = $this->findIdBy('permissions', 'slug', $permission['slug']);

            if ($permissionId === null) {
                continue;
            }

            $check = $this->pdo->prepare(
                'SELECT 1 FROM `role_permissions`
                 WHERE `role_id` = :role_id AND `permission_id` = :permission_id
                 LIMIT 1'
            );
            $check->execute([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ]);

            if ($check->fetchColumn()) {
                continue;
            }

            $this->pdo->prepare(
                'INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`)
                 VALUES (:role_id, :permission_id, :created_at, :updated_at)'
            )->execute([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
