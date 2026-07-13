<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Aggregates;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;

/**
 * Resume aggregate root — enforces multi-resume lifecycle invariants.
 */
final class ResumeAggregate
{
    public function __construct(
        private Resume $resume
    ) {
    }

    public function resume(): Resume
    {
        return $this->resume;
    }

    public function id(): int|string|null
    {
        return $this->resume->id();
    }

    public function updateDetails(string $title, string $visibility): void
    {
        if ($this->resume->isSoftDeleted()) {
            throw ResumeException::alreadyDeleted();
        }

        $this->resume->rename($title);
        $this->resume->changeVisibility($visibility);
    }

    public function publish(): void
    {
        if ($this->resume->isSoftDeleted()) {
            throw ResumeException::alreadyDeleted();
        }

        $this->resume->markPublished();
    }

    public function draft(): void
    {
        if ($this->resume->isSoftDeleted()) {
            throw ResumeException::alreadyDeleted();
        }

        $this->resume->markDraft();
    }

    public function makeDefault(): void
    {
        if ($this->resume->isSoftDeleted()) {
            throw ResumeException::alreadyDeleted();
        }

        $this->resume->markDefault(true);
    }

    public function clearDefaultFlag(): void
    {
        $this->resume->markDefault(false);
    }

    public function softDelete(): void
    {
        $this->resume->softDelete();
    }

    public function setCompletionPercentage(int $score): void
    {
        $this->resume->setCompletenessScore($score);
    }
}
