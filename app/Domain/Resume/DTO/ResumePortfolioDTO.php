<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume professional portfolio record.
 */
final class ResumePortfolioDTO
{
    /**
     * @param  list<array<string, mixed>>  $galleryImages
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly ?int $projectId,
        public readonly ?string $projectTitle,
        public readonly ?int $countryId,
        public readonly ?string $countryName,
        public readonly ?int $cityId,
        public readonly ?string $cityName,
        public readonly string $title,
        public readonly string $category,
        public readonly ?string $description,
        public readonly ?string $portfolioUrl,
        public readonly ?string $githubUrl,
        public readonly ?string $behanceUrl,
        public readonly ?string $dribbbleUrl,
        public readonly ?string $figmaUrl,
        public readonly ?string $youtubeUrl,
        public readonly ?string $googleDriveUrl,
        public readonly ?string $featuredImagePath,
        public readonly ?string $featuredImageOriginalName,
        public readonly ?string $featuredImageMime,
        public readonly ?int $featuredImageSize,
        public readonly bool $isFeatured,
        public readonly string $visibility,
        public readonly string $status,
        public readonly int $sortOrder,
        public readonly array $galleryImages,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row, bool $canEdit): self
    {
        $gallery = [];
        if (isset($row['gallery_images']) && is_array($row['gallery_images'])) {
            foreach ($row['gallery_images'] as $image) {
                if (is_array($image)) {
                    $gallery[] = $image;
                }
            }
        }

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            projectId: self::nullId($row['project_id'] ?? null),
            projectTitle: self::nullStr($row['project_title'] ?? null),
            countryId: self::nullId($row['country_id'] ?? null),
            countryName: self::nullStr($row['country_name'] ?? null),
            cityId: self::nullId($row['city_id'] ?? null),
            cityName: self::nullStr($row['city_name'] ?? null),
            title: trim((string) ($row['title'] ?? '')),
            category: (string) ($row['category'] ?? 'other'),
            description: self::nullStr($row['description'] ?? null),
            portfolioUrl: self::nullStr($row['portfolio_url'] ?? null),
            githubUrl: self::nullStr($row['github_url'] ?? null),
            behanceUrl: self::nullStr($row['behance_url'] ?? null),
            dribbbleUrl: self::nullStr($row['dribbble_url'] ?? null),
            figmaUrl: self::nullStr($row['figma_url'] ?? null),
            youtubeUrl: self::nullStr($row['youtube_url'] ?? null),
            googleDriveUrl: self::nullStr($row['google_drive_url'] ?? null),
            featuredImagePath: self::nullStr($row['featured_image_path'] ?? null),
            featuredImageOriginalName: self::nullStr($row['featured_image_original_name'] ?? null),
            featuredImageMime: self::nullStr($row['featured_image_mime'] ?? null),
            featuredImageSize: isset($row['featured_image_size']) && $row['featured_image_size'] !== null && $row['featured_image_size'] !== ''
                ? (int) $row['featured_image_size']
                : null,
            isFeatured: !empty($row['is_featured']),
            visibility: (string) ($row['visibility'] ?? 'public'),
            status: (string) ($row['status'] ?? 'active'),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            galleryImages: $gallery,
            createdAt: self::nullStr($row['created_at'] ?? null),
            updatedAt: self::nullStr($row['updated_at'] ?? null),
            deletedAt: self::nullStr($row['deleted_at'] ?? null),
            canEdit: $canEdit,
        );
    }

    public static function blank(int $resumeId, bool $canEdit): self
    {
        return new self(
            id: null,
            resumeId: $resumeId,
            projectId: null,
            projectTitle: null,
            countryId: null,
            countryName: null,
            cityId: null,
            cityName: null,
            title: '',
            category: '',
            description: null,
            portfolioUrl: null,
            githubUrl: null,
            behanceUrl: null,
            dribbbleUrl: null,
            figmaUrl: null,
            youtubeUrl: null,
            googleDriveUrl: null,
            featuredImagePath: null,
            featuredImageOriginalName: null,
            featuredImageMime: null,
            featuredImageSize: null,
            isFeatured: false,
            visibility: 'public',
            status: 'active',
            sortOrder: 0,
            galleryImages: [],
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            canEdit: $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description ?? '',
            'portfolio_url' => $this->portfolioUrl ?? '',
            'github_url' => $this->githubUrl ?? '',
            'behance_url' => $this->behanceUrl ?? '',
            'dribbble_url' => $this->dribbbleUrl ?? '',
            'figma_url' => $this->figmaUrl ?? '',
            'youtube_url' => $this->youtubeUrl ?? '',
            'google_drive_url' => $this->googleDriveUrl ?? '',
            'project_id' => $this->projectId !== null ? (string) $this->projectId : '',
            'country_id' => $this->countryId !== null ? (string) $this->countryId : '',
            'city_id' => $this->cityId !== null ? (string) $this->cityId : '',
            'is_featured' => $this->isFeatured ? '1' : '',
            'visibility' => $this->visibility,
            'status' => $this->status,
            'sort_order' => (string) $this->sortOrder,
        ];
    }

    /**
     * Public profile projection. Only public + active rows.
     * Featured image path and public gallery paths are included (relative storage paths).
     *
     * @return array<string, mixed>|null
     */
    public function toPublicArray(): ?array
    {
        if ($this->visibility !== 'public' || $this->deletedAt !== null || $this->status !== 'active') {
            return null;
        }

        return $this->sharedProjection(true);
    }

    /**
     * Employer-facing projection. Public + employers visibility; private hidden.
     *
     * @return array<string, mixed>|null
     */
    public function toEmployerArray(): ?array
    {
        if (
            !in_array($this->visibility, ['public', 'employers'], true)
            || $this->deletedAt !== null
            || $this->status !== 'active'
        ) {
            return null;
        }

        return $this->sharedProjection(false);
    }

    public function hasFeaturedImage(): bool
    {
        return $this->featuredImagePath !== null && $this->featuredImagePath !== '';
    }

    public function galleryCount(): int
    {
        return count($this->galleryImages);
    }

    /**
     * Contact-safe shared fields for public / employer consumers.
     *
     * @return array<string, mixed>
     */
    private function sharedProjection(bool $publicOnly): array
    {
        $galleryPaths = [];
        foreach ($this->galleryImages as $image) {
            $path = self::nullStr($image['image_path'] ?? null);
            if ($path !== null) {
                $galleryPaths[] = $path;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description,
            'portfolio_url' => $this->portfolioUrl,
            'github_url' => $this->githubUrl,
            'behance_url' => $this->behanceUrl,
            'dribbble_url' => $this->dribbbleUrl,
            'figma_url' => $this->figmaUrl,
            'youtube_url' => $this->youtubeUrl,
            'google_drive_url' => $this->googleDriveUrl,
            'project_id' => $this->projectId,
            'project_title' => $this->projectTitle,
            'country_name' => $this->countryName,
            'city_name' => $this->cityName,
            'is_featured' => $this->isFeatured,
            'has_featured_image' => $this->hasFeaturedImage(),
            'featured_image_path' => $this->hasFeaturedImage() ? $this->featuredImagePath : null,
            'gallery_images' => $galleryPaths,
            'gallery_count' => count($galleryPaths),
            'sort_order' => $this->sortOrder,
            'visibility' => $publicOnly ? 'public' : $this->visibility,
        ];
    }

    private static function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
