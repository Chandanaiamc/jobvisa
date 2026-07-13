<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

/**
 * Immutable-ish data transfer object base.
 *
 * Concrete DTOs hold typed public properties or constructor args only.
 * No business rules belong here.
 */
abstract class DataTransferObject
{
    /**
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
