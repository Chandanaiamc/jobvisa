<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class SalaryIntelligenceException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use salary intelligence.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function predictionNotFound(): self
    {
        return new self('Salary prediction not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Salary history entry not found.');
    }
}
