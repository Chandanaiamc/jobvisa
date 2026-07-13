<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeSkillDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeSkillPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeSkillValidator;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;

/**
 * Resume builder — skills section (catalogue = `skills`; does not use `user_skills`).
 */
final class ResumeSkillService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeSkillRepositoryInterface $resumeSkills,
        private readonly SkillCatalogRepositoryInterface $catalog,
        private readonly ResumeSkillValidator $validator,
        private readonly ResumeSkillPolicy $policy,
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
        $userId = $aggregate->resume()->userId();

        $items = array_map(
            static fn (array $row): ResumeSkillDTO => ResumeSkillDTO::fromRow($row, $canEdit),
            $this->resumeSkills->listByResumeId($resumeId)
        );
        $deleted = array_map(
            static fn (array $row): ResumeSkillDTO => ResumeSkillDTO::fromRow($row, $canEdit),
            $this->resumeSkills->listDeletedByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeSkillDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'levels' => ResumeSkillValidator::LEVELS,
            'statuses' => ResumeSkillValidator::STATUSES,
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/skills/search',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $skillRowId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->resumeSkills->findOwned($skillRowId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeSkillDTO::fromRow($row, $data['can_edit']),
        ]);
    }

    /**
     * Autocomplete — requires view access to the resume.
     *
     * @param  array<string, mixed>  $actor
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function search(array $actor, int $resumeId, string $query): array
    {
        $this->requireResume($actor, $resumeId, true);
        $rows = $this->catalog->search($query, 15);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'slug' => (string) ($r['slug'] ?? ''),
        ], $rows);
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
            fn (int $id): bool => $this->catalog->isActive($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        $existing = $this->resumeSkills->findByResumeAndSkill($resumeId, (int) $payload['skill_id']);

        if ($existing !== null) {
            if (empty($existing['deleted_at'])) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => ['skill_id' => ['This skill is already on the resume.']],
                ];
            }

            // Restore + update soft-deleted row instead of violating unique key.
            $this->resumeSkills->restore((int) $existing['id'], $resumeId);
            if (!empty($payload['is_primary'])) {
                $this->resumeSkills->clearPrimaryExcept($resumeId, (int) $existing['id']);
            }
            $this->resumeSkills->update((int) $existing['id'], $resumeId, $payload);
            $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

            return [
                'success' => true,
                'message' => 'Skill restored and updated.',
                'completion' => $completion,
            ];
        }

        if (!empty($payload['is_primary'])) {
            $this->resumeSkills->clearPrimaryExcept($resumeId, null);
        }

        $newId = $this->resumeSkills->create($resumeId, $payload);
        if (!empty($payload['is_primary'])) {
            $this->resumeSkills->clearPrimaryExcept($resumeId, $newId);
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Skill added to resume.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $skillRowId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $current = $this->resumeSkills->findOwned($skillRowId, $resumeId);

        if ($current === null) {
            return ['success' => false, 'message' => 'Skill not found.'];
        }

        // Keep skill_id locked on edit (change via delete + add).
        $input['skill_id'] = (int) $current['skill_id'];

        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->catalog->isActive($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        $payload['skill_id'] = (int) $current['skill_id'];

        if (!empty($payload['is_primary'])) {
            $this->resumeSkills->clearPrimaryExcept($resumeId, $skillRowId);
        }

        if (!$this->resumeSkills->update($skillRowId, $resumeId, $payload)) {
            return ['success' => false, 'message' => 'Skill not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Skill updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $skillRowId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->resumeSkills->delete($skillRowId, $resumeId)) {
            return ['success' => false, 'message' => 'Skill not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Skill moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $skillRowId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->resumeSkills->restore($skillRowId, $resumeId)) {
            return ['success' => false, 'message' => 'Skill not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Skill restored.',
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
        $ids = $input['order'] ?? $input['skill_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No skill order provided.'];
        }

        $owned = $this->resumeSkills->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid skill order.'];
        }

        $this->resumeSkills->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Skill order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $years = $input['years_experience'] ?? null;
        $yearsNorm = null;
        if ($years !== null && $years !== '' && is_numeric($years)) {
            $yearsNorm = number_format((float) $years, 1, '.', '');
        }

        $lastUsed = $input['last_used_year'] ?? null;
        $lastNorm = null;
        if ($lastUsed !== null && $lastUsed !== '') {
            $lastNorm = (int) $lastUsed;
        }

        return [
            'skill_id' => (int) ($input['skill_id'] ?? 0),
            'level' => strtolower(trim((string) ($input['level'] ?? 'intermediate'))),
            'years_experience' => $yearsNorm,
            'last_used_year' => $lastNorm,
            'is_primary' => !empty($input['is_primary']),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => trim((string) ($input['status'] ?? 'active')) ?: 'active',
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
}
