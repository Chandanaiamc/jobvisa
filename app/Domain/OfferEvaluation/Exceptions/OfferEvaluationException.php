<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class OfferEvaluationException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the offer evaluation assistant.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function analysisNotFound(): self
    {
        return new self('Offer evaluation not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Offer evaluation history entry not found.');
    }

    public static function invalidOffer(): self
    {
        return new self('Provide a valid job title and base salary to evaluate the offer.');
    }
}
