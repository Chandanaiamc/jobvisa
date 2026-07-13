<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Employer\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Employer bounded context.
 */
final class EmployerDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.employer';
    }
}
