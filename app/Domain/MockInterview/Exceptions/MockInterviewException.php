<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class MockInterviewException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the mock interview simulator.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function invalidJob(): self
    {
        return new self('Select a valid target job for the mock interview.');
    }

    public static function jobNotFound(): self
    {
        return new self('Target job not found or not available.');
    }

    public static function sessionNotFound(): self
    {
        return new self('Mock interview session not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Mock interview history entry not found.');
    }

    public static function answersRequired(): self
    {
        return new self('Provide answers for at least one interview question before analyzing.');
    }
}
