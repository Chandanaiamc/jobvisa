<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Immutable fact that something happened in the domain.
 */
interface DomainEventInterface
{
    public function eventName(): string;

    public function occurredAt(): \DateTimeImmutable;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
