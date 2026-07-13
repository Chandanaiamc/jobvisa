<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Support;

use DomainException as PhpDomainException;
use Throwable;

/**
 * Base exception for domain rule / invariant failures.
 */
abstract class DomainException extends PhpDomainException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
