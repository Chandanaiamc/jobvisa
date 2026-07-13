<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\User\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the User bounded context.
 */
final class UserDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.user';
    }
}
