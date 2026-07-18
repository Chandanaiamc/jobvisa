<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\Job\Entities\Job;
use JobVisa\App\Domain\Job\Repositories\JobRepositoryInterface as DomainJobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface as InfrastructureJobRepositoryInterface;

/**
 * Enterprise job repository.
 */
final class JobRepository extends BaseRepository implements
    InfrastructureJobRepositoryInterface,
    DomainJobRepositoryInterface
{
    protected string $table = 'jobs';

    public function findById(int|string $id): ?Job
    {
        $row = $this->findRecordById($id);

        if ($row === null) {
            return null;
        }

        return Job::reconstitute((int) $row['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRecordById(int|string $id): ?array
    {
        if ((int) $id < 1) {
            return null;
        }

        return $this->findRowById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `jobs` WHERE `slug` = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPublished(int $limit = 50): array
    {
        $result = $this->searchPublished(['page' => 1, 'per_page' => $limit]);

        return $result['jobs'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{jobs: list<array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function searchPublished(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = ['j.`status` = :status'];
        $params = ['status' => 'published'];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $q = mb_substr($q, 0, 120);
            $ft = mb_strlen($q) >= 3 ? $this->fulltextBooleanQuery($q) : '';
            if ($ft !== '') {
                $where[] = 'MATCH(j.`title`, j.`description`) AGAINST (:q_ft IN BOOLEAN MODE)';
                $params['q_ft'] = $ft;
            } else {
                $where[] = '(j.`title` LIKE :q_like_title OR j.`description` LIKE :q_like_desc)';
                $like = '%' . $this->escapeLike($q) . '%';
                $params['q_like_title'] = $like;
                $params['q_like_desc'] = $like;
            }
        }

        $countryId = (int) ($filters['country_id'] ?? 0);
        if ($countryId > 0) {
            $where[] = 'j.`country_id` = :country_id';
            $params['country_id'] = $countryId;
        }

        $jobTypeId = (int) ($filters['job_type_id'] ?? 0);
        if ($jobTypeId > 0) {
            $where[] = 'j.`job_type_id` = :job_type_id';
            $params['job_type_id'] = $jobTypeId;
        }

        $whereSql = implode(' AND ', $where);

        $countRow = $this->fetchOne(
            'SELECT COUNT(*) AS `aggregate`
             FROM `jobs` j
             WHERE ' . $whereSql,
            $params
        );
        $total = (int) ($countRow['aggregate'] ?? 0);

        $jobs = $this->fetchAll(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug
             FROM `jobs` j
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE ' . $whereSql . '
             ORDER BY j.`published_at` DESC, j.`id` DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return [
            'jobs' => $jobs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function listActiveJobTypes(): array
    {
        $rows = $this->fetchAll(
            'SELECT `id`, `name`, `slug` FROM `job_types`
             WHERE `is_active` = 1
             ORDER BY `name` ASC'
        );

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
            ];
        }

        return $out;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function fulltextBooleanQuery(string $q): string
    {
        $tokens = preg_split('/\s+/u', $q) ?: [];
        $parts = [];
        foreach ($tokens as $token) {
            $token = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $token) ?? '';
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }
            $parts[] = '+' . $token . '*';
        }

        return $parts !== [] ? implode(' ', $parts) : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedRecordById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug
             FROM `jobs` j
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE j.`id` = :id AND j.`status` = :status
             LIMIT 1',
            ['id' => $id, 'status' => 'published']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOwnedByEmployerUser(int $jobId, int $employerUserId): ?array
    {
        if ($jobId < 1 || $employerUserId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT j.*, c.`name` AS country_name, jt.`name` AS job_type_name, jt.`slug` AS job_type_slug,
                    e.`id` AS employer_profile_id, e.`user_id` AS employer_user_id, e.`company_id`
             FROM `jobs` j
             INNER JOIN `employers` e ON e.`id` = j.`employer_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             LEFT JOIN `job_types` jt ON jt.`id` = j.`job_type_id`
             WHERE j.`id` = :job_id AND e.`user_id` = :user_id
             LIMIT 1',
            ['job_id' => $jobId, 'user_id' => $employerUserId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedByEmployerUser(int $employerUserId, int $limit = 50): array
    {
        if ($employerUserId < 1) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        return $this->fetchAll(
            'SELECT j.`id`, j.`title`, j.`slug`, j.`status`, j.`published_at`, j.`applications_count`,
                    c.`name` AS country_name
             FROM `jobs` j
             INNER JOIN `employers` e ON e.`id` = j.`employer_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             WHERE e.`user_id` = :user_id
             ORDER BY j.`published_at` DESC, j.`id` DESC
             LIMIT ' . $limit,
            ['user_id' => $employerUserId]
        );
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return $this->rowExists($id);
    }

    /**
     * @return array{id: int, company_id: int, user_id: int}|null
     */
    public function findEmployerProfileByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $row = $this->fetchOne(
            'SELECT `id`, `company_id`, `user_id` FROM `employers` WHERE `user_id` = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'company_id' => (int) ($row['company_id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
        ];
    }

    public function slugExists(string $slug, ?int $exceptJobId = null): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }
        if ($exceptJobId !== null && $exceptJobId > 0) {
            $row = $this->fetchOne(
                'SELECT `id` FROM `jobs` WHERE `slug` = :slug AND `id` <> :id LIMIT 1',
                ['slug' => $slug, 'id' => $exceptJobId]
            );
        } else {
            $row = $this->fetchOne(
                'SELECT `id` FROM `jobs` WHERE `slug` = :slug LIMIT 1',
                ['slug' => $slug]
            );
        }

        return $row !== null;
    }

    public function jobCategoryExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->fetchOne(
            'SELECT `id` FROM `job_categories` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $id]
        ) !== null;
    }

    public function jobTypeExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->fetchOne(
            'SELECT `id` FROM `job_types` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $id]
        ) !== null;
    }

    public function countryExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->fetchOne(
            'SELECT `id` FROM `countries` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        ) !== null;
    }

    public function cityExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        return $this->fetchOne(
            'SELECT `id` FROM `cities` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        ) !== null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function insertJob(array $data): int
    {
        $this->query(
            'INSERT INTO `jobs`
                (`employer_id`, `company_id`, `category_id`, `job_type_id`, `posted_by_user_id`,
                 `title`, `slug`, `description`, `requirements`, `benefits`,
                 `country_id`, `city_id`, `vacancies`, `salary_min`, `salary_max`,
                 `salary_currency`, `salary_period`, `experience_min_years`, `education_level`,
                 `visa_sponsorship`, `application_deadline`, `status`, `published_at`, `closes_at`)
             VALUES
                (:employer_id, :company_id, :category_id, :job_type_id, :posted_by_user_id,
                 :title, :slug, :description, :requirements, :benefits,
                 :country_id, :city_id, :vacancies, :salary_min, :salary_max,
                 :salary_currency, :salary_period, :experience_min_years, :education_level,
                 :visa_sponsorship, :application_deadline, :status, :published_at, :closes_at)',
            [
                'employer_id' => (int) ($data['employer_id'] ?? 0),
                'company_id' => (int) ($data['company_id'] ?? 0),
                'category_id' => (int) ($data['category_id'] ?? 0),
                'job_type_id' => (int) ($data['job_type_id'] ?? 0),
                'posted_by_user_id' => (int) ($data['posted_by_user_id'] ?? 0),
                'title' => (string) ($data['title'] ?? ''),
                'slug' => (string) ($data['slug'] ?? ''),
                'description' => (string) ($data['description'] ?? ''),
                'requirements' => $data['requirements'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'country_id' => (int) ($data['country_id'] ?? 0),
                'city_id' => $data['city_id'] ?? null,
                'vacancies' => (int) ($data['vacancies'] ?? 1),
                'salary_min' => $data['salary_min'] ?? null,
                'salary_max' => $data['salary_max'] ?? null,
                'salary_currency' => $data['salary_currency'] ?? null,
                'salary_period' => $data['salary_period'] ?? null,
                'experience_min_years' => $data['experience_min_years'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'visa_sponsorship' => (int) ($data['visa_sponsorship'] ?? 0),
                'application_deadline' => $data['application_deadline'] ?? null,
                'status' => (string) ($data['status'] ?? 'draft'),
                'published_at' => $data['published_at'] ?? null,
                'closes_at' => $data['closes_at'] ?? null,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateJobById(int $jobId, array $fields): bool
    {
        if ($jobId < 1 || $fields === []) {
            return false;
        }

        $allowed = [
            'title', 'slug', 'description', 'requirements', 'benefits',
            'category_id', 'job_type_id', 'country_id', 'city_id', 'vacancies',
            'salary_min', 'salary_max', 'salary_currency', 'salary_period',
            'experience_min_years', 'education_level', 'visa_sponsorship',
            'application_deadline', 'status', 'published_at', 'closes_at',
        ];
        $sets = [];
        $params = ['id' => $jobId];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $fields)) {
                continue;
            }
            $sets[] = '`' . $col . '` = :' . $col;
            $params[$col] = $fields[$col];
        }
        if ($sets === []) {
            return false;
        }

        $this->query(
            'UPDATE `jobs` SET ' . implode(', ', $sets) . ' WHERE `id` = :id',
            $params
        );

        return true;
    }
}
