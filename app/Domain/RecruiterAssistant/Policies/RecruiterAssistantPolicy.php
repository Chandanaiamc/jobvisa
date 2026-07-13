<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\Policies;

/**
 * Employer-only recruiter assistant access.
 */
final class RecruiterAssistantPolicy
{
    /** @param array<string, mixed> $actor */
    public function canUse(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'employer'
            && (int) ($actor['id'] ?? 0) > 0;
    }

    /** @param array<string, mixed> $actor */
    public function canManageHistory(array $actor): bool
    {
        return $this->canUse($actor);
    }
}
