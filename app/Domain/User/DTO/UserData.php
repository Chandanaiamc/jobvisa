<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\User\DTO;

use JobVisa\App\Domain\Support\DataTransferObject;

/**
 * Transfer shape for User data crossing layer boundaries.
 */
final class UserData extends DataTransferObject
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [];
    }
}
