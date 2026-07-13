<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class PortfolioBuilderException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to use the portfolio builder.');
    }

    public static function resumeNotFound(): self
    {
        return new self('Resume not found.');
    }

    public static function invalidResume(): self
    {
        return new self('Invalid resume identifier.');
    }

    public static function planNotFound(): self
    {
        return new self('Portfolio plan not found.');
    }

    public static function historyNotFound(): self
    {
        return new self('Portfolio builder history entry not found.');
    }
}
