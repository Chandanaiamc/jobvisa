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
     * Employer-owned job payload. Additive vs public; includes status and optional detail fields.
     *
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function jobEmployer(array $job, bool $detailed = false): array
    {
        $base = self::jobPublic($job, $detailed);
        $base['status'] = (string) ($job['status'] ?? '');
        $base['applications_count'] = isset($job['applications_count']) ? (int) $job['applications_count'] : null;
        $base['closes_at'] = $job['closes_at'] ?? null;
        $base['category_id'] = isset($job['category_id']) ? (int) $job['category_id'] : null;
        $base['company_id'] = isset($job['company_id']) ? (int) $job['company_id'] : null;
        $base['employer_id'] = isset($job['employer_id']) ? (int) $job['employer_id'] : null;
        if ($detailed) {
            $base['city_id'] = isset($job['city_id']) ? (int) $job['city_id'] : null;
            $base['views_count'] = isset($job['views_count']) ? (int) $job['views_count'] : null;
            $base['updated_at'] = $job['updated_at'] ?? null;
            $base['created_at'] = $job['created_at'] ?? null;
        }

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
     * Employer-facing applicant row. Email intentionally omitted.
     *
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    public static function applicant(array $app, bool $detailed = false): array
    {
        $base = [
            'id' => (int) ($app['id'] ?? 0),
            'job_id' => isset($app['job_id']) ? (int) $app['job_id'] : null,
            'status' => (string) ($app['status'] ?? ''),
            'applied_at' => $app['applied_at'] ?? null,
            'status_updated_at' => $app['status_updated_at'] ?? null,
            'applicant_name' => (string) ($app['applicant_name'] ?? ''),
            'resume_id' => isset($app['resume_id']) ? (int) $app['resume_id'] : null,
            'resume_title' => (string) ($app['resume_title'] ?? ''),
            // Intentionally omit email / private contact fields
        ];
        if ($detailed) {
            $base['cover_letter'] = isset($app['cover_letter']) ? (string) $app['cover_letter'] : null;
            $base['employer_notes'] = isset($app['employer_notes']) ? (string) $app['employer_notes'] : null;
            $base['job_title'] = (string) ($app['job_title'] ?? '');
        }

        return $base;
    }

    /**
     * Seeker-facing application row.
     *
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    public static function applicationSeeker(array $app, bool $detailed = false): array
    {
        $base = [
            'id' => (int) ($app['id'] ?? 0),
            'job_id' => isset($app['job_id']) ? (int) $app['job_id'] : null,
            'job_title' => (string) ($app['job_title'] ?? ''),
            'job_status' => (string) ($app['job_status'] ?? ''),
            'status' => (string) ($app['status'] ?? ''),
            'applied_at' => $app['applied_at'] ?? null,
            'status_updated_at' => $app['status_updated_at'] ?? null,
            'resume_id' => isset($app['resume_id']) ? (int) $app['resume_id'] : null,
            'resume_title' => (string) ($app['resume_title'] ?? ''),
        ];
        if ($detailed) {
            $base['cover_letter'] = isset($app['cover_letter']) ? (string) $app['cover_letter'] : null;
            $base['country_name'] = (string) ($app['country_name'] ?? '');
        }

        return $base;
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

    /**
     * Scheduled interview payload. Times stored UTC; local wall time derived from timezone.
     *
     * @param  array<string, mixed>  $row
     * @param  \JobVisa\App\Domain\InterviewScheduling\Validators\InterviewSchedulingValidator|null  $validator
     * @return array<string, mixed>
     */
    public static function scheduledInterview(
        array $row,
        bool $detailed = false,
        mixed $validator = null
    ): array {
        $timezone = (string) ($row['timezone'] ?? 'UTC');
        $utcRaw = (string) ($row['scheduled_at_utc'] ?? '');
        $scheduledLocal = null;
        if ($utcRaw !== '' && $validator instanceof \JobVisa\App\Domain\InterviewScheduling\Validators\InterviewSchedulingValidator) {
            $utc = $validator->parseToUtc($utcRaw, 'UTC', true);
            if ($utc !== null) {
                $scheduledLocal = $validator->formatLocal($utc, $timezone);
            }
        }

        $base = [
            'id' => (int) ($row['id'] ?? 0),
            'application_id' => isset($row['application_id']) ? (int) $row['application_id'] : null,
            'job_id' => isset($row['job_id']) ? (int) $row['job_id'] : null,
            'job_title' => (string) ($row['job_title'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'scheduled_at_utc' => $utcRaw !== '' ? $utcRaw : null,
            'scheduled_at_local' => $scheduledLocal,
            'timezone' => $timezone,
            'duration_minutes' => isset($row['duration_minutes']) ? (int) $row['duration_minutes'] : null,
            'location_type' => (string) ($row['location_type'] ?? 'other'),
            'round_number' => isset($row['round_number']) ? (int) $row['round_number'] : null,
            'application_status' => (string) ($row['application_status'] ?? ''),
        ];

        if ($detailed) {
            $base['location_notes'] = isset($row['location_notes']) ? (string) $row['location_notes'] : null;
            $base['candidate_user_id'] = isset($row['candidate_user_id']) ? (int) $row['candidate_user_id'] : null;
            $base['employer_user_id'] = isset($row['employer_user_id']) ? (int) $row['employer_user_id'] : null;
            $base['candidate_name'] = (string) ($row['candidate_name'] ?? '');
            $base['cancelled_at'] = $row['cancelled_at'] ?? null;
            $base['completed_at'] = $row['completed_at'] ?? null;
            $base['created_at'] = $row['created_at'] ?? null;
            $base['updated_at'] = $row['updated_at'] ?? null;
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function jobOffer(array $row, bool $detailed = false): array
    {
        $base = [
            'id' => (int) ($row['id'] ?? 0),
            'application_id' => isset($row['application_id']) ? (int) $row['application_id'] : null,
            'job_id' => isset($row['job_id']) ? (int) $row['job_id'] : null,
            'job_title' => (string) ($row['job_title'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'salary_amount' => isset($row['salary_amount']) ? (string) $row['salary_amount'] : null,
            'salary_currency' => (string) ($row['salary_currency'] ?? 'LKR'),
            'pay_period' => (string) ($row['pay_period'] ?? 'monthly'),
            'start_date' => $row['start_date'] ?? null,
            'expires_at_utc' => $row['expires_at_utc'] ?? null,
            'application_status' => (string) ($row['application_status'] ?? ''),
        ];

        if ($detailed) {
            $base['notes'] = isset($row['notes']) ? (string) $row['notes'] : null;
            $base['candidate_user_id'] = isset($row['candidate_user_id']) ? (int) $row['candidate_user_id'] : null;
            $base['employer_user_id'] = isset($row['employer_user_id']) ? (int) $row['employer_user_id'] : null;
            $base['candidate_name'] = (string) ($row['candidate_name'] ?? '');
            $base['sent_at'] = $row['sent_at'] ?? null;
            $base['accepted_at'] = $row['accepted_at'] ?? null;
            $base['declined_at'] = $row['declined_at'] ?? null;
            $base['withdrawn_at'] = $row['withdrawn_at'] ?? null;
            $base['expired_at'] = $row['expired_at'] ?? null;
            $base['created_at'] = $row['created_at'] ?? null;
            $base['updated_at'] = $row['updated_at'] ?? null;
        }

        return $base;
    }
}
