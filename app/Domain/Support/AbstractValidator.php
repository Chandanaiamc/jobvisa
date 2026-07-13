<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use JobVisa\App\Domain\Contracts\ValidatorInterface;

/**
 * Domain validator shell — no rule evaluation yet.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        return [];
    }
}
