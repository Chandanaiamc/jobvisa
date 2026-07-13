<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Factories;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeData;
use JobVisa\App\Domain\Resume\Entities\Resume;

/**
 * Creates Resume aggregates / entities without persistence.
 */
final class ResumeFactory
{
    public function newDraft(int $userId, string $title = 'Untitled Resume', bool $asDefault = false): ResumeAggregate
    {
        $title = trim($title);

        return $this->fromRow([
            'id' => 0,
            'user_id' => $userId,
            'title' => $title !== '' ? $title : 'Untitled Resume',
            'status' => Resume::STATUS_DRAFT,
            'visibility' => Resume::VISIBILITY_EMPLOYERS,
            'is_primary' => $asDefault ? 1 : 0,
            'completeness_score' => 0,
            'file_path' => null,
            'file_mime' => null,
            'file_size_bytes' => null,
            'created_at' => null,
            'updated_at' => null,
            'deleted_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function fromRow(array $row): ResumeAggregate
    {
        return new ResumeAggregate(Resume::fromRow($row));
    }

    public function fromData(ResumeData $data): ResumeAggregate
    {
        return $this->fromRow($data->toArray());
    }

    public function fromEntity(Resume $resume): ResumeAggregate
    {
        return new ResumeAggregate($resume);
    }
}
