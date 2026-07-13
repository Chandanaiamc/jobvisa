<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\EmployerDashboard\Policies;

/**
 * Employer-only AI dashboard access.
 */
final class EmployerDashboardPolicy
{
    /**
     * @param  array<string, mixed>  $actor
     */
    public function canView(array $actor): bool
    {
        return (string) ($actor['role'] ?? '') === 'employer'
            && (int) ($actor['id'] ?? 0) > 0;
    }

    /**
     * Refresh/recalculate insights (same ownership boundary as view).
     *
     * @param  array<string, mixed>  $actor
     */
    public function canRefresh(array $actor): bool
    {
        return $this->canView($actor);
    }
}
