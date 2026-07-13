<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\Validators;

use JobVisa\App\Domain\CareerCoach\Exceptions\CareerCoachException;

final class CareerCoachValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw CareerCoachException::invalidResume();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw CareerCoachException::historyNotFound();
        }
    }

    public function normalizeTargetRole(?string $targetRole): ?string
    {
        if ($targetRole === null) {
            return null;
        }
        $role = trim($targetRole);
        if ($role === '') {
            return null;
        }

        return mb_substr($role, 0, 191);
    }
}
