<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume-scoped language proficiency + optional certificate metadata.
 */
final class ResumeLanguageDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly int $languageId,
        public readonly string $languageName,
        public readonly ?string $languageCode,
        public readonly string $speaking,
        public readonly string $reading,
        public readonly string $writing,
        public readonly string $listening,
        public readonly bool $isNative,
        public readonly ?string $certificateType,
        public readonly ?string $certificateScore,
        public readonly ?string $certificateIssuedAt,
        public readonly ?string $certificateExpiresAt,
        public readonly ?string $certificatePath,
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
            languageId: (int) ($row['language_id'] ?? 0),
            languageName: trim((string) ($row['language_name'] ?? '')),
            languageCode: self::nullStr($row['language_code'] ?? null),
            speaking: (string) ($row['speaking'] ?? 'B1'),
            reading: (string) ($row['reading'] ?? 'B1'),
            writing: (string) ($row['writing'] ?? 'B1'),
            listening: (string) ($row['listening'] ?? 'B1'),
            isNative: !empty($row['is_native']),
            certificateType: self::nullStr($row['certificate_type'] ?? null),
            certificateScore: self::nullStr($row['certificate_score'] ?? null),
            certificateIssuedAt: self::nullStr($row['certificate_issued_at'] ?? null),
            certificateExpiresAt: self::nullStr($row['certificate_expires_at'] ?? null),
            certificatePath: self::nullStr($row['certificate_path'] ?? null),
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
            languageId: 0,
            languageName: '',
            languageCode: null,
            speaking: 'B1',
            reading: 'B1',
            writing: 'B1',
            listening: 'B1',
            isNative: false,
            certificateType: null,
            certificateScore: null,
            certificateIssuedAt: null,
            certificateExpiresAt: null,
            certificatePath: null,
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
            'language_id' => $this->languageId > 0 ? (string) $this->languageId : '',
            'language_name' => $this->languageName,
            'speaking' => $this->speaking,
            'reading' => $this->reading,
            'writing' => $this->writing,
            'listening' => $this->listening,
            'is_native' => $this->isNative ? '1' : '',
            'certificate_type' => $this->certificateType ?? '',
            'certificate_score' => $this->certificateScore ?? '',
            'certificate_issued_at' => $this->certificateIssuedAt ?? '',
            'certificate_expires_at' => $this->certificateExpiresAt ?? '',
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
