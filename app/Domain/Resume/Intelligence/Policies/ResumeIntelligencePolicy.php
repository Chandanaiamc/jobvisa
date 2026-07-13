<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;

/**
 * Owner-only intelligence access (admin may view via resume view policy).
 */
final class ResumeIntelligencePolicy
{
    public function __construct(
        private readonly ResumePolicy $resumePolicy
    ) {
    }

    /** @param array<string, mixed> $actor */
    public function canView(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('view', $resume, $actor);
    }

    /** @param array<string, mixed> $actor */
    public function canRecalculate(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('update', $resume, $actor);
    }

    /** Soft-delete / clear score history (owner update). */
    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor, Resume $resume): bool
    {
        return $this->canRecalculate($actor, $resume);
    }
}
