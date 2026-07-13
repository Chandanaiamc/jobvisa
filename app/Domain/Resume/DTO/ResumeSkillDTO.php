<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume-scoped skill row (catalogue skill + proficiency metadata).
 */
final class ResumeSkillDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly int $skillId,
        public readonly string $skillName,
        public readonly ?string $skillSlug,
        public readonly string $level,
        public readonly ?string $yearsExperience,
        public readonly ?int $lastUsedYear,
        public readonly bool $isPrimary,
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
            skillId: (int) ($row['skill_id'] ?? 0),
            skillName: trim((string) ($row['skill_name'] ?? '')),
            skillSlug: self::nullStr($row['skill_slug'] ?? null),
            level: (string) ($row['level'] ?? 'intermediate'),
            yearsExperience: isset($row['years_experience']) && $row['years_experience'] !== null && $row['years_experience'] !== ''
                ? (string) $row['years_experience']
                : null,
            lastUsedYear: isset($row['last_used_year']) && $row['last_used_year'] !== null && $row['last_used_year'] !== ''
                ? (int) $row['last_used_year']
                : null,
            isPrimary: !empty($row['is_primary']),
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
            skillId: 0,
            skillName: '',
            skillSlug: null,
            level: 'intermediate',
            yearsExperience: null,
            lastUsedYear: null,
            isPrimary: false,
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
            'skill_id' => $this->skillId > 0 ? (string) $this->skillId : '',
            'skill_name' => $this->skillName,
            'level' => $this->level,
            'years_experience' => $this->yearsExperience ?? '',
            'last_used_year' => $this->lastUsedYear ?? '',
            'is_primary' => $this->isPrimary ? '1' : '',
            'sort_order' => (string) $this->sortOrder,
            'status' => $this->status,
        ];
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
