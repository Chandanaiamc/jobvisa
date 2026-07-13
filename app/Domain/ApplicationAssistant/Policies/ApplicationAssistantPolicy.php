<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Policies;

use JobVisa\App\Domain\Resume\Entities\Resume;

/**
 * Jobseeker owner-only application assistant access.
 */
final class ApplicationAssistantPolicy
{
    /** @param array<string, mixed> $actor */
    public function canUse(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'seeker'
            && (int) ($actor['id'] ?? 0) > 0;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>|null  $job
     */
    public function canAnalyzeJob(array $actor, ?array $job): bool
    {
        if (!$this->canUse($actor)) {
            return false;
        }
        if ($job === null) {
            return false;
        }

        return (string) ($job['status'] ?? '') === 'published';
    }

    /** @param array<string, mixed> $actor */
    public function canAnalyzeResume(array $actor, Resume $resume): bool
    {
        if (!$this->canUse($actor)) {
            return false;
        }

        return (int) ($actor['id'] ?? 0) === $resume->userId()
            && $resume->deletedAt() === null;
    }

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor): bool
    {
        return $this->canUse($actor);
    }
}
