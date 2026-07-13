<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Company\DTO;

use JobVisa\App\Domain\Support\DataTransferObject;

/**
 * Transfer shape for Company data crossing layer boundaries.
 */
final class CompanyData extends DataTransferObject
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
