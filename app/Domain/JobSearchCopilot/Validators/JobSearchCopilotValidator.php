<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\Validators;

use JobVisa\App\Domain\JobSearchCopilot\Exceptions\JobSearchCopilotException;

final class JobSearchCopilotValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw JobSearchCopilotException::invalidResume();
        }
    }

    public function assertPlanId(int $planId): void
    {
        if ($planId < 1) {
            throw JobSearchCopilotException::planNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw JobSearchCopilotException::historyNotFound();
        }
    }

    public function normalizeCareerGoal(?string $goal): string
    {
        $goal = trim((string) $goal);
        if ($goal === '') {
            return '';
        }

        return mb_substr($goal, 0, 255);
    }
}
