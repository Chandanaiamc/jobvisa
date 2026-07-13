<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use DateTimeImmutable;
use JobVisa\App\Domain\Contracts\DomainEventInterface;

/**
 * Base domain event.
 */
abstract class DomainEvent implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(?DateTimeImmutable $occurredAt = null)
    {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable('now');
    }

    abstract public function eventName(): string;

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [];
    }
}
