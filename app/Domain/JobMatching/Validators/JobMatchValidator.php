<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Validators;

use JobVisa\App\Domain\JobMatching\Exceptions\JobMatchException;

final class JobMatchValidator
{
    public function assertIds(int $resumeId, int $jobId): void
    {
        if ($resumeId < 1) {
            throw JobMatchException::invalidInput('A valid resume is required.');
        }
        if ($jobId < 1) {
            throw JobMatchException::invalidInput('A valid job is required.');
        }
    }

    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw JobMatchException::invalidInput('A valid resume is required.');
        }
    }
}
