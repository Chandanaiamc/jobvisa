<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

/**
 * Domain exception for the Job bounded context.
 */
final class JobException extends DomainException
{
    public static function notFound(): self
    {
        return new self('Job not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this job.');
    }

    public static function employerProfileRequired(): self
    {
        return new self('Employer profile is required before managing jobs.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot change job status from %s to %s.', $from, $to));
    }
}
