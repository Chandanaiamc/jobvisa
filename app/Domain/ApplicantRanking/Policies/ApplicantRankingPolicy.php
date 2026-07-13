<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\Policies;

/**
 * Employer ownership gate for applicant ranking.
 */
final class ApplicantRankingPolicy
{
    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job  must include employer_user_id or be ownership-verified
     */
    public function canView(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canRecalculate(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canManageHistory(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
    }

    /**
     * Seekers/admins cannot use employer ranking unless they own the employer profile.
     *
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    private function ownsJob(array $actor, ?array $job): bool
    {
        if ($job === null) {
            return false;
        }

        $role = (string) ($actor['role'] ?? '');
        if ($role !== 'employer') {
            return false;
        }

        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($job['employer_user_id'] ?? 0);

        return $actorId > 0 && $ownerId > 0 && $actorId === $ownerId;
    }
}
