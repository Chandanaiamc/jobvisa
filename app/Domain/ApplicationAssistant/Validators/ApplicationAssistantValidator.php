<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Validators;

use JobVisa\App\Domain\ApplicationAssistant\Exceptions\ApplicationAssistantException;

final class ApplicationAssistantValidator
{
    public function assertJobId(int $jobId): void
    {
        if ($jobId < 1) {
            throw ApplicationAssistantException::invalidJob();
        }
    }

    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw ApplicationAssistantException::invalidResume();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw ApplicationAssistantException::historyNotFound();
        }
    }

    public function assertAnalysisId(int $analysisId): void
    {
        if ($analysisId < 1) {
            throw ApplicationAssistantException::analysisNotFound();
        }
    }
}
