<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Policies;

use JobVisa\App\Domain\Contracts\EntityInterface;
use JobVisa\App\Domain\Support\AbstractPolicy;

/**
 * Seeker/employer authorization for job applications.
 */
final class ApplicationPolicy extends AbstractPolicy
{
    public function allows(string $action, ?EntityInterface $resource = null, mixed $actor = null): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    public function canApply(array $actor): bool
    {
        return $this->isSeeker($actor);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $application
     */
    public function canViewOwn(array $actor, ?array $application): bool
    {
        return $this->ownsApplication($actor, $application);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $application
     */
    public function canWithdraw(array $actor, ?array $application): bool
    {
        if (!$this->ownsApplication($actor, $application)) {
            return false;
        }
        $status = (string) ($application['status'] ?? '');

        return in_array($status, ['submitted', 'reviewing'], true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job  must include employer_user_id when checked
     */
    public function canManageAsEmployer(array $actor, ?array $job): bool
    {
        if (!$this->isEmployer($actor) || $job === null) {
            return false;
        }
        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($job['employer_user_id'] ?? 0);

        return $actorId > 0 && $ownerId > 0 && $actorId === $ownerId;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function isSeeker(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'seeker' && (int) ($actor['id'] ?? 0) > 0;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function isEmployer(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'employer' && (int) ($actor['id'] ?? 0) > 0;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $application
     */
    private function ownsApplication(array $actor, ?array $application): bool
    {
        if ($application === null || !$this->isSeeker($actor)) {
            return false;
        }
        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($application['user_id'] ?? 0);

        return $actorId > 0 && $ownerId > 0 && $actorId === $ownerId;
    }
}
