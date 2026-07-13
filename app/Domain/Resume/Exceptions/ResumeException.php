<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class ResumeException extends DomainException
{
    public static function invalidTitle(): self
    {
        return new self('Resume title is required.');
    }

    public static function invalidVisibility(): self
    {
        return new self('Invalid resume visibility.');
    }

    public static function invalidStatus(): self
    {
        return new self('Invalid resume status.');
    }

    public static function alreadyDeleted(): self
    {
        return new self('This resume has been deleted.');
    }

    public static function notFound(): self
    {
        return new self('Resume not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to manage this resume.');
    }
}
