<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds a demo employer user, company, and employer profile.
 */
final class DemoEmployerSeeder extends Seeder
{
    public function name(): string
    {
        return 'Demo Employer';
    }

    public function run(): void
    {
        if (
            !$this->tableExists('users')
            || !$this->tableExists('companies')
            || !$this->tableExists('employers')
        ) {
            return;
        }

        /** @var array<string, mixed> $demo */
        $demo = config('seeders.demo', []);
        $email = (string) ($demo['employer_email'] ?? 'employer@demo.jobvisa.lk');
        $password = (string) ($demo['employer_password'] ?? 'ChangeMeEmployer!123');
        $name = (string) ($demo['employer_name'] ?? 'Demo Employer');
        $now = $this->now();

        $userId = $this->findIdBy('users', 'email', $email);

        if ($userId === null) {
            $hasher = new PasswordHasher();
            $roleId = $this->tableExists('roles') ? $this->findIdBy('roles', 'slug', 'employer') : null;

            $columns = [
                'email' => $email,
                'password_hash' => $hasher->hash($password),
                'full_name' => $name,
                'phone' => '+94770000001',
                'role' => 'employer',
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

        $countryId = $this->findIdBy('countries', 'iso2', 'AE');
        $cityId = null;

        if ($countryId !== null) {
            $cityStmt = $this->pdo->prepare(
                'SELECT `id` FROM `cities` WHERE `country_id` = :country_id AND `slug` = :slug LIMIT 1'
            );
            $cityStmt->execute(['country_id' => $countryId, 'slug' => 'dubai']);
            $cityId = $cityStmt->fetchColumn();
            $cityId = $cityId === false ? null : (int) $cityId;
        }

        $companySlug = 'demo-gulf-staffing';
        $companyId = $this->findIdBy('companies', 'slug', $companySlug);

        if ($companyId === null) {
            $this->pdo->prepare(
                'INSERT INTO `companies`
                    (`name`, `slug`, `registration_no`, `website`, `description`, `industry`,
                     `company_size`, `hq_country_id`, `hq_city_id`, `is_active`, `created_at`, `updated_at`)
                 VALUES
                    (:name, :slug, :registration_no, :website, :description, :industry,
                     :company_size, :hq_country_id, :hq_city_id, 1, :created_at, :updated_at)'
            )->execute([
                'name' => 'Demo Gulf Staffing LLC',
                'slug' => $companySlug,
                'registration_no' => 'DEMO-AE-0001',
                'website' => 'https://demo.jobvisa.lk',
                'description' => 'Demo employer company for local development and QA.',
                'industry' => 'Recruitment',
                'company_size' => '51-200',
                'hq_country_id' => $countryId,
                'hq_city_id' => $cityId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $companyId = (int) $this->pdo->lastInsertId();
        }

        $employerCheck = $this->pdo->prepare(
            'SELECT `id` FROM `employers` WHERE `user_id` = :user_id LIMIT 1'
        );
        $employerCheck->execute(['user_id' => $userId]);

        if ($employerCheck->fetchColumn() !== false) {
            return;
        }

        $this->pdo->prepare(
            'INSERT INTO `employers`
                (`user_id`, `company_id`, `job_title`, `verified_status`, `verified_at`,
                 `billing_email`, `created_at`, `updated_at`)
             VALUES
                (:user_id, :company_id, :job_title, :verified_status, :verified_at,
                 :billing_email, :created_at, :updated_at)'
        )->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
            'job_title' => 'Hiring Manager',
            'verified_status' => 'verified',
            'verified_at' => $now,
            'billing_email' => $email,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
