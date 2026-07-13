<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Marker for domain entities / aggregate roots.
 */
interface EntityInterface
{
    /**
     * Primary identity of the entity, when assigned.
     */
    public function id(): int|string|null;
}
