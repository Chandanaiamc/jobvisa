<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Future dispatcher for domain events (queue/sync listeners).
 *
 * Not wired into the application bootstrap yet.
 */
interface EventDispatcherInterface
{
    public function dispatch(DomainEventInterface $event): void;
}
