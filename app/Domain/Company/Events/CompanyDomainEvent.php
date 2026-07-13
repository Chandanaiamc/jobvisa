<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Company\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Company bounded context.
 */
final class CompanyDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.company';
    }
}
