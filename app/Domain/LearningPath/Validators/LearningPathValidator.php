<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\Validators;

use JobVisa\App\Domain\LearningPath\Exceptions\LearningPathException;

final class LearningPathValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw LearningPathException::invalidResume();
        }
    }

    public function assertPathId(int $pathId): void
    {
        if ($pathId < 1) {
            throw LearningPathException::pathNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw LearningPathException::historyNotFound();
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

    public function normalizeMilestoneKey(?string $key): string
    {
        $key = trim((string) $key);
        if ($key === '' || !preg_match('/^[a-zA-Z0-9_\-]{1,64}$/', $key)) {
            throw LearningPathException::milestoneNotFound();
        }

        return $key;
    }
}
