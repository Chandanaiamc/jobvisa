<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;

/**
 * Jobseeker owner-only AI resume builder access.
 */
final class ResumeBuilderPolicy
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
    public function canGenerate(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('update', $resume, $actor);
    }

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor, Resume $resume): bool
    {
        return $this->canGenerate($actor, $resume);
    }
}
