<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\Policies;

/**
 * Employer-only interview assistant access.
 */
final class InterviewAssistantPolicy
{
    /** @param array<string, mixed> $actor */
    public function canUse(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'employer'
            && (int) ($actor['id'] ?? 0) > 0;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $job
     */
    public function canPrepareForJob(array $actor, array $job): bool
    {
        if (!$this->canUse($actor)) {
            return false;
        }

        return (int) ($job['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0)
            || (int) ($job['employer_user_id'] ?? 0) === 0; // owned lookup already scoped
    }

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor): bool
    {
        return $this->canUse($actor);
    }

    /** @param array<string, mixed> $actor */
    public function canScore(array $actor): bool
    {
        return $this->canUse($actor);
    }
}
