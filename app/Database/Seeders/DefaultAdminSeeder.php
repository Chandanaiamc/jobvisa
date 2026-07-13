<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds the default platform administrator (idempotent by email).
 */
final class DefaultAdminSeeder extends Seeder
{
    public function name(): string
    {
        return 'Default Admin';
    }

    public function run(): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        /** @var array<string, mixed> $demo */
        $demo = config('seeders.demo', []);
        $email = (string) ($demo['admin_email'] ?? 'admin@jobvisa.lk');
        $password = (string) ($demo['admin_password'] ?? 'ChangeMeAdmin!123');
        $name = (string) ($demo['admin_name'] ?? 'System Administrator');

        if ($this->findIdBy('users', 'email', $email) !== null) {
            return;
        }

        $now = $this->now();
        $hasher = new PasswordHasher();
        $roleId = $this->tableExists('roles') ? $this->findIdBy('roles', 'slug', 'admin') : null;

        $columns = [
            'email' => $email,
            'password_hash' => $hasher->hash($password),
            'full_name' => $name,
            'phone' => null,
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($this->columnExists('users', 'role_id') && $roleId !== null) {
            $columns['role_id'] = $roleId;
        }

        $keys = array_keys($columns);
        $sql = sprintf(
            'INSERT INTO `users` (`%s`) VALUES (%s)',
            implode('`, `', $keys),
            implode(', ', array_map(static fn (string $k): string => ':' . $k, $keys))
        );

        $this->pdo->prepare($sql)->execute($columns);
    }
}
