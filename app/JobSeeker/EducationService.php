<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Security\Validator;
use RuntimeException;

final class EducationService
{
    public function __construct(
        private readonly EducationRepositoryInterface $education,
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

        return $this->education->listByResumeId((int) $resume['id']);
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
        $this->education->create((int) $resume['id'], $validated['data']);
        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Education record added.'];
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

        if (!$this->education->update($id, (int) $resume['id'], $validated['data'])) {
            return ['success' => false, 'message' => 'Education record not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Education record updated.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $userId, int $id): array
    {
        $this->assertEdit($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);

        if (!$this->education->delete($id, (int) $resume['id'])) {
            return ['success' => false, 'message' => 'Education record not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Education record deleted.'];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{data: array<string, mixed>, errors: array<string, list<string>>|null}
     */
    private function validate(array $input): array
    {
        $validator = Validator::make($input)
            ->required('institution')->max('institution', 200)
            ->required('degree')->max('degree', 150)
            ->max('school', 200)
            ->max('field_of_study', 150)
            ->max('grade', 64);

        if ($validator->fails()) {
            return ['data' => [], 'errors' => $validator->errors()];
        }

        return [
            'errors' => null,
            'data' => [
                'school' => $this->nullStr($input['school'] ?? null),
                'institution' => trim((string) $input['institution']),
                'degree' => trim((string) $input['degree']),
                'field_of_study' => $this->nullStr($input['field_of_study'] ?? null),
                'grade' => $this->nullStr($input['grade'] ?? null),
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
