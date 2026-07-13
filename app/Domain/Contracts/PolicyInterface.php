<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Authorization policy for a domain resource.
 */
interface PolicyInterface
{
    /**
     * Whether the actor may perform an action on the resource.
     *
     * Foundation stub — no authorization logic yet.
     */
    public function allows(string $action, ?EntityInterface $resource = null, mixed $actor = null): bool;
}
