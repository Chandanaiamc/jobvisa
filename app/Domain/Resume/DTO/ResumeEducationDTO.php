<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Single education record for a resume (maps to `education` table).
 */
final class ResumeEducationDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly ?string $school,
        public readonly string $institution,
        public readonly ?string $qualificationType,
        public readonly string $degree,
        public readonly ?string $fieldOfStudy,
        public readonly ?string $grade,
        public readonly ?int $countryId,
        public readonly ?string $countryName,
        public readonly ?string $city,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly bool $isCurrent,
        public readonly ?string $description,
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
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            school: self::nullStr($row['school'] ?? null),
            institution: trim((string) ($row['institution'] ?? '')),
            qualificationType: self::nullStr($row['qualification_type'] ?? null),
            degree: trim((string) ($row['degree'] ?? '')),
            fieldOfStudy: self::nullStr($row['field_of_study'] ?? null),
            grade: self::nullStr($row['grade'] ?? null),
            countryId: self::nullId($row['country_id'] ?? null),
            countryName: self::nullStr($row['country_name'] ?? null),
            city: self::nullStr($row['city'] ?? null),
            startDate: self::nullStr($row['start_date'] ?? null),
            endDate: self::nullStr($row['end_date'] ?? null),
            isCurrent: !empty($row['is_current']),
            description: self::nullStr($row['description'] ?? null),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            status: (string) ($row['status'] ?? 'active'),
            createdAt: self::nullStr($row['created_at'] ?? null),
            updatedAt: self::nullStr($row['updated_at'] ?? null),
            deletedAt: self::nullStr($row['deleted_at'] ?? null),
            canEdit: $canEdit,
        );
    }

    /**
     * Empty form defaults for "Add education".
     */
    public static function blank(int $resumeId, bool $canEdit): self
    {
        return new self(
            id: null,
            resumeId: $resumeId,
            school: null,
            institution: '',
            qualificationType: null,
            degree: '',
            fieldOfStudy: null,
            grade: null,
            countryId: null,
            countryName: null,
            city: null,
            startDate: null,
            endDate: null,
            isCurrent: false,
            description: null,
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
            'school' => $this->school ?? '',
            'institution' => $this->institution,
            'qualification_type' => $this->qualificationType ?? '',
            'degree' => $this->degree,
            'field_of_study' => $this->fieldOfStudy ?? '',
            'grade' => $this->grade ?? '',
            'country_id' => $this->countryId ?? '',
            'city' => $this->city ?? '',
            'start_date' => $this->startDate ?? '',
            'end_date' => $this->endDate ?? '',
            'is_current' => $this->isCurrent ? '1' : '',
            'description' => $this->description ?? '',
            'sort_order' => (string) $this->sortOrder,
            'status' => $this->status,
        ];
    }

    public function isValidForCompletion(): bool
    {
        return $this->institution !== ''
            && $this->degree !== ''
            && $this->startDate !== null
            && $this->deletedAt === null
            && ($this->isCurrent || $this->endDate !== null);
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
