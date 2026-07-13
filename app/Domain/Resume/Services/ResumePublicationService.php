<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumePublicationDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumePublicationPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumePublicationValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePublicationRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — publications & research (resume-scoped).
 */
final class ResumePublicationService
{
    public const PER_PAGE = 10;

    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumePublicationRepositoryInterface $publications,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumePublicationValidator $validator,
        private readonly ResumePublicationPolicy $policy,
        private readonly ResumeCompletionCalculator $completion,
        private readonly FileStorage $storage
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

        $listed = $this->publications->listByResumeId($resumeId, $filters, $page, self::PER_PAGE);
        $items = array_map(
            static fn (array $row): ResumePublicationDTO => ResumePublicationDTO::fromRow($row, $canEdit),
            $listed['items']
        );
        $deleted = array_map(
            static fn (array $row): ResumePublicationDTO => ResumePublicationDTO::fromRow($row, $canEdit),
            $this->publications->listDeletedByResumeId($resumeId)
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
            'blank' => ResumePublicationDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'types' => ResumePublicationValidator::TYPES,
            'visibilities' => ResumePublicationValidator::VISIBILITIES,
            'statuses' => ResumePublicationValidator::STATUSES,
            'sorts' => ResumePublicationValidator::SORTS,
            'projects' => $projectOptions,
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
            'filters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'publication_type' => (string) ($filters['publication_type'] ?? ''),
                'publication_year' => (string) ($filters['publication_year'] ?? ''),
                'is_peer_reviewed' => (string) ($filters['is_peer_reviewed'] ?? ''),
                'is_featured' => (string) ($filters['is_featured'] ?? ''),
                'visibility' => (string) ($filters['visibility'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'country_id' => (string) ($filters['country_id'] ?? ''),
                'sort' => (string) ($filters['sort'] ?? 'sort_order'),
            ],
            'pagination' => [
                'total' => $total,
                'page' => min($page, $lastPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/publications/search',
            'cities_url' => '/jobseeker/resumes/' . $resumeId . '/publications/cities',
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
                'title' => (string) $row['title'],
                'publication_type' => (string) ($row['publication_type'] ?? ''),
                'publisher' => (string) ($row['publisher'] ?? ''),
                'publication_year' => $row['publication_year'] ?? null,
                'is_featured' => !empty($row['is_featured']),
                'visibility' => (string) ($row['visibility'] ?? 'public'),
            ];
        }, $this->publications->search($resumeId, $query));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $resumeId): array
    {
        $out = [];
        foreach ($this->publications->listPublicByResumeId($resumeId) as $row) {
            $public = ResumePublicationDTO::fromRow($row, false)->toPublicArray();
            if ($public !== null) {
                $out[] = $public;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $publicationId, array $filters = [], int $page = 1): array
    {
        $data = $this->form($actor, $resumeId, $filters, $page);
        $row = $this->publications->findOwned($publicationId, $resumeId);
        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumePublicationDTO::fromRow($row, $data['can_edit']),
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

        $newId = $this->publications->create($resumeId, $this->normalize($input));
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Publication added.', 'completion' => $completion, 'id' => $newId];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $publicationId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validateInput($resumeId, $input, $publicationId);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if (!$this->publications->update($publicationId, $resumeId, $this->normalize($input))) {
            return ['success' => false, 'message' => 'Publication not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Publication updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $publicationId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->publications->delete($publicationId, $resumeId)) {
            return ['success' => false, 'message' => 'Publication not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Publication moved to trash.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $publicationId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->publications->restore($publicationId, $resumeId)) {
            return ['success' => false, 'message' => 'Publication not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Publication restored.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, completion?: array}
     */
    public function reorder(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $ids = $input['order'] ?? $input['publication_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No publication order provided.'];
        }

        $owned = $this->publications->listByResumeId($resumeId, [], 1, 500)['items'];
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid publication order.'];
        }

        $this->publications->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Publication order updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadDocument(array $actor, int $resumeId, int $publicationId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->publications->findOwned($publicationId, $resumeId);
        if ($row === null) {
            return ['success' => false, 'message' => 'Publication not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['document_path'] ?? null) ? (string) $row['document_path'] : null;
        $allowed = (array) config('uploads.publication_mimes');
        $maxBytes = (int) config('uploads.max_publication_bytes', 10_485_760);

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-publications/' . $userId . '/' . $resumeId,
                'pub',
                $allowed,
                $maxBytes
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $absolute = $this->storage->absolutePath($path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = is_file($absolute) ? (string) $finfo->file($absolute) : null;
        $size = is_file($absolute) ? (int) filesize($absolute) : (int) ($file['size'] ?? 0);

        $this->publications->updateDocumentMeta($publicationId, $resumeId, [
            'path' => $path,
            'original_name' => $this->sanitizeOriginalName((string) ($file['name'] ?? 'document')),
            'mime' => $mime,
            'size' => $size > 0 ? $size : null,
        ]);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Document uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function removeDocument(array $actor, int $resumeId, int $publicationId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->publications->findOwned($publicationId, $resumeId);
        if ($row === null) {
            return ['success' => false, 'message' => 'Publication not found.'];
        }

        $old = is_string($row['document_path'] ?? null) ? (string) $row['document_path'] : null;
        $this->publications->updateDocumentMeta($publicationId, $resumeId, [
            'path' => null,
            'original_name' => null,
            'mime' => null,
            'size' => null,
        ]);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Document removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function documentDownload(array $actor, int $resumeId, int $publicationId): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->publications->findOwned($publicationId, $resumeId);
        if ($row === null || empty($row['document_path'])) {
            return null;
        }

        $path = (string) $row['document_path'];
        $absolute = $this->storage->absolutePath($path);
        if (!is_file($absolute)) {
            return null;
        }

        $name = $this->sanitizeOriginalName((string) ($row['document_original_name'] ?? ''));
        if ($name === 'document' || $name === '') {
            $name = basename($path);
        }

        return ['path' => $absolute, 'name' => $name];
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
            function (string $title, ?string $publisher, ?int $year) use ($resumeId, $exceptId): bool {
                return $this->publications->findDuplicate($resumeId, $title, $publisher, $year, $exceptId) !== null;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $date = $this->nullStr($input['publication_date'] ?? null);
        $yearRaw = trim((string) ($input['publication_year'] ?? ''));
        $year = $yearRaw !== '' ? (int) $yearRaw : ($date !== null ? (int) substr($date, 0, 4) : null);

        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'publication_type' => trim((string) ($input['publication_type'] ?? '')),
            'publisher' => $this->nullStr($input['publisher'] ?? null),
            'authors' => $this->nullStr($input['authors'] ?? null),
            'user_contribution' => $this->nullStr($input['user_contribution'] ?? null),
            'publication_date' => $date,
            'publication_year' => $year,
            'volume' => $this->nullStr($input['volume'] ?? null),
            'issue' => $this->nullStr($input['issue'] ?? null),
            'page_range' => $this->nullStr($input['page_range'] ?? null),
            'doi' => $this->nullStr($input['doi'] ?? null),
            'isbn' => $this->nullStr($input['isbn'] ?? null),
            'issn' => $this->nullStr($input['issn'] ?? null),
            'patent_number' => $this->nullStr($input['patent_number'] ?? null),
            'conference_name' => $this->nullStr($input['conference_name'] ?? null),
            'abstract_summary' => $this->nullStr($input['abstract_summary'] ?? null),
            'keywords' => $this->nullStr($input['keywords'] ?? null),
            'publication_url' => $this->nullStr($input['publication_url'] ?? null),
            'project_id' => $this->nullId($input['project_id'] ?? null),
            'country_id' => $this->nullId($input['country_id'] ?? null),
            'city_id' => $this->nullId($input['city_id'] ?? null),
            'is_peer_reviewed' => !empty($input['is_peer_reviewed']),
            'is_featured' => !empty($input['is_featured']),
            'visibility' => $this->nullStr($input['visibility'] ?? null) ?? 'public',
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

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(str_replace(["\0", '\\'], '', $name));
        $name = preg_replace('/[^\p{L}\p{N}\.\-_ ()\[\]]+/u', '_', $name) ?? 'document';
        $name = trim($name, '._ ');

        return $name === '' ? 'document' : mb_substr($name, 0, 200);
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
