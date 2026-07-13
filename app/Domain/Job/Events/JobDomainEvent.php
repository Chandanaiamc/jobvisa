<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Job\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Job bounded context.
 */
final class JobDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.job';
    }
}
