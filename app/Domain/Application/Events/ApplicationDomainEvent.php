<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Application bounded context.
 */
final class ApplicationDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.application';
    }
}
