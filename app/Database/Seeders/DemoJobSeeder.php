<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;
use PDO;

/**
 * Seeds published demo job listings for the demo employer.
 */
final class DemoJobSeeder extends Seeder
{
    public function name(): string
    {
        return 'Demo Jobs';
    }

    public function run(): void
    {
        if (!$this->tableExists('jobs') || !$this->tableExists('employers') || !$this->tableExists('users')) {
            return;
        }

        /** @var array<string, mixed> $demo */
        $demo = config('seeders.demo', []);
        $employerEmail = (string) ($demo['employer_email'] ?? 'employer@demo.jobvisa.lk');
        $userId = $this->findIdBy('users', 'email', $employerEmail);

        if ($userId === null) {
            return;
        }

        $employerStmt = $this->pdo->prepare(
            'SELECT `id`, `company_id` FROM `employers` WHERE `user_id` = :user_id LIMIT 1'
        );
        $employerStmt->execute(['user_id' => $userId]);
        $employer = $employerStmt->fetch(PDO::FETCH_ASSOC);

        if ($employer === false) {
            return;
        }

        $employerId = (int) $employer['id'];
        $companyId = (int) $employer['company_id'];

        $categoryId = $this->findIdBy('job_categories', 'slug', 'healthcare')
            ?? $this->findIdBy('job_categories', 'slug', 'other');
        $jobTypeId = $this->findIdBy('job_types', 'slug', 'full-time');
        $countryId = $this->findIdBy('countries', 'iso2', 'AE');
        $hospitalityId = $this->findIdBy('job_categories', 'slug', 'hospitality');
        $constructionId = $this->findIdBy('job_categories', 'slug', 'construction');

        $cityId = null;

        if ($countryId !== null) {
            $cityStmt = $this->pdo->prepare(
                'SELECT `id` FROM `cities` WHERE `country_id` = :country_id AND `slug` = :slug LIMIT 1'
            );
            $cityStmt->execute(['country_id' => $countryId, 'slug' => 'dubai']);
            $fetched = $cityStmt->fetchColumn();
            $cityId = $fetched === false ? null : (int) $fetched;
        }

        if ($categoryId === null || $jobTypeId === null || $countryId === null) {
            return;
        }

        $now = $this->now();

        $jobs = [
            [
                'title' => 'Registered Nurse — Dubai (Demo)',
                'slug' => 'demo-registered-nurse-dubai',
                'category_id' => $categoryId,
                'description' => 'Demo listing: Registered nurse for a private hospital in Dubai. Visa sponsorship available.',
                'requirements' => 'Valid nursing license, 2+ years experience, English communication.',
                'benefits' => 'Accommodation allowance, medical insurance, annual ticket.',
                'salary_min' => '4500.00',
                'salary_max' => '6500.00',
                'salary_currency' => 'AED',
                'visa_sponsorship' => 1,
            ],
            [
                'title' => 'Hotel Front Desk Officer — Dubai (Demo)',
                'slug' => 'demo-hotel-front-desk-dubai',
                'category_id' => $hospitalityId ?? $categoryId,
                'description' => 'Demo listing: Front desk officer for a mid-scale hotel in Dubai.',
                'requirements' => 'Hospitality diploma preferred, customer service experience.',
                'benefits' => 'Shared accommodation, meals on duty.',
                'salary_min' => '2500.00',
                'salary_max' => '3200.00',
                'salary_currency' => 'AED',
                'visa_sponsorship' => 1,
            ],
            [
                'title' => 'Construction Supervisor — Dubai (Demo)',
                'slug' => 'demo-construction-supervisor-dubai',
                'category_id' => $constructionId ?? $categoryId,
                'description' => 'Demo listing: Site supervisor for commercial construction projects.',
                'requirements' => '5+ years site experience, safety certification preferred.',
                'benefits' => 'Transport, overtime as per UAE labour law.',
                'salary_min' => '5000.00',
                'salary_max' => '7000.00',
                'salary_currency' => 'AED',
                'visa_sponsorship' => 1,
            ],
        ];

        foreach ($jobs as $job) {
            if ($this->findIdBy('jobs', 'slug', $job['slug']) !== null) {
                continue;
            }

            $this->pdo->prepare(
                'INSERT INTO `jobs`
                    (`employer_id`, `company_id`, `category_id`, `job_type_id`, `posted_by_user_id`,
                     `title`, `slug`, `description`, `requirements`, `benefits`,
                     `country_id`, `city_id`, `vacancies`, `salary_min`, `salary_max`,
                     `salary_currency`, `salary_period`, `experience_min_years`,
                     `visa_sponsorship`, `status`, `published_at`, `created_at`, `updated_at`)
                 VALUES
                    (:employer_id, :company_id, :category_id, :job_type_id, :posted_by_user_id,
                     :title, :slug, :description, :requirements, :benefits,
                     :country_id, :city_id, :vacancies, :salary_min, :salary_max,
                     :salary_currency, :salary_period, :experience_min_years,
                     :visa_sponsorship, :status, :published_at, :created_at, :updated_at)'
            )->execute([
                'employer_id' => $employerId,
                'company_id' => $companyId,
                'category_id' => $job['category_id'],
                'job_type_id' => $jobTypeId,
                'posted_by_user_id' => $userId,
                'title' => $job['title'],
                'slug' => $job['slug'],
                'description' => $job['description'],
                'requirements' => $job['requirements'],
                'benefits' => $job['benefits'],
                'country_id' => $countryId,
                'city_id' => $cityId,
                'vacancies' => 5,
                'salary_min' => $job['salary_min'],
                'salary_max' => $job['salary_max'],
                'salary_currency' => $job['salary_currency'],
                'salary_period' => 'month',
                'experience_min_years' => 2,
                'visa_sponsorship' => $job['visa_sponsorship'],
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
