<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class ApplicantRankingException extends DomainException
{
    public static function jobNotFound(): self
    {
        return new self('Job not found.');
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to rank applicants for this job.');
    }

    public static function invalidFilters(string $message = 'Invalid ranking filters.'): self
    {
        return new self($message);
    }
}
