<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class CareerCoachException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to access this career coach.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function historyNotFound(): self
    {
        return new self('Coaching history entry not found.');
    }
}
