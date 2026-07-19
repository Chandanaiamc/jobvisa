<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobOffer\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class JobOfferException extends DomainException
{
    public static function notFound(): self
    {
        return new self('Offer not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this offer.');
    }

    public static function notShortlisted(): self
    {
        return new self('Offers can only be created for shortlisted applications.');
    }

    public static function activeExists(): self
    {
        return new self('An active offer already exists for this application.');
    }

    public static function invalidTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot change offer status from %s to %s.', $from, $to));
    }

    public static function alreadyExpired(): self
    {
        return new self('This offer has expired.');
    }
}
