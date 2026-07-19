<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\HiringCompletion\Policies;

/**
 * Ownership gates for hire completions.
 */
final class HiringCompletionPolicy
{
    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canManageAsEmployer(array $actor, ?array $hire, ?array $job): bool
    {
        if ($hire === null || !$this->isEmployerOwner($actor, $job)) {
            return false;
        }

        return (int) ($hire['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0)
            || (int) ($job['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $hire
     */
    public function canViewAsSeeker(array $actor, ?array $hire): bool
    {
        if ($hire === null || (string) ($actor['role'] ?? '') !== 'seeker') {
            return false;
        }

        return (int) ($actor['id'] ?? 0) > 0
            && (int) ($actor['id'] ?? 0) === (int) ($hire['candidate_user_id'] ?? 0);
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
