<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

/**
 * Authorization for seeker profile resources.
 *
 * Owner (seeker) may edit. Admin/staff may view. Employers may not edit.
 */
final class ProfileAccess
{
    /**
     * @param  array<string, mixed>|null  $actor
     */
    public function canView(?array $actor, int $targetUserId): bool
    {
        if ($actor === null || $targetUserId < 1) {
            return false;
        }

        $role = (string) ($actor['role'] ?? '');
        $actorId = (int) ($actor['id'] ?? 0);

        if (in_array($role, ['admin', 'super_admin', 'staff'], true)) {
            return true;
        }

        return $role === 'seeker' && $actorId === $targetUserId;
    }

    /**
     * @param  array<string, mixed>|null  $actor
     */
    public function canEdit(?array $actor, int $targetUserId): bool
    {
        if ($actor === null || $targetUserId < 1) {
            return false;
        }

        $role = (string) ($actor['role'] ?? '');
        $actorId = (int) ($actor['id'] ?? 0);

        if ($role === 'employer') {
            return false;
        }

        return $role === 'seeker' && $actorId === $targetUserId;
    }
}
