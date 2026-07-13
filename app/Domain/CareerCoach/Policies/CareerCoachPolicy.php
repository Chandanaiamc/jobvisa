<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;

/**
 * Jobseeker owner-only career coach access (admin may view via resume view).
 */
final class CareerCoachPolicy
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

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor, Resume $resume): bool
    {
        return $this->canRecalculate($actor, $resume);
    }
}
