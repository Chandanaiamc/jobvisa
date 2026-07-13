<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use JobVisa\App\Domain\Contracts\EntityInterface;
use JobVisa\App\Domain\Contracts\PolicyInterface;

/**
 * Domain policy shell — denies by default until rules are implemented.
 */
abstract class AbstractPolicy implements PolicyInterface
{
    public function allows(string $action, ?EntityInterface $resource = null, mixed $actor = null): bool
    {
        return false;
    }
}
