<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;
use JobVisa\App\Security\Validator;
use RuntimeException;

final class ExperienceService
{
    public function __construct(
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ProfileCompletenessService $completeness,
        private readonly ProfileAccess $access
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function list(array $actor, int $userId): array
    {
        $this->assertView($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);

        return $this->experience->listByResumeId((int) $resume['id']);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function store(array $actor, int $userId, array $input): array
    {
        $this->assertEdit($actor, $userId);
        $validated = $this->validate($input);

        if ($validated['errors'] !== null) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validated['errors']];
        }

        $resume = $this->resumes->ensurePrimary($userId);
        $this->experience->create((int) $resume['id'], $validated['data']);
        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Work experience added.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function update(array $actor, int $userId, int $id, array $input): array
    {
        $this->assertEdit($actor, $userId);
        $validated = $this->validate($input);

        if ($validated['errors'] !== null) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validated['errors']];
        }

        $resume = $this->resumes->ensurePrimary($userId);

        if (!$this->experience->update($id, (int) $resume['id'], $validated['data'])) {
            return ['success' => false, 'message' => 'Work experience not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Work experience updated.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $userId, int $id): array
    {
        $this->assertEdit($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);

        if (!$this->experience->delete($id, (int) $resume['id'])) {
            return ['success' => false, 'message' => 'Work experience not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Work experience deleted.'];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{data: array<string, mixed>, errors: array<string, list<string>>|null}
     */
    private function validate(array $input): array
    {
        $validator = Validator::make($input)
            ->required('company_name')->max('company_name', 200)
            ->required('job_title')->max('job_title', 150);

        if ($validator->fails()) {
            return ['data' => [], 'errors' => $validator->errors()];
        }

        $countryId = isset($input['country_id']) && $input['country_id'] !== ''
            ? (int) $input['country_id']
            : null;

        return [
            'errors' => null,
            'data' => [
                'company_name' => trim((string) $input['company_name']),
                'job_title' => trim((string) $input['job_title']),
                'country_id' => $countryId > 0 ? $countryId : null,
                'start_date' => $this->nullStr($input['start_date'] ?? null),
                'end_date' => !empty($input['is_current']) ? null : $this->nullStr($input['end_date'] ?? null),
                'is_current' => !empty($input['is_current']),
                'description' => $this->nullStr($input['description'] ?? null),
                'sort_order' => (int) ($input['sort_order'] ?? 0),
            ],
        ];
    }

    private function nullStr(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    /** @param array<string, mixed> $actor */
    private function assertView(array $actor, int $userId): void
    {
        if (!$this->access->canView($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }

    /** @param array<string, mixed> $actor */
    private function assertEdit(array $actor, int $userId): void
    {
        if (!$this->access->canEdit($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }
}
