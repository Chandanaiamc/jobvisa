<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class ResumeBuilderException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to access this AI resume builder.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function versionNotFound(): self
    {
        return new self('AI resume version not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Generation history entry not found.');
    }

    public static function invalidAction(string $message = 'Invalid resume builder action.'): self
    {
        return new self($message);
    }
}
