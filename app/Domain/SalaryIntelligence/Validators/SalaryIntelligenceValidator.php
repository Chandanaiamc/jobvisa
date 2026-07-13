<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\Validators;

use JobVisa\App\Domain\SalaryIntelligence\Exceptions\SalaryIntelligenceException;

final class SalaryIntelligenceValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw SalaryIntelligenceException::invalidResume();
        }
    }

    public function assertPredictionId(int $predictionId): void
    {
        if ($predictionId < 1) {
            throw SalaryIntelligenceException::predictionNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw SalaryIntelligenceException::historyNotFound();
        }
    }
}
