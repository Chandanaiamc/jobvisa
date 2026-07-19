<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewScheduling\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class InterviewSchedulingException extends DomainException
{
    public static function notFound(): self
    {
        return new self('Interview not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this interview.');
    }

    public static function notShortlisted(): self
    {
        return new self('Interviews can only be scheduled for shortlisted applications.');
    }

    public static function activeExists(): self
    {
        return new self('An active interview already exists for this application.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot change interview status from %s to %s.', $from, $to));
    }

    public static function invalidScheduleTime(): self
    {
        return new self('Interview time must be a valid future UTC datetime.');
    }

    public static function invalidTimezone(): self
    {
        return new self('Timezone must be a valid IANA timezone identifier.');
    }
}
