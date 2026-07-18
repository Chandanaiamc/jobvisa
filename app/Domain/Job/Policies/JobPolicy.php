<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Policies;

use JobVisa\App\Domain\Support\AbstractPolicy;
use JobVisa\App\Domain\Contracts\EntityInterface;

/**
 * Employer ownership gates for job management.
 */
final class JobPolicy extends AbstractPolicy
{
    public function allows(string $action, ?EntityInterface $resource = null, mixed $actor = null): bool
    {
        if (!is_array($actor)) {
            return false;
        }

        return match ($action) {
            'create', 'viewAny' => $this->isEmployer($actor),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    public function canCreate(array $actor): bool
    {
        return $this->isEmployer($actor);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job  must include employer_user_id
     */
    public function canManage(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canPublish(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canArchive(array $actor, ?array $job): bool
    {
        return $this->ownsJob($actor, $job);
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
     * @param  array<string, mixed>|null  $job
     */
    private function ownsJob(array $actor, ?array $job): bool
    {
        if ($job === null || !$this->isEmployer($actor)) {
            return false;
        }

        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($job['employer_user_id'] ?? 0);

        return $actorId > 0 && $ownerId > 0 && $actorId === $ownerId;
    }
}
