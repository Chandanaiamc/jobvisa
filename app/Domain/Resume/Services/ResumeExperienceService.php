<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeExperienceDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeExperiencePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeExperienceValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

/**
 * Resume builder — multi-record work experience (reuses `work_experience` table).
 */
final class ResumeExperienceService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly LocationRepositoryInterface $locations,
        private readonly SkillCatalogRepositoryInterface $skills,
        private readonly ResumeExperienceValidator $validator,
        private readonly ResumeExperiencePolicy $policy,
        private readonly ResumeCompletionCalculator $completion
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function form(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canManage($actor, $aggregate->resume());
        $includePrivate = $this->policy->canViewPrivate($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $rows = $this->experience->listByResumeId($resumeId);
        $deletedRows = $this->experience->listDeletedByResumeId($resumeId);
        $skillMap = $this->experience->mapSkillsForExperiences(
            array_map(static fn (array $r): int => (int) $r['id'], array_merge($rows, $deletedRows))
        );

        $items = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $items[] = ResumeExperienceDTO::fromRow($row, $skillMap[$id] ?? [], $canEdit, $includePrivate);
        }

        $deleted = [];
        foreach ($deletedRows as $row) {
            $id = (int) $row['id'];
            $deleted[] = ResumeExperienceDTO::fromRow($row, $skillMap[$id] ?? [], $canEdit, $includePrivate);
        }

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeExperienceDTO::blank($resumeId, $canEdit),
            'countries' => $this->locations->listCountries(),
            'skill_options' => $this->skills->listActive(),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'include_private' => $includePrivate,
            'employment_types' => ResumeExperienceValidator::EMPLOYMENT_TYPES,
            'statuses' => ResumeExperienceValidator::STATUSES,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $experienceId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->experience->findOwned($experienceId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        $skills = $this->experience->mapSkillsForExperiences([$experienceId])[$experienceId] ?? [];

        return array_merge($data, [
            'item' => ResumeExperienceDTO::fromRow(
                $row,
                $skills,
                $data['can_edit'],
                $data['include_private']
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function store(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $skillIds = $this->normalizeSkillIds($input);
        $allowed = $this->experience->filterActiveSkillIds($skillIds);

        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->experience->countryExists($id),
            $allowed
        );

        if (count($skillIds) !== count($allowed)) {
            $errors['skill_ids'][] = 'One or more skills are invalid.';
        }

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        $newId = $this->experience->create($resumeId, $payload);
        $this->experience->syncSkills($newId, $allowed);

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Work experience added.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $experienceId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $skillIds = $this->normalizeSkillIds($input);
        $allowed = $this->experience->filterActiveSkillIds($skillIds);

        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->experience->countryExists($id),
            $allowed
        );

        if (count($skillIds) !== count($allowed)) {
            $errors['skill_ids'][] = 'One or more skills are invalid.';
        }

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        if (!$this->experience->update($experienceId, $resumeId, $payload)) {
            return ['success' => false, 'message' => 'Work experience not found.'];
        }

        $this->experience->syncSkills($experienceId, $allowed);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Work experience updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $experienceId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->experience->delete($experienceId, $resumeId)) {
            return ['success' => false, 'message' => 'Work experience not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Work experience moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $experienceId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->experience->restore($experienceId, $resumeId)) {
            return ['success' => false, 'message' => 'Work experience not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Work experience restored.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, completion?: array}
     */
    public function reorder(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $ids = $input['order'] ?? $input['experience_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No experience order provided.'];
        }

        $owned = $this->experience->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid experience order.'];
        }

        $this->experience->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Experience order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $isCurrent = !empty($input['is_current']);

        return [
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'job_title' => trim((string) ($input['job_title'] ?? '')),
            'employment_type' => $this->nullStr($input['employment_type'] ?? null),
            'industry' => $this->nullStr($input['industry'] ?? null),
            'country_id' => $this->nullId($input['country_id'] ?? null),
            'city' => $this->nullStr($input['city'] ?? null),
            'start_date' => $this->nullStr($input['start_date'] ?? null),
            'end_date' => $isCurrent ? null : $this->nullStr($input['end_date'] ?? null),
            'is_current' => $isCurrent,
            'responsibilities' => $this->nullStr($input['responsibilities'] ?? $input['description'] ?? null),
            'achievements' => $this->nullStr($input['achievements'] ?? null),
            'reason_for_leaving' => $this->nullStr($input['reason_for_leaving'] ?? null),
            'supervisor_name' => $this->nullStr($input['supervisor_name'] ?? null),
            'supervisor_contact' => $this->nullStr($input['supervisor_contact'] ?? null),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<int>
     */
    private function normalizeSkillIds(array $input): array
    {
        $raw = $input['skill_ids'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);

        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw ResumeException::notFound();
        }

        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canManage($actor, $aggregate->resume());

        if (!$allowed) {
            throw ResumeException::forbidden();
        }

        return $aggregate;
    }

    private function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
