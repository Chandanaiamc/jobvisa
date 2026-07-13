<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;

/**
 * Resume ownership + published-job eligibility for matching.
 */
final class JobMatchPolicy
{
    public function __construct(
        private readonly ResumePolicy $resumePolicy
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canViewMatch(array $actor, Resume $resume, ?array $job): bool
    {
        if (!$this->resumePolicy->allows('view', $resume, $actor)) {
            return false;
        }

        return $this->isEligibleJob($job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canRecalculate(array $actor, Resume $resume, ?array $job): bool
    {
        if (!$this->resumePolicy->allows('update', $resume, $actor)) {
            return false;
        }

        return $this->isEligibleJob($job);
    }

    /** @param array<string, mixed> $actor */
    public function canViewRecommendations(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('view', $resume, $actor);
    }

    /** Owner may persist match snapshots / recalculate. */
    /** @param array<string, mixed> $actor */
    public function canManageMatches(array $actor, Resume $resume): bool
    {
        return $this->resumePolicy->allows('update', $resume, $actor);
    }

    /**
     * Employers cannot use seeker match endpoints (role boundary via jobseeker middleware + seeker ownership).
     *
     * @param  array<string, mixed>|null  $job
     */
    public function isEligibleJob(?array $job): bool
    {
        if ($job === null) {
            return false;
        }

        return (string) ($job['status'] ?? '') === 'published';
    }
}
