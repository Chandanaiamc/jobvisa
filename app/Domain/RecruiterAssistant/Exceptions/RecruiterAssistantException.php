<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class RecruiterAssistantException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the recruiter assistant.');
    }

    public static function invalidQuery(string $message = 'Enter a search query.'): self
    {
        return new self($message);
    }
}
