<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\Validators;

use JobVisa\App\Domain\SkillGap\Exceptions\SkillGapException;

final class SkillGapValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw SkillGapException::invalidResume();
        }
    }

    public function assertJobId(int $jobId): void
    {
        if ($jobId < 1) {
            throw SkillGapException::invalidJob();
        }
    }

    public function assertAnalysisId(int $analysisId): void
    {
        if ($analysisId < 1) {
            throw SkillGapException::analysisNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw SkillGapException::historyNotFound();
        }
    }
}
