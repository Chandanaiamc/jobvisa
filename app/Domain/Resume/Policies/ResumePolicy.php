<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Policies;

use JobVisa\App\Domain\Contracts\EntityInterface;
use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Support\AbstractPolicy;

/**
 * Resume authorization — owner edit; admin view; employer cannot edit.
 */
final class ResumePolicy extends AbstractPolicy
{
    public function allows(string $action, ?EntityInterface $resource = null, mixed $actor = null): bool
    {
        if (!is_array($actor)) {
            return false;
        }

        $role = (string) ($actor['role'] ?? '');
        $actorId = (int) ($actor['id'] ?? 0);

        if ($actorId < 1) {
            return false;
        }

        $isAdmin = in_array($role, ['admin', 'super_admin', 'staff'], true);
        $isEmployer = $role === 'employer';
        $isSeeker = $role === 'seeker';

        if ($isEmployer) {
            return false;
        }

        if ($resource === null) {
            return match ($action) {
                'create', 'viewAny' => $isSeeker || $isAdmin,
                default => false,
            };
        }

        if (!$resource instanceof Resume) {
            return false;
        }

        $ownerId = $resource->userId();
        $isOwner = $isSeeker && $actorId === $ownerId;

        return match ($action) {
            'view' => $isOwner || $isAdmin,
            'update', 'delete', 'publish', 'draft', 'setDefault' => $isOwner,
            default => false,
        };
    }
}
