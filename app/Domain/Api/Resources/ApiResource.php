<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Resources;

/**
 * Output filtering — never expose secrets or private PII beyond safe API fields.
 */
final class ApiResource
{
    /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>
     */
    public static function user(array $user): array
    {
        return [
            'id' => (int) ($user['id'] ?? 0),
            'email' => (string) ($user['email'] ?? ''),
            'full_name' => (string) ($user['full_name'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'status' => (string) ($user['status'] ?? ''),
        ];
    }

    /**
     * Public job card / detail. Additive fields; `summary` remains for older clients.
     *
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function jobPublic(array $job, bool $detailed = false): array
    {
        $description = (string) ($job['description'] ?? '');
        $summarySource = (string) ($job['summary'] ?? $description);
        $summary = $summarySource !== '' ? mb_substr(trim(strip_tags($summarySource)), 0, 500) : null;

        $base = [
            'id' => (int) ($job['id'] ?? 0),
            'title' => (string) ($job['title'] ?? ''),
            'slug' => (string) ($job['slug'] ?? ''),
            'status' => (string) ($job['status'] ?? ''),
            'country_id' => isset($job['country_id']) ? (int) $job['country_id'] : null,
            'country_name' => (string) ($job['country_name'] ?? ''),
            'job_type_id' => isset($job['job_type_id']) ? (int) $job['job_type_id'] : null,
            'job_type_name' => (string) ($job['job_type_name'] ?? ''),
            'job_type_slug' => (string) ($job['job_type_slug'] ?? ''),
            'experience_min_years' => isset($job['experience_min_years']) ? (int) $job['experience_min_years'] : null,
            'visa_sponsorship' => (bool) ($job['visa_sponsorship'] ?? false),
            'salary_min' => isset($job['salary_min']) ? (float) $job['salary_min'] : null,
            'salary_max' => isset($job['salary_max']) ? (float) $job['salary_max'] : null,
            'salary_currency' => isset($job['salary_currency']) ? (string) $job['salary_currency'] : null,
            'salary_period' => isset($job['salary_period']) ? (string) $job['salary_period'] : null,
            'application_deadline' => $job['application_deadline'] ?? null,
            'published_at' => $job['published_at'] ?? null,
            'summary' => $summary,
        ];

        if ($detailed) {
            $base['description'] = $description;
            $base['requirements'] = isset($job['requirements']) ? (string) $job['requirements'] : null;
            $base['benefits'] = isset($job['benefits']) ? (string) $job['benefits'] : null;
            $base['vacancies'] = isset($job['vacancies']) ? (int) $job['vacancies'] : null;
            $base['education_level'] = isset($job['education_level']) ? (string) $job['education_level'] : null;
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function jobEmployer(array $job): array
    {
        $base = self::jobPublic($job);
        $base['status'] = (string) ($job['status'] ?? '');

        return $base;
    }

    /**
     * @param  array<string, mixed>  $resume
     * @return array<string, mixed>
     */
    public static function resume(array $resume): array
    {
        return [
            'id' => (int) ($resume['id'] ?? 0),
            'title' => (string) ($resume['title'] ?? ''),
            'status' => (string) ($resume['status'] ?? ''),
            'visibility' => (string) ($resume['visibility'] ?? ''),
            'completeness_score' => (int) ($resume['completeness_score'] ?? 0),
            'updated_at' => $resume['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    public static function applicant(array $app): array
    {
        return [
            'id' => (int) ($app['id'] ?? 0),
            'status' => (string) ($app['status'] ?? ''),
            'applied_at' => $app['applied_at'] ?? null,
            'applicant_name' => (string) ($app['applicant_name'] ?? ''),
            'resume_title' => (string) ($app['resume_title'] ?? ''),
            // Intentionally omit email / private contact fields
        ];
    }

    /**
     * @param  array<string, mixed>  $token
     * @return array<string, mixed>
     */
    public static function tokenMeta(array $token): array
    {
        return [
            'id' => (int) ($token['id'] ?? 0),
            'name' => (string) ($token['name'] ?? ''),
            'token_prefix' => (string) ($token['token_prefix'] ?? $token['prefix'] ?? ''),
            'last_used_at' => $token['last_used_at'] ?? null,
            'expires_at' => $token['expires_at'] ?? null,
            'revoked_at' => $token['revoked_at'] ?? null,
            'created_at' => $token['created_at'] ?? null,
        ];
    }
}
