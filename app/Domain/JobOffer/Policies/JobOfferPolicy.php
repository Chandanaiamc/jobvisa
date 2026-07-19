<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobOffer\Policies;

/**
 * Ownership gates for job offers.
 */
final class JobOfferPolicy
{
    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canCreate(array $actor, ?array $job): bool
    {
        return $this->isEmployerOwner($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $offer
     * @param  array<string, mixed>|null  $job
     */
    public function canManageAsEmployer(array $actor, ?array $offer, ?array $job): bool
    {
        if ($offer === null || !$this->isEmployerOwner($actor, $job)) {
            return false;
        }

        return (int) ($offer['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0)
            || (int) ($job['employer_user_id'] ?? 0) === (int) ($actor['id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $offer
     */
    public function canViewAsSeeker(array $actor, ?array $offer): bool
    {
        if ($offer === null || (string) ($actor['role'] ?? '') !== 'seeker') {
            return false;
        }

        return (int) ($actor['id'] ?? 0) > 0
            && (int) ($actor['id'] ?? 0) === (int) ($offer['candidate_user_id'] ?? 0);
    }

    /**
     * Seeker may accept/decline only when status is sent (caller may auto-expire first).
     *
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $offer
     */
    public function canRespondAsSeeker(array $actor, ?array $offer): bool
    {
        if (!$this->canViewAsSeeker($actor, $offer)) {
            return false;
        }

        return (string) ($offer['status'] ?? '') === 'sent';
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
