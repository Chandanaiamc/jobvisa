<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeEducationDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeEducationPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeEducationValidator;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;

/**
 * Resume builder — multi-record education section (reuses `education` table).
 */
final class ResumeEducationService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly EducationRepositoryInterface $education,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumeEducationValidator $validator,
        private readonly ResumeEducationPolicy $policy,
        private readonly ResumeCompletionCalculator $completion
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{
     *   items: list<ResumeEducationDTO>,
     *   deleted: list<ResumeEducationDTO>,
     *   blank: ResumeEducationDTO,
     *   countries: list<array<string, mixed>>,
     *   completion: array{score: int, sections: array},
     *   resume: array<string, mixed>,
     *   can_edit: bool,
     *   qualification_types: list<string>,
     *   statuses: list<string>
     * }
     */
    public function form(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canManage($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $items = array_map(
            static fn (array $row): ResumeEducationDTO => ResumeEducationDTO::fromRow($row, $canEdit),
            $this->education->listByResumeId($resumeId)
        );
        $deleted = array_map(
            static fn (array $row): ResumeEducationDTO => ResumeEducationDTO::fromRow($row, $canEdit),
            $this->education->listDeletedByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeEducationDTO::blank($resumeId, $canEdit),
            'countries' => $this->locations->listCountries(),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'qualification_types' => ResumeEducationValidator::QUALIFICATION_TYPES,
            'statuses' => ResumeEducationValidator::STATUSES,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{item: ResumeEducationDTO, countries: list<array>, can_edit: bool, resume: array, completion: array, qualification_types: list<string>, statuses: list<string>}
     */
    public function editForm(array $actor, int $resumeId, int $educationId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->education->findOwned($educationId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return [
            'item' => ResumeEducationDTO::fromRow($row, $data['can_edit']),
            'countries' => $data['countries'],
            'can_edit' => $data['can_edit'],
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'qualification_types' => $data['qualification_types'],
            'statuses' => $data['statuses'],
            'items' => $data['items'],
            'deleted' => $data['deleted'],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function store(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->education->countryExists($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        if (!empty($payload['is_current'])) {
            $this->education->clearCurrentExcept($resumeId, null);
        }

        $this->education->create($resumeId, $payload);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Education record added.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $educationId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->education->countryExists($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        if (!empty($payload['is_current'])) {
            $this->education->clearCurrentExcept($resumeId, $educationId);
        }

        if (!$this->education->update($educationId, $resumeId, $payload)) {
            return ['success' => false, 'message' => 'Education record not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Education record updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $educationId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->education->delete($educationId, $resumeId)) {
            return ['success' => false, 'message' => 'Education record not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Education record moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $educationId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->education->restore($educationId, $resumeId)) {
            return ['success' => false, 'message' => 'Education record not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Education record restored.',
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
        $ids = $input['order'] ?? $input['education_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No education order provided.'];
        }

        $owned = $this->education->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid education order.'];
        }

        $this->education->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Education order updated.',
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
            'school' => $this->nullStr($input['school'] ?? null),
            'institution' => trim((string) ($input['institution'] ?? '')),
            'qualification_type' => $this->nullStr($input['qualification_type'] ?? null),
            'degree' => trim((string) ($input['degree'] ?? '')),
            'field_of_study' => $this->nullStr($input['field_of_study'] ?? null),
            'grade' => $this->nullStr($input['grade'] ?? null),
            'country_id' => $this->nullId($input['country_id'] ?? null),
            'city' => $this->nullStr($input['city'] ?? null),
            'start_date' => $this->nullStr($input['start_date'] ?? null),
            'end_date' => $isCurrent ? null : $this->nullStr($input['end_date'] ?? null),
            'is_current' => $isCurrent,
            'description' => $this->nullStr($input['description'] ?? null),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
        ];
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
