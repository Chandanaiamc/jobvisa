<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Services;

use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\Job\Exceptions\JobException;
use JobVisa\App\Domain\Job\Policies\JobPolicy;
use JobVisa\App\Domain\Job\Validators\JobValidator;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;

/**
 * Employer-owned job CRUD (Phase 1): create, update, publish, unpublish, archive.
 */
final class EmployerJobsService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly JobPolicy $policy,
        private readonly JobValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForActor(array $actor, int $limit = 50): array
    {
        if (!$this->policy->canCreate($actor)) {
            throw JobException::forbidden();
        }

        $rows = $this->jobs->listOwnedByEmployerUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            static fn (array $j): array => ApiResource::jobEmployer($j, false),
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForActor(array $actor, int $jobId): array
    {
        $job = $this->requireOwned($actor, $jobId);

        return ApiResource::jobEmployer($job, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, job?: array<string, mixed>}
     */
    public function create(array $actor, array $input): array
    {
        if (!$this->policy->canCreate($actor)) {
            return ['success' => false, 'message' => JobException::forbidden()->getMessage()];
        }

        $errors = $this->validator->fieldErrors($input, false);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $profile = $this->jobs->findEmployerProfileByUserId((int) ($actor['id'] ?? 0));
        if ($profile === null) {
            return [
                'success' => false,
                'message' => JobException::employerProfileRequired()->getMessage(),
                'errors' => ['employer' => [JobException::employerProfileRequired()->getMessage()]],
            ];
        }

        $fkErrors = $this->validateForeignKeys($input);
        if ($fkErrors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $fkErrors];
        }

        $status = (string) ($input['status'] ?? 'draft');
        if (!in_array($status, JobValidator::STATUSES_WRITABLE, true)) {
            $status = 'draft';
        }

        $slug = $this->resolveSlug($input, null);
        $now = gmdate('Y-m-d H:i:s.v');
        $payload = $this->normalizeWritableFields($input);
        $payload['employer_id'] = (int) $profile['id'];
        $payload['company_id'] = (int) $profile['company_id'];
        $payload['posted_by_user_id'] = (int) ($actor['id'] ?? 0);
        $payload['slug'] = $slug;
        $payload['status'] = $status;
        $payload['published_at'] = $status === 'published' ? $now : null;
        $payload['closes_at'] = null;

        $id = $this->jobs->insertJob($payload);
        $job = $this->jobs->findOwnedByEmployerUser($id, (int) ($actor['id'] ?? 0));

        return [
            'success' => true,
            'message' => $status === 'published' ? 'Job created and published.' : 'Job draft created.',
            'job' => ApiResource::jobEmployer($job ?? ['id' => $id], true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, job?: array<string, mixed>}
     */
    public function update(array $actor, int $jobId, array $input): array
    {
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));
        if ($job === null) {
            return ['success' => false, 'message' => JobException::notFound()->getMessage()];
        }
        if (!$this->policy->canManage($actor, $job)) {
            return ['success' => false, 'message' => JobException::forbidden()->getMessage()];
        }

        $errors = $this->validator->fieldErrors($input, true);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $fkErrors = $this->validateForeignKeys($input, true);
        if ($fkErrors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $fkErrors];
        }

        $fields = $this->normalizeWritableFields($input, true);
        if (array_key_exists('slug', $input) && trim((string) ($input['slug'] ?? '')) !== '') {
            $fields['slug'] = $this->resolveSlug($input, $jobId);
        } elseif (array_key_exists('title', $input) && trim((string) ($input['title'] ?? '')) !== '') {
            // Keep existing slug on title-only edits unless slug provided.
        }

        // Optional inline status change limited to draft|published (not archive).
        if (array_key_exists('status', $input) && $input['status'] !== null && $input['status'] !== '') {
            $next = (string) $input['status'];
            if (!in_array($next, JobValidator::STATUSES_WRITABLE, true)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => ['status' => ['Status must be draft or published.']],
                ];
            }
            $fields['status'] = $next;
            if ($next === 'published' && empty($job['published_at'])) {
                $fields['published_at'] = gmdate('Y-m-d H:i:s.v');
            }
            if ($next === 'draft') {
                // Unpublish via update: leave published_at history intact.
            }
        }

        if ($fields !== []) {
            $this->jobs->updateJobById($jobId, $fields);
        }

        $updated = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));

        return [
            'success' => true,
            'message' => 'Job updated.',
            'job' => ApiResource::jobEmployer($updated ?? $job, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, job?: array<string, mixed>}
     */
    public function publish(array $actor, int $jobId): array
    {
        return $this->transition($actor, $jobId, 'published', 'Job published.', true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, job?: array<string, mixed>}
     */
    public function unpublish(array $actor, int $jobId): array
    {
        return $this->transition($actor, $jobId, 'draft', 'Job unpublished.', false);
    }

    /**
     * Soft-archive via status=closed (no hard delete).
     *
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, job?: array<string, mixed>}
     */
    public function archive(array $actor, int $jobId): array
    {
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));
        if ($job === null) {
            return ['success' => false, 'message' => JobException::notFound()->getMessage()];
        }
        if (!$this->policy->canArchive($actor, $job)) {
            return ['success' => false, 'message' => JobException::forbidden()->getMessage()];
        }

        $fields = [
            'status' => 'closed',
            'closes_at' => gmdate('Y-m-d H:i:s.v'),
        ];
        $this->jobs->updateJobById($jobId, $fields);
        $updated = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));

        return [
            'success' => true,
            'message' => 'Job archived.',
            'job' => ApiResource::jobEmployer($updated ?? $job, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, job?: array<string, mixed>}
     */
    private function transition(array $actor, int $jobId, string $status, string $okMessage, bool $setPublishedAt): array
    {
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));
        if ($job === null) {
            return ['success' => false, 'message' => JobException::notFound()->getMessage()];
        }
        if (!$this->policy->canPublish($actor, $job)) {
            return ['success' => false, 'message' => JobException::forbidden()->getMessage()];
        }

        $fields = ['status' => $status];
        if ($setPublishedAt) {
            $fields['published_at'] = !empty($job['published_at'])
                ? (string) $job['published_at']
                : gmdate('Y-m-d H:i:s.v');
            // Reopening from closed clears closes_at.
            if ((string) ($job['status'] ?? '') === 'closed') {
                $fields['closes_at'] = null;
            }
        }

        $this->jobs->updateJobById($jobId, $fields);
        $updated = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));

        return [
            'success' => true,
            'message' => $okMessage,
            'job' => ApiResource::jobEmployer($updated ?? $job, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireOwned(array $actor, int $jobId): array
    {
        if (!$this->policy->canCreate($actor)) {
            throw JobException::forbidden();
        }
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));
        if ($job === null) {
            throw JobException::notFound();
        }
        if (!$this->policy->canManage($actor, $job)) {
            throw JobException::forbidden();
        }

        return $job;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    private function validateForeignKeys(array $input, bool $partial = false): array
    {
        $errors = [];
        $checks = [
            'category_id' => 'jobCategoryExists',
            'job_type_id' => 'jobTypeExists',
            'country_id' => 'countryExists',
        ];
        foreach ($checks as $field => $method) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            if (!array_key_exists($field, $input) && $partial) {
                continue;
            }
            $id = (int) ($input[$field] ?? 0);
            if ($id < 1) {
                continue;
            }
            if (!$this->jobs->{$method}($id)) {
                $errors[$field][] = 'Selected ' . $field . ' is invalid.';
            }
        }
        if (array_key_exists('city_id', $input) && $input['city_id'] !== null && $input['city_id'] !== '') {
            $cityId = (int) $input['city_id'];
            if ($cityId > 0 && !$this->jobs->cityExists($cityId)) {
                $errors['city_id'][] = 'Selected city_id is invalid.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeWritableFields(array $input, bool $partial = false): array
    {
        $map = [
            'title' => static fn ($v) => trim((string) $v),
            'description' => static fn ($v) => trim((string) $v),
            'requirements' => static fn ($v) => $v === null || $v === '' ? null : (string) $v,
            'benefits' => static fn ($v) => $v === null || $v === '' ? null : (string) $v,
            'category_id' => static fn ($v) => (int) $v,
            'job_type_id' => static fn ($v) => (int) $v,
            'country_id' => static fn ($v) => (int) $v,
            'city_id' => static fn ($v) => ($v === null || $v === '') ? null : (int) $v,
            'vacancies' => static fn ($v) => max(1, (int) $v),
            'salary_min' => static fn ($v) => ($v === null || $v === '') ? null : (float) $v,
            'salary_max' => static fn ($v) => ($v === null || $v === '') ? null : (float) $v,
            'salary_currency' => static fn ($v) => ($v === null || $v === '') ? null : strtoupper(trim((string) $v)),
            'salary_period' => static fn ($v) => ($v === null || $v === '') ? null : (string) $v,
            'experience_min_years' => static fn ($v) => ($v === null || $v === '') ? null : (int) $v,
            'education_level' => static fn ($v) => ($v === null || $v === '') ? null : trim((string) $v),
            'visa_sponsorship' => static fn ($v) => !empty($v) ? 1 : 0,
            'application_deadline' => static fn ($v) => ($v === null || $v === '') ? null : (string) $v,
        ];

        $out = [];
        foreach ($map as $key => $cast) {
            if ($partial && !array_key_exists($key, $input)) {
                continue;
            }
            if (!$partial && !array_key_exists($key, $input) && !in_array($key, ['title', 'description', 'category_id', 'job_type_id', 'country_id'], true)) {
                // Defaults for create
                if ($key === 'vacancies') {
                    $out[$key] = 1;
                } elseif ($key === 'visa_sponsorship') {
                    $out[$key] = 0;
                } elseif (in_array($key, ['requirements', 'benefits', 'city_id', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'experience_min_years', 'education_level', 'application_deadline'], true)) {
                    $out[$key] = null;
                }
                continue;
            }
            if (!array_key_exists($key, $input) && !$partial) {
                continue;
            }
            if (array_key_exists($key, $input)) {
                $out[$key] = $cast($input[$key]);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveSlug(array $input, ?int $exceptJobId): string
    {
        $raw = trim((string) ($input['slug'] ?? ''));
        if ($raw === '') {
            $raw = $this->slugify((string) ($input['title'] ?? 'job'));
        } else {
            $raw = $this->slugify($raw);
        }
        if ($raw === '') {
            $raw = 'job';
        }

        $candidate = mb_substr($raw, 0, 180);
        $n = 0;
        while ($this->jobs->slugExists($candidate, $exceptJobId)) {
            $n++;
            $suffix = '-' . $n;
            $candidate = mb_substr($raw, 0, 180 - mb_strlen($suffix)) . $suffix;
        }

        return $candidate;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }
}
