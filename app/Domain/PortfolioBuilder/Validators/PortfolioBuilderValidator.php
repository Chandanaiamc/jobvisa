<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\Validators;

use JobVisa\App\Domain\PortfolioBuilder\Exceptions\PortfolioBuilderException;

final class PortfolioBuilderValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw PortfolioBuilderException::invalidResume();
        }
    }

    public function assertPlanId(int $planId): void
    {
        if ($planId < 1) {
            throw PortfolioBuilderException::planNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw PortfolioBuilderException::historyNotFound();
        }
    }

    public function normalizeCareerGoal(?string $goal): string
    {
        $goal = trim((string) $goal);

        return $goal === '' ? '' : mb_substr($goal, 0, 255);
    }
}
