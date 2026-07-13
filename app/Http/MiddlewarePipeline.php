<?php

declare(strict_types=1);

namespace JobVisa\App\Http;

use JobVisa\App\Http\Middleware\MiddlewareInterface;
use RuntimeException;

/**
 * Runs middleware aliases then the route action.
 */
final class MiddlewarePipeline
{
    /**
     * @param  array<string, class-string<MiddlewareInterface>|callable(): MiddlewareInterface>  $aliases
     */
    public function __construct(
        private array $aliases = []
    ) {
    }

    /**
     * @param  list<string>  $middleware
     * @param  callable(): mixed  $destination
     */
    public function run(array $middleware, callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            function (callable $next, string $alias): callable {
                return function () use ($alias, $next): mixed {
                    $instance = $this->resolve($alias);

                    return $instance->handle($next);
                };
            },
            $destination
        );

        return $pipeline();
    }

    private function resolve(string $alias): MiddlewareInterface
    {
        if (!isset($this->aliases[$alias])) {
            throw new RuntimeException("Unknown middleware alias [{$alias}].");
        }

        $binding = $this->aliases[$alias];

        if (is_callable($binding) && !is_string($binding)) {
            $instance = $binding();
        } else {
            /** @var class-string<MiddlewareInterface> $binding */
            $instance = container($binding);
        }

        if (!$instance instanceof MiddlewareInterface) {
            throw new RuntimeException("Middleware [{$alias}] must implement MiddlewareInterface.");
        }

        return $instance;
    }
}
