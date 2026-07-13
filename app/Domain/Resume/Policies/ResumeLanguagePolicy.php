<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;

/**
 * Authorization for resume language child records.
 */
final class ResumeLanguagePolicy
{
    public function __construct(
        private readonly ResumePolicy $resumePolicy
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    public function canView(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('view', $resume, $actor);
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    public function canManage(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('update', $resume, $actor);
    }
}
