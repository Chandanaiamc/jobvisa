<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds a demo job seeker user and profile.
 */
final class DemoJobSeekerSeeder extends Seeder
{
    public function name(): string
    {
        return 'Demo Job Seeker';
    }

    public function run(): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        /** @var array<string, mixed> $demo */
        $demo = config('seeders.demo', []);
        $email = (string) ($demo['seeker_email'] ?? 'seeker@demo.jobvisa.lk');
        $password = (string) ($demo['seeker_password'] ?? 'ChangeMeSeeker!123');
        $name = (string) ($demo['seeker_name'] ?? 'Demo Job Seeker');
        $now = $this->now();

        $userId = $this->findIdBy('users', 'email', $email);

        if ($userId === null) {
            $hasher = new PasswordHasher();
            $roleId = $this->tableExists('roles') ? $this->findIdBy('roles', 'slug', 'seeker') : null;

            $columns = [
                'email' => $email,
                'password_hash' => $hasher->hash($password),
                'full_name' => $name,
                'phone' => '+94770000002',
                'role' => 'seeker',
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
            $userId = (int) $this->pdo->lastInsertId();
        }

        if (!$this->tableExists('user_profiles')) {
            return;
        }

        $profileCheck = $this->pdo->prepare(
            'SELECT `id` FROM `user_profiles` WHERE `user_id` = :user_id LIMIT 1'
        );
        $profileCheck->execute(['user_id' => $userId]);

        if ($profileCheck->fetchColumn() !== false) {
            return;
        }

        $nationalityId = $this->findIdBy('countries', 'iso2', 'LK');
        $preferredId = $this->findIdBy('countries', 'iso2', 'AE');
        $cityId = null;

        if ($nationalityId !== null) {
            $cityStmt = $this->pdo->prepare(
                'SELECT `id` FROM `cities` WHERE `country_id` = :country_id AND `slug` = :slug LIMIT 1'
            );
            $cityStmt->execute(['country_id' => $nationalityId, 'slug' => 'colombo']);
            $fetched = $cityStmt->fetchColumn();
            $cityId = $fetched === false ? null : (int) $fetched;
        }

        $this->pdo->prepare(
            'INSERT INTO `user_profiles`
                (`user_id`, `headline`, `summary`, `nationality_country_id`, `current_city_id`,
                 `preferred_country_id`, `visibility`, `created_at`, `updated_at`)
             VALUES
                (:user_id, :headline, :summary, :nationality_country_id, :current_city_id,
                 :preferred_country_id, :visibility, :created_at, :updated_at)'
        )->execute([
            'user_id' => $userId,
            'headline' => 'Experienced overseas job seeker (demo)',
            'summary' => 'Demo profile used for local development and QA of JobVisa.lk.',
            'nationality_country_id' => $nationalityId,
            'current_city_id' => $cityId,
            'preferred_country_id' => $preferredId,
            'visibility' => 'employers',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
