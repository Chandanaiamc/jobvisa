<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Resume bounded context.
 */
final class ResumeDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.resume';
    }
}
