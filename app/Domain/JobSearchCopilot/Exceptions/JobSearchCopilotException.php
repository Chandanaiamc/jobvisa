<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class JobSearchCopilotException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the job search copilot.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function planNotFound(): self
    {
        return new self('Job search copilot plan not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Job search copilot history entry not found.');
    }
}
