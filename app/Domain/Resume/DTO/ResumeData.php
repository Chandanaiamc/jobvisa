<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Support\DataTransferObject;

/**
 * Resume data transfer object for application / presentation layers.
 */
final class ResumeData extends DataTransferObject
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $status,
        public readonly string $visibility,
        public readonly bool $isDefault,
        public readonly int $completenessScore,
        public readonly ?string $filePath,
        public readonly ?string $fileMime,
        public readonly ?int $fileSizeBytes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $deletedAt,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            id: isset($data['id']) ? (int) $data['id'] : null,
            userId: (int) ($data['user_id'] ?? 0),
            title: (string) ($data['title'] ?? ''),
            status: (string) ($data['status'] ?? Resume::STATUS_DRAFT),
            visibility: (string) ($data['visibility'] ?? Resume::VISIBILITY_EMPLOYERS),
            isDefault: !empty($data['is_primary']) || !empty($data['is_default']),
            completenessScore: (int) ($data['completeness_score'] ?? 0),
            filePath: isset($data['file_path']) && $data['file_path'] !== '' ? (string) $data['file_path'] : null,
            fileMime: isset($data['file_mime']) && $data['file_mime'] !== '' ? (string) $data['file_mime'] : null,
            fileSizeBytes: isset($data['file_size_bytes']) ? (int) $data['file_size_bytes'] : null,
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
            deletedAt: isset($data['deleted_at']) && $data['deleted_at'] !== null && $data['deleted_at'] !== ''
                ? (string) $data['deleted_at']
                : null,
        );
    }

    public static function fromEntity(Resume $resume): self
    {
        return self::fromArray([
            'id' => $resume->id(),
            'user_id' => $resume->userId(),
            'title' => $resume->title(),
            'status' => $resume->status(),
            'visibility' => $resume->visibility(),
            'is_primary' => $resume->isDefault() ? 1 : 0,
            'completeness_score' => $resume->completenessScore(),
            'file_path' => $resume->filePath(),
            'file_mime' => $resume->fileMime(),
            'file_size_bytes' => $resume->fileSizeBytes(),
            'created_at' => $resume->createdAt(),
            'updated_at' => $resume->updatedAt(),
            'deleted_at' => $resume->deletedAt(),
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'is_primary' => $this->isDefault ? 1 : 0,
            'is_default' => $this->isDefault,
            'completeness_score' => $this->completenessScore,
            'file_path' => $this->filePath,
            'file_mime' => $this->fileMime,
            'file_size_bytes' => $this->fileSizeBytes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
