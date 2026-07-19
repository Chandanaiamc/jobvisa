<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\HiringCompletion\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class HiringCompletionException extends DomainException
{
    public static function notFound(): self
    {
        return new self('Hire completion not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this hire completion.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot change hire completion status from %s to %s.', $from, $to));
    }

    public static function alreadyExists(): self
    {
        return new self('A hire completion already exists for this application.');
    }

    public static function applicationNotHireable(string $status): self
    {
        return new self(sprintf('Cannot complete hire for application status %s.', $status));
    }
}
