<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;

final class OfferEvaluationPolicy
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
    public function canEvaluate(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('update', $resume, $actor);
    }

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor, Resume $resume): bool
    {
        return $this->canEvaluate($actor, $resume);
    }

    /** @param array<string, mixed> $actor */
    public function canExport(array $actor, Resume $resume): bool
    {
        return $this->canView($actor, $resume);
    }
}
