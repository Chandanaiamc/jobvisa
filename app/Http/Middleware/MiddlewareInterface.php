<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

/**
 * HTTP middleware contract.
 */
interface MiddlewareInterface
{
    /**
     * @param  callable(): mixed  $next
     */
    public function handle(callable $next): mixed;
}
