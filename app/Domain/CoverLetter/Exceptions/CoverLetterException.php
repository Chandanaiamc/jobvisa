<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class CoverLetterException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to access this cover letter generator.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function jobNotFound(): self
    {
        return new self('Job not found or not available.');
    }

    public static function versionNotFound(): self
    {
        return new self('Cover letter version not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Cover letter history entry not found.');
    }

    public static function invalidStyle(string $message = 'Choose a valid writing style.'): self
    {
        return new self($message);
    }

    public static function invalidAction(string $message = 'Invalid cover letter action.'): self
    {
        return new self($message);
    }
}
