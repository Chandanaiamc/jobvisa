<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumePortfolioDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumePortfolioPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumePortfolioValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — professional portfolio (resume-scoped).
 */
final class ResumePortfolioService
{
    public const PER_PAGE = 10;

    public const MAX_GALLERY = 12;

    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumePortfolioRepositoryInterface $portfolios,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumePortfolioValidator $validator,
        private readonly ResumePortfolioPolicy $policy,
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

        $listed = $this->portfolios->listByResumeId($resumeId, $filters, $page, self::PER_PAGE);
        $items = array_map(
            static fn (array $row): ResumePortfolioDTO => ResumePortfolioDTO::fromRow($row, $canEdit),
            $listed['items']
        );
        $deleted = array_map(
            static fn (array $row): ResumePortfolioDTO => ResumePortfolioDTO::fromRow($row, $canEdit),
            $this->portfolios->listDeletedByResumeId($resumeId)
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
            'blank' => ResumePortfolioDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'categories' => ResumePortfolioValidator::CATEGORIES,
            'visibilities' => ResumePortfolioValidator::VISIBILITIES,
            'statuses' => ResumePortfolioValidator::STATUSES,
            'sorts' => ResumePortfolioValidator::SORTS,
            'projects' => $projectOptions,
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
            'filters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'category' => (string) ($filters['category'] ?? ''),
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
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/portfolio/search',
            'cities_url' => '/jobseeker/resumes/' . $resumeId . '/portfolio/cities',
            'max_gallery' => self::MAX_GALLERY,
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
                'category' => (string) ($row['category'] ?? ''),
                'is_featured' => !empty($row['is_featured']),
                'visibility' => (string) ($row['visibility'] ?? 'public'),
            ];
        }, $this->portfolios->search($resumeId, $query));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $resumeId): array
    {
        $out = [];
        foreach ($this->portfolios->listPublicByResumeId($resumeId) as $row) {
            $public = ResumePortfolioDTO::fromRow($row, false)->toPublicArray();
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
        foreach ($this->portfolios->listForEmployerByResumeId($resumeId) as $row) {
            $employer = ResumePortfolioDTO::fromRow($row, false)->toEmployerArray();
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
    public function editForm(array $actor, int $resumeId, int $portfolioId, array $filters = [], int $page = 1): array
    {
        $data = $this->form($actor, $resumeId, $filters, $page);
        $row = $this->portfolios->findOwned($portfolioId, $resumeId);
        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumePortfolioDTO::fromRow($row, $data['can_edit']),
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

        $newId = $this->portfolios->create($resumeId, $this->normalize($input));
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Portfolio item added.', 'completion' => $completion, 'id' => $newId];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $portfolioId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validateInput($resumeId, $input, $portfolioId);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if (!$this->portfolios->update($portfolioId, $resumeId, $this->normalize($input))) {
            return ['success' => false, 'message' => 'Portfolio item not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Portfolio item updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $portfolioId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->portfolios->delete($portfolioId, $resumeId)) {
            return ['success' => false, 'message' => 'Portfolio item not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Portfolio item moved to trash.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $portfolioId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->portfolios->restore($portfolioId, $resumeId)) {
            return ['success' => false, 'message' => 'Portfolio item not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Portfolio item restored.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, completion?: array}
     */
    public function reorder(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $ids = $input['order'] ?? $input['portfolio_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No portfolio order provided.'];
        }

        $owned = $this->portfolios->listByResumeId($resumeId, [], 1, 500)['items'];
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid portfolio order.'];
        }

        $this->portfolios->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return ['success' => true, 'message' => 'Portfolio order updated.', 'completion' => $completion];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadFeaturedImage(array $actor, int $resumeId, int $portfolioId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->portfolios->findOwned($portfolioId, $resumeId);
        if ($row === null) {
            return ['success' => false, 'message' => 'Portfolio item not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['featured_image_path'] ?? null) ? (string) $row['featured_image_path'] : null;
        $allowed = (array) config('uploads.portfolio_image_mimes');
        $maxBytes = (int) config('uploads.max_portfolio_image_bytes', 5_242_880);

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-portfolio/' . $userId . '/' . $resumeId,
                'feat',
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

        $this->portfolios->updateFeaturedImageMeta($portfolioId, $resumeId, [
            'path' => $path,
            'original_name' => $this->sanitizeOriginalName((string) ($file['name'] ?? 'image')),
            'mime' => $mime,
            'size' => $size > 0 ? $size : null,
        ]);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Featured image uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function removeFeaturedImage(array $actor, int $resumeId, int $portfolioId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->portfolios->findOwned($portfolioId, $resumeId);
        if ($row === null) {
            return ['success' => false, 'message' => 'Portfolio item not found.'];
        }

        $old = is_string($row['featured_image_path'] ?? null) ? (string) $row['featured_image_path'] : null;
        $this->portfolios->updateFeaturedImageMeta($portfolioId, $resumeId, [
            'path' => null,
            'original_name' => null,
            'mime' => null,
            'size' => null,
        ]);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Featured image removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function featuredImageDownload(array $actor, int $resumeId, int $portfolioId): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->portfolios->findOwned($portfolioId, $resumeId);
        if ($row === null || empty($row['featured_image_path'])) {
            return null;
        }

        $path = (string) $row['featured_image_path'];
        $absolute = $this->storage->absolutePath($path);
        if (!is_file($absolute)) {
            return null;
        }

        $name = $this->sanitizeOriginalName((string) ($row['featured_image_original_name'] ?? ''));
        if ($name === 'image' || $name === '') {
            $name = basename($path);
        }

        return ['path' => $absolute, 'name' => $name];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadGalleryImage(array $actor, int $resumeId, int $portfolioId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->portfolios->findOwned($portfolioId, $resumeId);
        if ($row === null) {
            return ['success' => false, 'message' => 'Portfolio item not found.'];
        }

        if ($this->portfolios->countGallery($portfolioId) >= self::MAX_GALLERY) {
            return ['success' => false, 'message' => 'Gallery may contain at most ' . self::MAX_GALLERY . ' images.'];
        }

        $userId = $aggregate->resume()->userId();
        $allowed = (array) config('uploads.portfolio_image_mimes');
        $maxBytes = (int) config('uploads.max_portfolio_image_bytes', 5_242_880);

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-portfolio/' . $userId . '/' . $resumeId . '/gallery',
                'gal',
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

        $this->portfolios->addGalleryImage($portfolioId, [
            'image_path' => $path,
            'original_name' => $this->sanitizeOriginalName((string) ($file['name'] ?? 'image')),
            'mime' => $mime,
            'file_size' => $size > 0 ? $size : null,
        ]);

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Gallery image uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function removeGalleryImage(array $actor, int $resumeId, int $portfolioId, int $imageId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $image = $this->portfolios->findGalleryOwned($imageId, $portfolioId, $resumeId);
        if ($image === null) {
            return ['success' => false, 'message' => 'Gallery image not found.'];
        }

        $old = is_string($image['image_path'] ?? null) ? (string) $image['image_path'] : null;
        $this->portfolios->softDeleteGalleryImage($imageId, $portfolioId);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Gallery image removed.'];
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
            function (string $title, string $category) use ($resumeId, $exceptId): bool {
                return $this->portfolios->findDuplicate($resumeId, $title, $category, $exceptId) !== null;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'category' => trim((string) ($input['category'] ?? '')),
            'description' => $this->nullStr($input['description'] ?? null),
            'portfolio_url' => $this->nullStr($input['portfolio_url'] ?? null),
            'github_url' => $this->nullStr($input['github_url'] ?? null),
            'behance_url' => $this->nullStr($input['behance_url'] ?? null),
            'dribbble_url' => $this->nullStr($input['dribbble_url'] ?? null),
            'figma_url' => $this->nullStr($input['figma_url'] ?? null),
            'youtube_url' => $this->nullStr($input['youtube_url'] ?? null),
            'google_drive_url' => $this->nullStr($input['google_drive_url'] ?? null),
            'project_id' => $this->nullId($input['project_id'] ?? null),
            'country_id' => $this->nullId($input['country_id'] ?? null),
            'city_id' => $this->nullId($input['city_id'] ?? null),
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
        $name = preg_replace('/[^\p{L}\p{N}\.\-_ ()\[\]]+/u', '_', $name) ?? 'image';
        $name = trim($name, '._ ');

        return $name === '' ? 'image' : mb_substr($name, 0, 200);
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
