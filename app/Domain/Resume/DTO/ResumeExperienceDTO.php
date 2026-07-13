<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Work experience record for a resume (maps to `work_experience`).
 * Private fields: reason_for_leaving, supervisor_contact — omit from public views.
 */
final class ResumeExperienceDTO
{
    /**
     * @param  list<array{id: int, name: string}>  $skills
     * @param  list<int>  $skillIds
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly string $companyName,
        public readonly string $jobTitle,
        public readonly ?string $employmentType,
        public readonly ?string $industry,
        public readonly ?int $countryId,
        public readonly ?string $countryName,
        public readonly ?string $city,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly bool $isCurrent,
        public readonly ?string $responsibilities,
        public readonly ?string $achievements,
        public readonly ?string $reasonForLeaving,
        public readonly ?string $supervisorName,
        public readonly ?string $supervisorContact,
        public readonly array $skills,
        public readonly array $skillIds,
        public readonly int $sortOrder,
        public readonly string $status,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly bool $canEdit,
        public readonly bool $includePrivate,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array{id: int, name: string}>  $skills
     */
    public static function fromRow(array $row, array $skills, bool $canEdit, bool $includePrivate): self
    {
        $responsibilities = self::nullStr($row['responsibilities'] ?? null)
            ?? self::nullStr($row['description'] ?? null);
        $skillIds = array_map(static fn (array $s): int => (int) $s['id'], $skills);

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            companyName: trim((string) ($row['company_name'] ?? '')),
            jobTitle: trim((string) ($row['job_title'] ?? '')),
            employmentType: self::nullStr($row['employment_type'] ?? null),
            industry: self::nullStr($row['industry'] ?? null),
            countryId: self::nullId($row['country_id'] ?? null),
            countryName: self::nullStr($row['country_name'] ?? null),
            city: self::nullStr($row['city'] ?? null),
            startDate: self::nullStr($row['start_date'] ?? null),
            endDate: self::nullStr($row['end_date'] ?? null),
            isCurrent: !empty($row['is_current']),
            responsibilities: $responsibilities,
            achievements: self::nullStr($row['achievements'] ?? null),
            reasonForLeaving: $includePrivate ? self::nullStr($row['reason_for_leaving'] ?? null) : null,
            supervisorName: self::nullStr($row['supervisor_name'] ?? null),
            supervisorContact: $includePrivate ? self::nullStr($row['supervisor_contact'] ?? null) : null,
            skills: $skills,
            skillIds: $skillIds,
            sortOrder: (int) ($row['sort_order'] ?? 0),
            status: (string) ($row['status'] ?? 'active'),
            createdAt: self::nullStr($row['created_at'] ?? null),
            updatedAt: self::nullStr($row['updated_at'] ?? null),
            deletedAt: self::nullStr($row['deleted_at'] ?? null),
            canEdit: $canEdit,
            includePrivate: $includePrivate,
        );
    }

    public static function blank(int $resumeId, bool $canEdit): self
    {
        return new self(
            id: null,
            resumeId: $resumeId,
            companyName: '',
            jobTitle: '',
            employmentType: null,
            industry: null,
            countryId: null,
            countryName: null,
            city: null,
            startDate: null,
            endDate: null,
            isCurrent: false,
            responsibilities: null,
            achievements: null,
            reasonForLeaving: null,
            supervisorName: null,
            supervisorContact: null,
            skills: [],
            skillIds: [],
            sortOrder: 0,
            status: 'active',
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            canEdit: $canEdit,
            includePrivate: true,
        );
    }

    /**
     * Owner/admin form values (includes private fields).
     *
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'job_title' => $this->jobTitle,
            'employment_type' => $this->employmentType ?? '',
            'industry' => $this->industry ?? '',
            'country_id' => $this->countryId ?? '',
            'city' => $this->city ?? '',
            'start_date' => $this->startDate ?? '',
            'end_date' => $this->endDate ?? '',
            'is_current' => $this->isCurrent ? '1' : '',
            'responsibilities' => $this->responsibilities ?? '',
            'achievements' => $this->achievements ?? '',
            'reason_for_leaving' => $this->reasonForLeaving ?? '',
            'supervisor_name' => $this->supervisorName ?? '',
            'supervisor_contact' => $this->supervisorContact ?? '',
            'skill_ids' => $this->skillIds,
            'sort_order' => (string) $this->sortOrder,
            'status' => $this->status,
        ];
    }

    /**
     * Public-safe projection (excludes reason_for_leaving + supervisor_contact).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->companyName,
            'job_title' => $this->jobTitle,
            'employment_type' => $this->employmentType,
            'industry' => $this->industry,
            'country_name' => $this->countryName,
            'city' => $this->city,
            'start_date' => $this->startDate,
            'end_date' => $this->isCurrent ? null : $this->endDate,
            'is_current' => $this->isCurrent,
            'responsibilities' => $this->responsibilities,
            'achievements' => $this->achievements,
            'supervisor_name' => $this->supervisorName,
            'skills' => $this->skills,
            'status' => $this->status,
        ];
    }

    public function isValidForCompletion(): bool
    {
        return $this->companyName !== ''
            && $this->jobTitle !== ''
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
