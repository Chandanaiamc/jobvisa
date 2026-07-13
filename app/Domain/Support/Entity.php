<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use JobVisa\App\Domain\Contracts\EntityInterface;

/**
 * Base domain entity / aggregate root shell.
 */
abstract class Entity implements EntityInterface
{
    protected int|string|null $id = null;

    public function id(): int|string|null
    {
        return $this->id;
    }

    protected function setId(int|string|null $id): void
    {
        $this->id = $id;
    }

    /**
     * Reconstitute an entity from persistence (repository hydration).
     */
    public static function reconstitute(int|string $id): static
    {
        $entity = new static();
        $entity->setId($id);

        return $entity;
    }
}
