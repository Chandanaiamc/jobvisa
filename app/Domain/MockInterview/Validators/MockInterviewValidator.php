<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\Validators;

use JobVisa\App\Domain\MockInterview\Exceptions\MockInterviewException;

final class MockInterviewValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw MockInterviewException::invalidResume();
        }
    }

    public function assertJobId(int $jobId): void
    {
        if ($jobId < 1) {
            throw MockInterviewException::invalidJob();
        }
    }

    public function assertSessionId(int $sessionId): void
    {
        if ($sessionId < 1) {
            throw MockInterviewException::sessionNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw MockInterviewException::historyNotFound();
        }
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, string>
     */
    public function normalizeAnswers(array $answers): array
    {
        $out = [];
        foreach ($answers as $key => $value) {
            $id = trim((string) $key);
            $text = trim((string) $value);
            if ($id === '' || $text === '') {
                continue;
            }
            $out[$id] = mb_substr($text, 0, 5000);
        }
        if ($out === []) {
            throw MockInterviewException::answersRequired();
        }

        return $out;
    }
}
