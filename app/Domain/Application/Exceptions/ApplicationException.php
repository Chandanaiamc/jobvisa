<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

/**
 * Domain exception for the Application bounded context.
 */
final class ApplicationException extends DomainException
{
    public static function notFound(): self
    {
        return new self('Application not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this application.');
    }

    public static function jobNotOpen(): self
    {
        return new self('Applications are only accepted for published jobs.');
    }

    public static function duplicate(): self
    {
        return new self('You have already applied to this job.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot change application status from %s to %s.', $from, $to));
    }

    public static function resumeRequired(): self
    {
        return new self('A valid resume is required to apply.');
    }

    public static function resumeNotOwned(): self
    {
        return new self('Resume not found.');
    }
}
