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
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    public static function jobPublic(array $job): array
    {
        return [
            'id' => (int) ($job['id'] ?? 0),
            'title' => (string) ($job['title'] ?? ''),
            'slug' => (string) ($job['slug'] ?? ''),
            'status' => (string) ($job['status'] ?? ''),
            'country_name' => (string) ($job['country_name'] ?? ''),
            'job_type_name' => (string) ($job['job_type_name'] ?? ''),
            'job_type_slug' => (string) ($job['job_type_slug'] ?? ''),
            'experience_min_years' => isset($job['experience_min_years']) ? (int) $job['experience_min_years'] : null,
            'published_at' => $job['published_at'] ?? null,
            'summary' => isset($job['summary']) ? mb_substr((string) $job['summary'], 0, 500) : null,
        ];
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
