<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume professional reference record.
 */
final class ResumeReferenceDTO
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
        public readonly string $name,
        public readonly ?string $designation,
        public readonly ?string $company,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $relationship,
        public readonly ?float $yearsKnown,
        public readonly bool $permissionToContact,
        public readonly ?string $notes,
        public readonly bool $isFeatured,
        public readonly string $visibility,
        public readonly string $status,
        public readonly int $sortOrder,
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
        $yearsKnown = null;
        if (isset($row['years_known']) && $row['years_known'] !== null && $row['years_known'] !== '') {
            $yearsKnown = (float) $row['years_known'];
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
            name: trim((string) ($row['name'] ?? '')),
            designation: self::nullStr($row['designation'] ?? null),
            company: self::nullStr($row['company'] ?? null),
            email: self::nullStr($row['email'] ?? null),
            phone: self::nullStr($row['phone'] ?? null),
            relationship: self::nullStr($row['relationship'] ?? null),
            yearsKnown: $yearsKnown,
            permissionToContact: !empty($row['permission_to_contact']),
            notes: self::nullStr($row['notes'] ?? null),
            isFeatured: !empty($row['is_featured']),
            visibility: (string) ($row['visibility'] ?? 'private'),
            status: (string) ($row['status'] ?? 'active'),
            sortOrder: (int) ($row['sort_order'] ?? 0),
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
            name: '',
            designation: null,
            company: null,
            email: null,
            phone: null,
            relationship: null,
            yearsKnown: null,
            permissionToContact: false,
            notes: null,
            isFeatured: false,
            visibility: 'private',
            status: 'active',
            sortOrder: 0,
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
            'name' => $this->name,
            'designation' => $this->designation ?? '',
            'position' => $this->designation ?? '',
            'company' => $this->company ?? '',
            'email' => $this->email ?? '',
            'phone' => $this->phone ?? '',
            'relationship' => $this->relationship ?? '',
            'years_known' => $this->yearsKnown !== null ? (string) $this->yearsKnown : '',
            'permission_to_contact' => $this->permissionToContact ? '1' : '',
            'notes' => $this->notes ?? '',
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
     * Email and phone are never exposed.
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
            'name' => $this->name,
            'designation' => $this->designation,
            'position' => $this->designation,
            'company' => $this->company,
            'relationship' => $this->relationship,
            'years_known' => $this->yearsKnown,
            'permission_to_contact' => $this->permissionToContact,
            'project_id' => $this->projectId,
            'project_title' => $this->projectTitle,
            'country_name' => $this->countryName,
            'city_name' => $this->cityName,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * Employer projection for public + employers visibility.
     * Email/phone included only when permission_to_contact is true.
     * Private references never returned.
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

        $out = [
            'id' => $this->id,
            'name' => $this->name,
            'designation' => $this->designation,
            'position' => $this->designation,
            'company' => $this->company,
            'relationship' => $this->relationship,
            'years_known' => $this->yearsKnown,
            'permission_to_contact' => $this->permissionToContact,
            'project_id' => $this->projectId,
            'project_title' => $this->projectTitle,
            'country_name' => $this->countryName,
            'city_name' => $this->cityName,
            'is_featured' => $this->isFeatured,
            'visibility' => $this->visibility,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->permissionToContact) {
            $out['email'] = $this->email;
            $out['phone'] = $this->phone;
        }

        return $out;
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
