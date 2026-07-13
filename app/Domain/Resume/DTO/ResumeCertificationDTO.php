<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume certification / licence record.
 */
final class ResumeCertificationDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly string $name,
        public readonly string $issuingOrganization,
        public readonly ?string $credentialId,
        public readonly ?string $credentialUrl,
        public readonly ?string $issueDate,
        public readonly ?string $expiryDate,
        public readonly bool $doesNotExpire,
        public readonly ?string $licenseNumber,
        public readonly ?string $verificationUrl,
        public readonly ?string $certificatePath,
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
            name: trim((string) ($row['name'] ?? '')),
            issuingOrganization: trim((string) ($row['issuing_organization'] ?? '')),
            credentialId: self::nullStr($row['credential_id'] ?? null),
            credentialUrl: self::nullStr($row['credential_url'] ?? null),
            issueDate: self::nullStr($row['issue_date'] ?? null),
            expiryDate: self::nullStr($row['expiry_date'] ?? null),
            doesNotExpire: !empty($row['does_not_expire']),
            licenseNumber: self::nullStr($row['license_number'] ?? null),
            verificationUrl: self::nullStr($row['verification_url'] ?? null),
            certificatePath: self::nullStr($row['certificate_path'] ?? null),
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
            name: '',
            issuingOrganization: '',
            credentialId: null,
            credentialUrl: null,
            issueDate: null,
            expiryDate: null,
            doesNotExpire: false,
            licenseNumber: null,
            verificationUrl: null,
            certificatePath: null,
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
            'name' => $this->name,
            'issuing_organization' => $this->issuingOrganization,
            'credential_id' => $this->credentialId ?? '',
            'credential_url' => $this->credentialUrl ?? '',
            'issue_date' => $this->issueDate ?? '',
            'expiry_date' => $this->expiryDate ?? '',
            'does_not_expire' => $this->doesNotExpire ? '1' : '',
            'license_number' => $this->licenseNumber ?? '',
            'verification_url' => $this->verificationUrl ?? '',
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
