<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewScheduling\Policies;

/**
 * Ownership gates for scheduled interviews.
 */
final class InterviewSchedulingPolicy
{
    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job  must include employer_user_id
     */
    public function canSchedule(array $actor, ?array $job): bool
    {
        return $this->isEmployerOwner($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $interview
     * @param  array<string, mixed>|null  $job
     */
    public function canManageAsEmployer(array $actor, ?array $interview, ?array $job): bool
    {
        if ($interview === null || !$this->isEmployerOwner($actor, $job)) {
            return false;
        }

        return (int) ($interview['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0)
            || (int) ($job['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $interview
     */
    public function canViewAsSeeker(array $actor, ?array $interview): bool
    {
        if ($interview === null || (string) ($actor['role'] ?? '') !== 'seeker') {
            return false;
        }

        return (int) ($actor['id'] ?? 0) > 0
            && (int) ($actor['id'] ?? 0) === (int) ($interview['candidate_user_id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $interview
     */
    public function canRespondAsSeeker(array $actor, ?array $interview): bool
    {
        if (!$this->canViewAsSeeker($actor, $interview)) {
            return false;
        }

        return (string) ($interview['status'] ?? '') === 'proposed';
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    private function isEmployerOwner(array $actor, ?array $job): bool
    {
        if ($job === null || (string) ($actor['role'] ?? '') !== 'employer') {
            return false;
        }
        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($job['employer_user_id'] ?? 0);

        return $actorId > 0 && $ownerId > 0 && $actorId === $ownerId;
    }
}
