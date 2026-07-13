<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class InterviewAssistantException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the interview assistant.');
    }

    public static function jobNotFound(): self
    {
        return new self('Job not found.');
    }

    public static function applicationNotFound(): self
    {
        return new self('Application not found for this job.');
    }

    public static function sessionNotFound(): self
    {
        return new self('Interview session not found.');
    }

    public static function invalidScorecard(string $message = 'Invalid scorecard values.'): self
    {
        return new self($message);
    }

    public static function resumeRequired(): self
    {
        return new self('This application has no resume attached. Attach a resume before preparing an interview.');
    }
}
