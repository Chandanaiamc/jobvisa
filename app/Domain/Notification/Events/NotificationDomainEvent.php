<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Notification\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Notification bounded context.
 */
final class NotificationDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.notification';
    }
}
