<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class ApplicationAssistantException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the application assistant.');
    }

    public static function jobNotFound(): self
    {
        return new self('Job not found or not available.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidJob(): self
    {
        return new self('Invalid job identifier.');
    }

    public static function invalidResume(): self
    {
        return new self('Select a valid resume to analyze.');
    }

    public static function historyNotFound(): self
    {
        return new self('Analysis history entry not found.');
    }

    public static function analysisNotFound(): self
    {
        return new self('Analysis not found.');
    }
}
