<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Validators;

use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Employer job create/update validation.
 */
final class JobValidator extends AbstractValidator
{
    public const STATUSES_WRITABLE = ['draft', 'published'];

    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];
        $partial = !empty($data['_partial']);

        $title = trim((string) ($data['title'] ?? ''));
        if (!$partial || array_key_exists('title', $data)) {
            if ($title === '') {
                $errors[] = 'Title is required.';
            } elseif (mb_strlen($title) > 255) {
                $errors[] = 'Title may not exceed 255 characters.';
            }
        }

        $description = trim((string) ($data['description'] ?? ''));
        if (!$partial || array_key_exists('description', $data)) {
            if ($description === '') {
                $errors[] = 'Description is required.';
            } elseif (mb_strlen($description) > 50000) {
                $errors[] = 'Description is too long.';
            }
        }

        foreach (['category_id', 'job_type_id', 'country_id'] as $fk) {
            if ($partial && !array_key_exists($fk, $data)) {
                continue;
            }
            $id = (int) ($data[$fk] ?? 0);
            if ($id < 1) {
                $errors[] = ucfirst(str_replace('_', ' ', $fk)) . ' is required.';
            }
        }

        if (array_key_exists('city_id', $data) && $data['city_id'] !== null && $data['city_id'] !== '') {
            if ((int) $data['city_id'] < 1) {
                $errors[] = 'City id is invalid.';
            }
        }

        if (array_key_exists('vacancies', $data) && $data['vacancies'] !== null && $data['vacancies'] !== '') {
            $vacancies = (int) $data['vacancies'];
            if ($vacancies < 1 || $vacancies > 10000) {
                $errors[] = 'Vacancies must be between 1 and 10000.';
            }
        }

        if (array_key_exists('salary_min', $data) && $data['salary_min'] !== null && $data['salary_min'] !== '') {
            if (!is_numeric($data['salary_min']) || (float) $data['salary_min'] < 0) {
                $errors[] = 'Salary min must be a non-negative number.';
            }
        }
        if (array_key_exists('salary_max', $data) && $data['salary_max'] !== null && $data['salary_max'] !== '') {
            if (!is_numeric($data['salary_max']) || (float) $data['salary_max'] < 0) {
                $errors[] = 'Salary max must be a non-negative number.';
            }
        }
        if (
            isset($data['salary_min'], $data['salary_max'])
            && $data['salary_min'] !== null && $data['salary_min'] !== ''
            && $data['salary_max'] !== null && $data['salary_max'] !== ''
            && is_numeric($data['salary_min']) && is_numeric($data['salary_max'])
            && (float) $data['salary_max'] < (float) $data['salary_min']
        ) {
            $errors[] = 'Salary max must be greater than or equal to salary min.';
        }

        if (array_key_exists('salary_currency', $data) && $data['salary_currency'] !== null && $data['salary_currency'] !== '') {
            $cur = strtoupper(trim((string) $data['salary_currency']));
            if (!preg_match('/^[A-Z]{3}$/', $cur)) {
                $errors[] = 'Salary currency must be a 3-letter ISO code.';
            }
        }

        if (array_key_exists('salary_period', $data) && $data['salary_period'] !== null && $data['salary_period'] !== '') {
            if (!in_array((string) $data['salary_period'], ['month', 'year', 'hour'], true)) {
                $errors[] = 'Salary period must be month, year, or hour.';
            }
        }

        if (array_key_exists('experience_min_years', $data) && $data['experience_min_years'] !== null && $data['experience_min_years'] !== '') {
            $years = (int) $data['experience_min_years'];
            if ($years < 0 || $years > 50) {
                $errors[] = 'Experience min years must be between 0 and 50.';
            }
        }

        if (array_key_exists('requirements', $data) && is_string($data['requirements']) && mb_strlen($data['requirements']) > 50000) {
            $errors[] = 'Requirements is too long.';
        }
        if (array_key_exists('benefits', $data) && is_string($data['benefits']) && mb_strlen($data['benefits']) > 10000) {
            $errors[] = 'Benefits is too long.';
        }
        if (array_key_exists('education_level', $data) && is_string($data['education_level']) && mb_strlen($data['education_level']) > 50) {
            $errors[] = 'Education level may not exceed 50 characters.';
        }

        if (array_key_exists('application_deadline', $data) && $data['application_deadline'] !== null && $data['application_deadline'] !== '') {
            $deadline = (string) $data['application_deadline'];
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $deadline);
            if ($dt === false || $dt->format('Y-m-d') !== $deadline) {
                $errors[] = 'Application deadline must be Y-m-d.';
            }
        }

        if (array_key_exists('status', $data) && $data['status'] !== null && $data['status'] !== '') {
            if (!in_array((string) $data['status'], self::STATUSES_WRITABLE, true)) {
                $errors[] = 'Status must be draft or published.';
            }
        }

        if (array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== '') {
            $slug = (string) $data['slug'];
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || mb_strlen($slug) > 191) {
                $errors[] = 'Slug must be lowercase letters, numbers, and hyphens (max 191).';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function fieldErrors(array $input, bool $partial = false): array
    {
        if ($partial) {
            $input['_partial'] = true;
        }
        $messages = $this->validate($input);
        $mapped = [];

        foreach ($messages as $message) {
            $lower = strtolower($message);
            $field = 'form';
            foreach ([
                'title' => 'title',
                'description' => 'description',
                'category' => 'category_id',
                'job type' => 'job_type_id',
                'country' => 'country_id',
                'city' => 'city_id',
                'vacancies' => 'vacancies',
                'salary min' => 'salary_min',
                'salary max' => 'salary_max',
                'salary currency' => 'salary_currency',
                'salary period' => 'salary_period',
                'experience' => 'experience_min_years',
                'requirements' => 'requirements',
                'benefits' => 'benefits',
                'education' => 'education_level',
                'application deadline' => 'application_deadline',
                'status' => 'status',
                'slug' => 'slug',
            ] as $needle => $key) {
                if (str_contains($lower, $needle)) {
                    $field = $key;
                    break;
                }
            }
            $mapped[$field][] = $message;
        }

        return $mapped;
    }
}
