<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Entities;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Support\Entity;

/**
 * Resume entity — identity and lifecycle for a seeker resume document.
 */
final class Resume extends Entity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_EMPLOYERS = 'employers';
    public const VISIBILITY_PRIVATE = 'private';

    private int $userId = 0;
    private string $title = '';
    private string $status = self::STATUS_DRAFT;
    private string $visibility = self::VISIBILITY_EMPLOYERS;
    private ?string $filePath = null;
    private ?string $fileMime = null;
    private ?int $fileSizeBytes = null;
    private bool $isDefault = false;
    private int $completenessScore = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private ?string $deletedAt = null;

    public function userId(): int
    {
        return $this->userId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function visibility(): string
    {
        return $this->visibility;
    }

    public function filePath(): ?string
    {
        return $this->filePath;
    }

    public function fileMime(): ?string
    {
        return $this->fileMime;
    }

    public function fileSizeBytes(): ?int
    {
        return $this->fileSizeBytes;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /** Alias for is_primary column. */
    public function isPrimary(): bool
    {
        return $this->isDefault;
    }

    public function completenessScore(): int
    {
        return $this->completenessScore;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isSoftDeleted(): bool
    {
        return $this->deletedAt !== null && $this->deletedAt !== '';
    }

    public function rename(string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            throw ResumeException::invalidTitle();
        }

        $this->title = mb_substr($title, 0, 150);
    }

    public function changeVisibility(string $visibility): void
    {
        if (!in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_EMPLOYERS, self::VISIBILITY_PRIVATE], true)) {
            throw ResumeException::invalidVisibility();
        }

        $this->visibility = $visibility;
    }

    public function markDraft(): void
    {
        $this->status = self::STATUS_DRAFT;
    }

    public function markPublished(): void
    {
        $this->status = self::STATUS_PUBLISHED;
    }

    public function markDefault(bool $default = true): void
    {
        $this->isDefault = $default;
    }

    public function setCompletenessScore(int $score): void
    {
        $this->completenessScore = max(0, min(100, $score));
    }

    public function softDelete(): void
    {
        if ($this->isSoftDeleted()) {
            return;
        }

        $this->deletedAt = date('Y-m-d H:i:s');
        $this->isDefault = false;
        $this->status = self::STATUS_DRAFT;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        $entity = new self();
        $entity->setId((int) ($row['id'] ?? 0));
        $entity->userId = (int) ($row['user_id'] ?? 0);
        $entity->title = (string) ($row['title'] ?? '');
        $entity->status = (string) ($row['status'] ?? self::STATUS_DRAFT);
        $entity->visibility = (string) ($row['visibility'] ?? self::VISIBILITY_EMPLOYERS);
        $entity->filePath = isset($row['file_path']) && $row['file_path'] !== '' ? (string) $row['file_path'] : null;
        $entity->fileMime = isset($row['file_mime']) && $row['file_mime'] !== '' ? (string) $row['file_mime'] : null;
        $entity->fileSizeBytes = isset($row['file_size_bytes']) ? (int) $row['file_size_bytes'] : null;
        $entity->isDefault = !empty($row['is_primary']);
        $entity->completenessScore = (int) ($row['completeness_score'] ?? 0);
        $entity->createdAt = isset($row['created_at']) ? (string) $row['created_at'] : null;
        $entity->updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : null;
        $entity->deletedAt = isset($row['deleted_at']) && $row['deleted_at'] !== null && $row['deleted_at'] !== ''
            ? (string) $row['deleted_at']
            : null;

        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'user_id' => $this->userId,
            'title' => $this->title,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'file_path' => $this->filePath,
            'file_mime' => $this->fileMime,
            'file_size_bytes' => $this->fileSizeBytes,
            'is_primary' => $this->isDefault ? 1 : 0,
            'completeness_score' => $this->completenessScore,
            'deleted_at' => $this->deletedAt,
        ];
    }

    public static function reconstitute(int|string $id): static
    {
        $entity = new static();
        $entity->setId($id);

        return $entity;
    }

    /**
     * @internal used by factory
     */
    public function assignOwner(int $userId): void
    {
        $this->userId = $userId;
    }
}
