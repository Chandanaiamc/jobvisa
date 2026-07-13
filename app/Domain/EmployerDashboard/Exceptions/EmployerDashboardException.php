<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\EmployerDashboard\Exceptions;

use JobVisa\App\Domain\Support\DomainException;

final class EmployerDashboardException extends DomainException
{
    public static function forbidden(): self
    {
        return new self('You are not allowed to view the employer AI dashboard.');
    }
}
