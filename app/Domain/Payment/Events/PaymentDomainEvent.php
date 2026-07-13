<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Payment\Events;

use JobVisa\App\Domain\Support\DomainEvent;

/**
 * Placeholder domain event for the Payment bounded context.
 */
final class PaymentDomainEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'domain.payment';
    }
}
