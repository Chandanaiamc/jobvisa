<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume award / achievement record.
 */
final class ResumeAchievementDTO
{
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
        public readonly ?string $issuer,
        public readonly ?string $description,
        public readonly ?string $remarks,
        public readonly ?string $achievementType,
        public readonly ?string $awardLevel,
        public readonly ?string $rankOrPlacement,
        public readonly ?string $achievementDate,
        public readonly ?string $credentialUrl,
        public readonly ?string $certificatePath,
        public readonly ?string $certificateOriginalName,
        public readonly ?string $certificateMime,
        public readonly ?int $certificateSize,
        public readonly bool $isFeatured,
        public readonly string $visibility,
        public readonly int $sortOrder,
        public readonly string $status,
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
        $projectId = self::nullId($row['project_id'] ?? null);
        $countryId = self::nullId($row['country_id'] ?? null);
        $cityId = self::nullId($row['city_id'] ?? null);
        $certSize = isset($row['certificate_size']) && $row['certificate_size'] !== null && $row['certificate_size'] !== ''
            ? (int) $row['certificate_size']
            : null;

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            projectId: $projectId,
            projectTitle: self::nullStr($row['project_title'] ?? null),
            countryId: $countryId,
            countryName: self::nullStr($row['country_name'] ?? null),
            cityId: $cityId,
            cityName: self::nullStr($row['city_name'] ?? null),
            title: trim((string) ($row['title'] ?? '')),
            issuer: self::nullStr($row['issuer'] ?? null),
            description: self::nullStr($row['description'] ?? null),
            remarks: self::nullStr($row['remarks'] ?? null),
            achievementType: self::nullStr($row['achievement_type'] ?? null),
            awardLevel: self::nullStr($row['award_level'] ?? null),
            rankOrPlacement: self::nullStr($row['rank_or_placement'] ?? null),
            achievementDate: self::nullStr($row['achievement_date'] ?? null),
            credentialUrl: self::nullStr($row['credential_url'] ?? null),
            certificatePath: self::nullStr($row['certificate_path'] ?? null),
            certificateOriginalName: self::nullStr($row['certificate_original_name'] ?? null),
            certificateMime: self::nullStr($row['certificate_mime'] ?? null),
            certificateSize: $certSize,
            isFeatured: !empty($row['is_featured']),
            visibility: (string) ($row['visibility'] ?? 'public'),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            status: (string) ($row['status'] ?? 'active'),
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
            issuer: null,
            description: null,
            remarks: null,
            achievementType: null,
            awardLevel: null,
            rankOrPlacement: null,
            achievementDate: null,
            credentialUrl: null,
            certificatePath: null,
            certificateOriginalName: null,
            certificateMime: null,
            certificateSize: null,
            isFeatured: false,
            visibility: 'public',
            sortOrder: 0,
            status: 'active',
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
            'issuer' => $this->issuer ?? '',
            'description' => $this->description ?? '',
            'remarks' => $this->remarks ?? '',
            'achievement_type' => $this->achievementType ?? '',
            'award_level' => $this->awardLevel ?? '',
            'rank_or_placement' => $this->rankOrPlacement ?? '',
            'achievement_date' => $this->achievementDate ?? '',
            'credential_url' => $this->credentialUrl ?? '',
            'project_id' => $this->projectId !== null ? (string) $this->projectId : '',
            'country_id' => $this->countryId !== null ? (string) $this->countryId : '',
            'city_id' => $this->cityId !== null ? (string) $this->cityId : '',
            'is_featured' => $this->isFeatured ? '1' : '',
            'visibility' => $this->visibility,
            'sort_order' => (string) $this->sortOrder,
            'status' => $this->status,
        ];
    }

    /**
     * Public profile projection. Private achievements return null.
     * Certificate path / mime / size / original name are never exposed.
     *
     * @return array<string, mixed>|null
     */
    public function toPublicArray(): ?array
    {
        if ($this->visibility !== 'public' || $this->deletedAt !== null || $this->status !== 'active') {
            return null;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'issuer' => $this->issuer,
            'description' => $this->description,
            'remarks' => $this->remarks,
            'achievement_type' => $this->achievementType,
            'award_level' => $this->awardLevel,
            'rank_or_placement' => $this->rankOrPlacement,
            'achievement_date' => $this->achievementDate,
            'credential_url' => $this->credentialUrl,
            'project_id' => $this->projectId,
            'project_title' => $this->projectTitle,
            'country_id' => $this->countryId,
            'country_name' => $this->countryName,
            'city_id' => $this->cityId,
            'city_name' => $this->cityName,
            'is_featured' => $this->isFeatured,
            'has_certificate' => $this->certificatePath !== null && $this->certificatePath !== '',
            'sort_order' => $this->sortOrder,
        ];
    }

    public function hasCertificate(): bool
    {
        return $this->certificatePath !== null && $this->certificatePath !== '';
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
