<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Contracts;

/**
 * Domain validation contract (rules evaluated against DTO/entity input).
 */
interface ValidatorInterface
{
    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>  Empty list means valid (foundation only).
     */
    public function validate(array|object $input): array;
}
