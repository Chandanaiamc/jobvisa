<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeReferenceDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeReferencePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeReferenceValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;

/**
 * Resume builder — professional references (resume-scoped).
 */
final class ResumeReferenceService
{
    public const PER_PAGE = 10;

    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeReferenceRepositoryInterface $references,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumeReferenceValidator $validator,
        private readonly ResumeReferencePolicy $policy,
        private readonly ResumeCompletionCalculator $completion
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function form(array $actor, int $resumeId, array $filters = [], int $page = 1): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canManage($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $listed = $this->references->listByResumeId($resumeId, $filters, $page, self::PER_PAGE);
        $items = array_map(
            static fn (array $row): ResumeReferenceDTO => ResumeReferenceDTO::fromRow($row, $canEdit),
            $listed['items']
        );
        $deleted = array_map(
            static fn (array $row): ResumeReferenceDTO => ResumeReferenceDTO::fromRow($row, $canEdit),
            $this->references->listDeletedByResumeId($resumeId)
        );

        $projectOptions = array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'title' => (string) $row['title']],
            $this->projects->listByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);
        $total = $listed['total'];
        $perPage = self::PER_PAGE;
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeReferenceDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'visibilities' => ResumeReferenceValidator::VISIBILITIES,
            'statuses' => ResumeReferenceValidator::STATUSES,
            'sorts' => ResumeReferenceValidator::SORTS,
            'relationships' => ResumeReferenceValidator::RELATIONSHIPS,
            'relationship_labels' => ResumeReferenceValidator::RELATIONSHIP_LABELS,
            'projects' => $projectOptions,
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
            'filters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'is_featured' => (string) ($filters['is_featured'] ?? ''),
                'visibility' => (string) ($filters['visibility'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'country_id' => (string) ($filters['country_id'] ?? ''),
                'permission_to_contact' => (string) ($filters['permission_to_contact'] ?? ''),
                'relationship' => (string) ($filters['relationship'] ?? ''),
                'sort' => (string) ($filters['sort'] ?? 'sort_order'),
            ],
            'pagination' => [
                'total' => $total,
                'page' => min($page, $lastPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/references/search',
            'cities_url' => '/jobseeker/resumes/' . $resumeId . '/references/cities',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array{id: int, name: string, country_id: int}>
     */
    public function citiesForCountry(array $actor, int $resumeId, int $countryId): array
    {
        $this->requireResume($actor, $resumeId, true);
        if ($countryId < 1 || !$this->locations->countryExists($countryId)) {
            return [];
        }

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'country_id' => (int) $row['country_id'],
        ], $this->locations->listCities($countryId));
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function search(array $actor, int $resumeId, string $query): array
    {
        $this->requireResume($actor, $resumeId, true);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'designation' => (string) ($row['designation'] ?? ''),
                'company' => (string) ($row['company'] ?? ''),
                'relationship' => (string) ($row['relationship'] ?? ''),
                'is_featured' => !empty($row['is_featured']),
                'visibility' => (string) ($row['visibility'] ?? 'private'),
                'permission_to_contact' => !empty($row['permission_to_contact']),
            ];
        }, $this->references->search($resumeId, $query));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $resumeId): array
    {
        $out = [];
        foreach ($this->references->listPublicByResumeId($resumeId) as $row) {
            $public = ResumeReferenceDTO::fromRow($row, false)->toPublicArray();
            if ($public !== null) {
                $out[] = $public;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEmployer(int $resumeId): array
    {
        $out = [];
        foreach ($this->references->listForEmployerByResumeId($resumeId) as $row) {
            $employer = ResumeReferenceDTO::fromRow($row, false)->toEmployerArray();
            if ($employer !== null) {
                $out[] = $employer;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $referenceId, array $filters = [], int $page = 1): array
    {
        $data = $this->form($actor, $resumeId, $filters, $page);
        $row = $this->references->findOwned($referenceId, $resumeId);
        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeReferenceDTO::fromRow($row, $data['can_edit']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array, id?: int}
     */
    public function store(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validateInput($resumeId, $input, null);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $newId = $this->references->create($resumeId, $this->normalize($input));
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Reference added.', 'completion' => $completion, 'id' => $newId];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $referenceId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validateInput($resumeId, $input, $referenceId);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if (!$this->references->update($referenceId, $resumeId, $this->normalize($input))) {
            return ['success' => false, 'message' => 'Reference not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Reference updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $referenceId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->references->delete($referenceId, $resumeId)) {
            return ['success' => false, 'message' => 'Reference not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Reference moved to trash.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $referenceId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->references->restore($referenceId, $resumeId)) {
            return ['success' => false, 'message' => 'Reference not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Reference restored.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, completion?: array}
     */
    public function reorder(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $ids = $input['order'] ?? $input['reference_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No reference order provided.'];
        }

        $owned = $this->references->listByResumeId($resumeId, [], 1, 500)['items'];
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid reference order.'];
        }

        $this->references->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Reference order updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    private function validateInput(int $resumeId, array $input, ?int $exceptId): array
    {
        return $this->validator->validate(
            $input,
            $this->projectOwnedChecker($resumeId),
            fn (int $id): bool => $this->locations->countryExists($id),
            fn (int $cityId, int $countryId): bool => $this->locations->cityBelongsToCountry($cityId, $countryId),
            function (string $name, ?string $company) use ($resumeId, $exceptId): bool {
                return $this->references->findDuplicate($resumeId, $name, $company, $exceptId) !== null;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $input = $this->validator->normalizeAliases($input);
        $yearsRaw = trim((string) ($input['years_known'] ?? ''));
        $yearsKnown = $yearsRaw !== '' ? round((float) $yearsRaw, 1) : null;

        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'designation' => $this->nullStr($input['designation'] ?? null),
            'company' => $this->nullStr($input['company'] ?? null),
            'email' => $this->nullStr($input['email'] ?? null),
            'phone' => $this->nullStr($input['phone'] ?? null),
            'relationship' => $this->nullStr($input['relationship'] ?? null),
            'years_known' => $yearsKnown,
            'permission_to_contact' => !empty($input['permission_to_contact']),
            'notes' => $this->nullStr($input['notes'] ?? null),
            'project_id' => $this->nullId($input['project_id'] ?? null),
            'country_id' => $this->nullId($input['country_id'] ?? null),
            'city_id' => $this->nullId($input['city_id'] ?? null),
            'is_featured' => !empty($input['is_featured']),
            'visibility' => $this->nullStr($input['visibility'] ?? null) ?? 'private',
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
    }

    private function projectOwnedChecker(int $resumeId): callable
    {
        $owned = [];
        foreach ($this->projects->listByResumeId($resumeId) as $row) {
            $owned[(int) $row['id']] = true;
        }

        return static fn (int $projectId): bool => isset($owned[$projectId]);
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

    private function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
