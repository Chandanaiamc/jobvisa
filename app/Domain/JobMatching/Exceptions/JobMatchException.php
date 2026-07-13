<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class JobMatchException extends DomainException
{
    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function jobNotFound(): self
    {
        return new self('Job not found or is not available for matching.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to match this resume.');
    }

    public static function invalidInput(string $message = 'Invalid match request.'): self
    {
        return new self($message);
    }
}
